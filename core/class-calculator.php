<?php

namespace RuleHook\Core;

use RuleHook\Core\Actions\Actions_Factory;
use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Evaluator;
use RuleHook\Core\Api\Exception;

class Calculator
{
    private string $shipping_title = 'Shipping';

    public function __construct(string $shipping_title)
    {
        $this->shipping_title = $shipping_title;
    }

    /**
     * @return array<Rate>
     */
    public function calculate(array $package): array
    {
        $api_key = get_option(Constants::API_KEY_KEY);

        if (! $api_key) {
            // Fail silently, no rates
            return [];
        }

        $cart_data = $this->build_cart_payload($package);
        try {
            $client = new Client(Constants::API_URL, $api_key);
            $evaluator = new Evaluator($client);

            $rates = $evaluator->evaluateRates($cart_data);

            if (empty($rates)) {
                return [];
            }

            $woocommerceRates = [];
            foreach ($rates as $rate) {

                if (! empty($rate['actions'])) {
                    $this->handleActions($rate['actions']);
                }

                $woocommerceRates[] = (new Rate)
                    ->setId('rulehook:'.sanitize_title($rate['id']))
                    ->setLabel(sprintf('%s (%s)', $this->shipping_title, sanitize_text_field($rate['label'])))
                    ->setCost(floatval($rate['cost']))
                    ->setMetaData(
                        [
                            'actions' => array_map(function ($action) {
                                return $action['type'];
                            }, $rate['actions'] ?? []),
                            'ETA' => $rate['eta'] ?? '',
                            'Notes' => $rate['description'] ?? '',
                        ]
                    );

            }

            return $woocommerceRates;

        } catch (Exception $e) {
            error_log('rulehook.com API  Error: '.$e->getMessage());

            return [];
        }

    }

    private function build_cart_payload($package): array
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
            'store_domain' => parse_url(home_url(), PHP_URL_HOST),
            'destination' => [
                'country' => $package['destination']['country'],
                'state' => $package['destination']['state'],
                'city' => $package['destination']['city'],
                'postcode' => $package['destination']['postcode'],
            ],
            'cart_total' => WC()->cart->get_cart_contents_total(),
            'user_role' => wp_get_current_user()->roles[0] ?? 'guest',
            'coupons' => WC()->cart->get_applied_coupons(),
            'items' => $items,
        ];
    }

    private function handleActions(array $actions): void
    {
        foreach ($actions as $action) {
            $ruleAction = Actions_Factory::make($action['type'], $action['payload']);
            if (! $ruleAction) {
                error_log("Unknown action type: {$action['type']}");

                continue; // Skip unknown actions
            }
            $ruleAction->execute();
        }

    }
}
