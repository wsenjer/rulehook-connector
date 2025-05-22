<?php

namespace RuleHook\Core;

use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Evaluator;
use RuleHook\Core\Api\Exception;

class Calculator
{
    /**
     * @return array<Rate>
     */
    public function calculate(array $package): array
    {
        $cart_data = $this->build_cart_payload($package);
        //   $api_token = get_option('rulehook_api_token');
        $api_token = 'key_wzcd01gy67v69kdzvetdyevkjb42owmvacteymftit';
        if (! $api_token) {
            // Fail silently, no rates
            return [];
        }

        try {
            $client = new Client(Constants::API_URL, $api_token);
            $evaluator = new Evaluator($client);

            $rates = $evaluator->evaluateRates($cart_data);

            if (empty($rates)) {
                return [];
            }

            $woocommerceRates = [];
            foreach ($rates as $rate) {
                $woocommerceRates[] = (new Rate)
                    ->setId($this->id.':'.sanitize_title($rate['id']))
                    ->setLabel(sanitize_text_field($rate['label']))
                    ->setCost(floatval($rate['cost']))
                    ->setMetaData(
                        [
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

    private function build_cart_payload($package)
    {
        $items = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $items[] = [
                'product_id' => $product->get_id(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price(),
                'weight' => $product->get_weight(),
                'tags' => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'slugs']),
            ];
        }

        return [
            'destination' => [
                'country' => $package['destination']['country'],
                'state' => $package['destination']['state'],
                'postcode' => $package['destination']['postcode'],
                'city' => $package['destination']['city'],
            ],
            'total' => WC()->cart->get_cart_contents_total(),
            'items' => $items,
        ];
    }
}
