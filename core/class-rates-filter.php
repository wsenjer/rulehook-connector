<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Actions\Subtitle_Action;
use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Evaluator;

/**
 * Protocol 2: once the store has opted in, RuleHook sees every rate.
 *
 * WooCommerce runs `woocommerce_package_rates` after every shipping method has
 * added its rates, and caches the result per package. At that point this
 * filter sends the store's own rates to RuleHook with the cart, in one call,
 * and rebuilds the list from the response: RuleHook's rates are added, the
 * store's rates are kept, renamed or repriced, and any the response leaves
 * out are hidden. On any error the original rates are returned unchanged.
 */
class Rates_Filter
{
    const PRIORITY = 100;

    /**
     * Server-side limit on `external_rates` per request.
     */
    const MAX_EXTERNAL_RATES = 100;

    /**
     * Packages the RuleHook shipping method is available for this request,
     * keyed by package hash. Its rates are only added to those, as in 1.1.1.
     *
     * @var array<string, \WC_Shipping_Method>
     */
    private static $rulehook_methods = [];

    /**
     * @var array<string, array>
     */
    private static $responses = [];

    public static function register(): void
    {
        add_filter('woocommerce_package_rates', [self::class, 'filter'], self::PRIORITY, 2);
    }

    /**
     * Called by the RuleHook shipping method instead of adding rates itself.
     */
    public static function mark_rulehook_available(array $package, \WC_Shipping_Method $method): void
    {
        self::$rulehook_methods[Calculator::get_package_hash($package)] = $method;
    }

    public static function filter($rates, $package)
    {
        if (! is_array($rates) || ! is_array($package) || Calculator::is_suspended() || ! External_Rates::is_enabled()) {
            return $rates;
        }

        try {
            return (new self)->apply($rates, $package);
        } catch (\Throwable $e) {
            // Fail open: never break checkout.
            return $rates;
        }
    }

    private function apply(array $rates, array $package): array
    {
        $api_key = get_option(Constants::API_KEY_KEY);

        if (! $api_key || ! function_exists('WC') || ! WC()->cart) {
            return $rates;
        }

        $calculator = new Calculator('');
        [$sent, $originals, $unsent] = $this->collect_external_rates($rates, $package);

        $payload = $calculator->build_cart_payload($package);
        $payload['protocol'] = 2;
        $payload['external_rates'] = array_values($sent);

        $response = $this->evaluate($payload, $api_key);

        if ($response === null) {
            return $rates;
        }

        External_Rates::remember_from_response($response);

        $hash = Calculator::get_package_hash($package);
        $rulehook_method = self::$rulehook_methods[$hash] ?? null;

        if (($response['external_rates_enabled'] ?? null) !== true) {
            // The store opted out since the flag was cached, so RuleHook
            // ignored the store's rates. Treat the response as protocol 1.
            return $this->apply_protocol_1($rates, $package, $response['rates'], $rulehook_method, $calculator);
        }

        $rebuilt = $this->rebuild($response['rates'], $sent, $originals, $package, $rulehook_method, $calculator);

        if ($rebuilt === null) {
            return $rates;
        }

        // Rates RuleHook couldn't be sent are shown as the store made them.
        foreach ($unsent as $key => $rate) {
            if (! isset($rebuilt[$key])) {
                $rebuilt[$key] = $rate;
            }
        }

        return $rebuilt;
    }

    /**
     * @return array|null The response, or null on any error or timeout
     */
    private function evaluate(array $payload, string $api_key): ?array
    {
        $key = md5(wp_json_encode($payload));

        if (isset(self::$responses[$key])) {
            return self::$responses[$key];
        }

        try {
            $client = new Client(Constants::api_url(), $api_key, External_Rates::timeout());
            $response = (new Evaluator($client))->evaluate($payload);
        } catch (\Throwable $e) {
            return null;
        }

        return self::$responses[$key] = $response;
    }

    /**
     * Every rate except RuleHook's own, in the shape `external_rates` expects.
     * Rates RuleHook would reject (too long, negative cost, past the limit)
     * are kept aside and shown unchanged.
     *
     * @return array{0: array<string, array>, 1: array<string, \WC_Shipping_Rate>, 2: array}
     */
    private function collect_external_rates(array $rates, array $package): array
    {
        $sent = [];
        $originals = [];
        $unsent = [];
        $zone_name = $this->zone_name($package);

        foreach ($rates as $key => $rate) {
            if (! $rate instanceof \WC_Shipping_Rate) {
                $unsent[$key] = $rate;
                continue;
            }

            // RuleHook's rates come back in the response.
            if ($rate->get_method_id() === 'rulehook') {
                continue;
            }

            $id = (string) $rate->get_id();
            $method_id = (string) $rate->get_method_id();
            $cost = (float) $rate->get_cost();

            if (
                $id === '' || $method_id === '' || isset($sent[$id])
                || mb_strlen($id) > 255 || mb_strlen($method_id) > 255
                || $cost < 0 || count($sent) >= self::MAX_EXTERNAL_RATES
            ) {
                $unsent[$key] = $rate;
                continue;
            }

            $external_rate = [
                'id' => $id,
                'method_id' => $method_id,
                'instance_id' => (string) $rate->get_instance_id(),
                'label' => mb_substr(wp_strip_all_tags((string) $rate->get_label()), 0, 255),
                'cost' => $cost,
            ];

            if ($zone_name !== '') {
                $external_rate['meta'] = ['zone' => $zone_name];
            }

            $sent[$id] = $external_rate;
            $originals[$id] = $rate;
        }

        return [$sent, $originals, $unsent];
    }

