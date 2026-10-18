<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Actions\Actions_Factory;
use RuleHook\Core\Actions\Subtitle_Action;
use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Evaluator;
use RuleHook\Core\Api\Exception;

class Calculator
{
    private static $in_process = [];

    /**
     * Set while the connector scans the store's own rates, so neither the
     * RuleHook method nor the rates filter calls the API.
     *
     * @var bool
     */
    private static $suspended = false;

    private string $shipping_title = 'Shipping';

    public function __construct(string $shipping_title)
    {
        $this->shipping_title = $shipping_title;
    }

    public static function suspend(bool $suspended): void
    {
        self::$suspended = $suspended;
    }

    public static function is_suspended(): bool
    {
        return self::$suspended;
    }

    /**
     * Protocol 1: RuleHook's own rates, added by its shipping method.
     *
     * @return array<Rate>
     */
    public function calculate(array $package): array
    {
        if (self::$suspended) {
            return [];
        }

        $package_hash = self::get_package_hash($package);

        if (isset(self::$in_process[$package_hash])) {
            return self::$in_process[$package_hash]; // already computed this request
        }

        $api_key = get_option(Constants::API_KEY_KEY);

        if (! $api_key) {
            // Fail silently, no rates
            return [];
        }

        $cart_data = $this->build_cart_payload($package);

        try {
            $client = new Client(Constants::api_url(), $api_key);
            $evaluator = new Evaluator($client);

            $response = $evaluator->evaluate($cart_data);
            External_Rates::remember_from_response($response);
            $rates = $response['rates'];

            if (empty($rates)) {
                return [];
            }

            $woocommerceRates = [];
            foreach ($rates as $rate) {

                if (! empty($rate['actions'])) {
                    $this->handleActions($rate['actions']);
                }

                $woocommerceRates[] = $this->build_rate($rate);
            }

            self::$in_process[$package_hash] = $woocommerceRates;

            return $woocommerceRates;

        } catch (Exception $e) {
            return [];
        }

    }

    /**
     * A RuleHook rate from the API response, as WooCommerce rate args.
     */
    public function build_rate(array $rate): Rate
    {
        return (new Rate)
            ->setId('rulehook:'.sanitize_title($rate['id']))
            ->setLabel(
                sprintf(
                    '%s%s',
                    sanitize_text_field($rate['label']),
                    ! empty($rate['eta']) ? ' ('.$rate['eta'].')' : ''
                )
            )
            ->setCost(floatval($rate['cost']))
            ->setMetaData(
                [
                    'actions' => array_map(function ($action) {
                        return $action['type'];
                    }, $rate['actions'] ?? []),
                    'ETA' => $rate['eta'] ?? '',
                    'Notes' => $rate['description'] ?? '',
                    Subtitle_Action::META_KEY => $this->get_subtitle($rate['actions'] ?? []),
                ]
            );
    }

    public function build_cart_payload($package): array
    {
        $items = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $items[] = [
                'product_id' => $product->get_id(),
                'quantity' => $cart_item['quantity'],
                'weight' => $product->get_weight(),
                'price' => $product->get_price(),
                'tags' => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'slugs']),
                'dimensions' => [
                    'length' => $product->get_length(),
                    'width' => $product->get_width(),
                    'height' => $product->get_height(),
                ],
                'shipping_class' => $product->get_shipping_class(),
                'product_attributes' => $product->get_attributes(),
                'categories' => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'slugs']),
                'stock_status' => $product->get_stock_status(),
            ];
        }

        return [
            'store_domain' => wp_parse_url(home_url(), PHP_URL_HOST),
            'destination' => [
                'country' => $package['destination']['country'],
                'state' => $package['destination']['state'],
                'city' => $package['destination']['city'],
                'postcode' => $package['destination']['postcode'],
            ],
            'origin' => [
                'country' => WC()->countries->get_base_country(),
                'state' => WC()->countries->get_base_state(),
                'city' => WC()->countries->get_base_city(),
                'postcode' => WC()->countries->get_base_postcode(),
            ],
            'cart_total' => WC()->cart->get_cart_contents_total(),
            'user_role' => wp_get_current_user()->roles[0] ?? 'guest',
            'coupons' => WC()->cart->get_applied_coupons(),
            'items' => $items,
        ];
    }

    /**
     * Runs the connector-side actions. With protocol 2 the server already
     * applied hide_other_methods to every rate, so it isn't repeated here.
     */
    public function handleActions(array $actions, bool $protocol_2 = false): void
    {
        foreach ($actions as $action) {
            if ($protocol_2 && ($action['type'] ?? '') === 'hide_other_methods') {
                continue;
            }

            $ruleAction = Actions_Factory::make($action['type'], $action['payload'] ?? []);
            if (! $ruleAction) {
                continue; // Skip unknown actions
            }

            $ruleAction->execute();
        }

    }

    public function get_subtitle(array $actions): string
    {
        $subtitle = '';
        foreach ($actions as $action) {
            if (($action['type'] ?? '') === 'set_subtitle') {
                $subtitle = sanitize_text_field($action['payload']['subtitle'] ?? '');
            }
        }

        return $subtitle;
    }

    public static function get_package_hash($package)
    {
        $payload = [
            'dest' => $package['destination'],
            'items' => array_map(function ($i) {
                return [
                    'id' => $i['product_id'],
                    'qty' => $i['quantity'],
                    'w' => $i['data']->get_weight(),
                    'd' => $i['data']->get_dimensions(false),
                    'price' => $i['data']->get_price(),
                ];
            }, $package['contents']),
        ];

        return 'rule_hook'.md5(wp_json_encode($payload));
    }
}