    /**
     * The final list, in the response's order. Returns null when the
     * response is malformed, so the caller can fail open.
     */
    private function rebuild(array $response_rates, array $sent, array $originals, array $package, ?\WC_Shipping_Method $rulehook_method, Calculator $calculator): ?array
    {
        $rebuilt = [];
        $actions = [];

        foreach ($response_rates as $response_rate) {
            if (! is_array($response_rate) || ! isset($response_rate['cost']) || ! is_numeric($response_rate['cost'])) {
                return null;
            }

            $rate_actions = is_array($response_rate['actions'] ?? null) ? $response_rate['actions'] : [];

            if (($response_rate['source'] ?? 'rulehook') === 'external') {
                $id = (string) ($response_rate['external_id'] ?? '');

                if (! isset($originals[$id])) {
                    continue;
                }

                $rebuilt[$id] = $this->apply_changes($originals[$id], $response_rate, $sent[$id], $rate_actions, $calculator);
            } else {
                if (! isset($response_rate['id'], $response_rate['label'])) {
                    return null;
                }

                if ($rulehook_method === null) {
                    continue;
                }

                $wc_rate = $this->rulehook_rate($rulehook_method, $calculator->build_rate($response_rate));

                if ($wc_rate === null) {
                    continue;
                }

                $rebuilt[$wc_rate->get_id()] = $wc_rate;
            }

            foreach ($rate_actions as $action) {
                if (is_array($action) && isset($action['type'])) {
                    $actions[md5(wp_json_encode($action))] = $action;
                }
            }
        }

        // Notices run once, however many rates carry them.
        $calculator->handleActions(array_values($actions), true);

        return $rebuilt;
    }

    /**
     * Applies RuleHook's label and cost to the store's own rate object.
     */
    private function apply_changes(\WC_Shipping_Rate $rate, array $response_rate, array $sent, array $actions, Calculator $calculator): \WC_Shipping_Rate
    {
        $label = $response_rate['label'] ?? null;

        if (is_string($label) && $label !== '' && $label !== $sent['label']) {
            $rate->set_label(sanitize_text_field($label));
        }

        $cost = (float) $response_rate['cost'];

        if (abs($cost - $sent['cost']) > 0.000001) {
            $taxes = $this->taxes_for($rate, $cost);
            $rate->set_cost(wc_format_decimal($cost));
            $rate->set_taxes($taxes);
        }

        $subtitle = $calculator->get_subtitle($actions);

        if ($subtitle !== '') {
            $rate->add_meta_data(Subtitle_Action::META_KEY, $subtitle);
        }

        return $rate;
    }

    /**
     * Taxes for a repriced rate. Without this the original tax amount would
     * stay on the new cost.
     */
    private function taxes_for(\WC_Shipping_Rate $rate, float $cost): array
    {
        $had_tax = array_sum(array_map('floatval', (array) $rate->get_taxes())) > 0;

        if (! wc_tax_enabled() || ! ($had_tax || $this->method_is_taxable($rate))) {
            return array_map(function () {
                return 0;
            }, (array) $rate->get_taxes());
        }

        return \WC_Tax::calc_shipping_tax($cost, \WC_Tax::get_shipping_tax_rates());
    }

    /**
     * Whether the method that made the rate charges tax. Used when the
     * original rate had no tax to go by, for example a free rate.
     */
    private function method_is_taxable(\WC_Shipping_Rate $rate): bool
    {
        $method = null;
        $instance_id = (int) $rate->get_instance_id();

        if ($instance_id > 0) {
            $method = \WC_Shipping_Zones::get_shipping_method($instance_id);
        }

        if (! $method) {
            $methods = WC()->shipping()->get_shipping_methods();
            $method = $methods[$rate->get_method_id()] ?? null;
        }

        return $method instanceof \WC_Shipping_Method && $method->is_taxable();
    }

    /**
     * Builds a RuleHook rate through the RuleHook method's own add_rate(), so
     * taxes and rate filters behave exactly as in 1.1.1.
     */
    private function rulehook_rate(\WC_Shipping_Method $method, Rate $rate): ?\WC_Shipping_Rate
    {
        $previous = $method->rates;
        $method->rates = [];
        $method->add_rate($rate->getWoocommerceRate());
        $added = $method->rates;
        $method->rates = $previous;

        $wc_rate = reset($added);

        return $wc_rate instanceof \WC_Shipping_Rate ? $wc_rate : null;
    }

    /**
     * The store's rates stay as they are; RuleHook's rates and actions apply
     * as in 1.1.1, including the connector-side hide_other_methods.
     */
    private function apply_protocol_1(array $rates, array $package, array $response_rates, ?\WC_Shipping_Method $rulehook_method, Calculator $calculator): array
    {
        if ($rulehook_method === null) {
            return $rates;
        }

        $hide_others = false;

        foreach ($response_rates as $response_rate) {
            if (! is_array($response_rate) || ! isset($response_rate['id'], $response_rate['label'], $response_rate['cost'])) {
                return $rates;
            }

            $actions = is_array($response_rate['actions'] ?? null) ? $response_rate['actions'] : [];

            foreach ($actions as $action) {
                if (($action['type'] ?? '') === 'hide_other_methods') {
                    $hide_others = true;
                }
            }

            $calculator->handleActions($actions, true);

            $wc_rate = $this->rulehook_rate($rulehook_method, $calculator->build_rate($response_rate));

            if ($wc_rate !== null) {
                $rates[$wc_rate->get_id()] = $wc_rate;
            }
        }

        if ($hide_others) {
            $filtered = Helper::filter_table_rates($rates);

            if (count($filtered) > 0) {
                return $filtered;
            }
        }

        return $rates;
    }

    private function zone_name(array $package): string
    {
        try {
            $zone = \WC_Shipping_Zones::get_zone_matching_package($package);

            return $zone ? (string) $zone->get_zone_name() : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
