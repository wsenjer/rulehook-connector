<?php

namespace RuleHook\Core\Sync;

use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Exception;
use RuleHook\Core\Api\Sync;
use RuleHook\Core\Constants;

class Store_Sync_Service
{
    protected static $_instance = null;

    /**
     * @return self
     */
    public static function get_instance()
    {

        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public function sync(): bool
    {
        $api_key = 'key_nman3zxzmjjnxpy4wpdceeharffbcagnvf9so54c4z';
        $client = new Client(Constants::API_URL, $api_key);

        try {
            $sync = new Sync($client);

            // Get basic payload without products (less memory intensive)
            $payload = $this->getPayload();

            // Sync the current batch
            return $sync->syncStoreMetadata($payload);

        } catch (Exception $e) {
            error_log('RuleHook API Error: '.$e->getMessage());

            return false;
        }
    }

    private function getPayload(): array
    {
        $storeBaseLocation = wc_get_base_location();
        $payload = [];
        $payload['store_id'] = get_site_url();
        $payload['base_location'] = $storeBaseLocation;
        $payload['currency'] = get_woocommerce_currency();
        $payload['products'] = $this->getNormalizedProducts();
        $payload['shipping_zones'] = $this->getNormalizedShippingZones();

        return $payload;

    }

    private function getNormalizedProducts(int $limit = 100): array
    {
        $normalizedProducts = [];

        // Get all published products
        $args = [
            'status' => 'publish',
            'limit' => $limit,
            'return' => 'objects',
        ];

        $products = wc_get_products($args);

        foreach ($products as $product) {
            // Skip variations as we'll handle them with the parent
            if ($product->get_type() === 'variation') {
                continue;
            }

            // Get product tags
            $tags = [];
            $product_tags = get_the_terms($product->get_id(), 'product_tag');
            if (! empty($product_tags) && ! is_wp_error($product_tags)) {
                foreach ($product_tags as $tag) {
                    $tags[] = $tag->name;
                }
            }

            // Get shipping class
            $shipping_class = '';
            $shipping_class_id = $product->get_shipping_class_id();
            if ($shipping_class_id) {
                $shipping_class_term = get_term($shipping_class_id, 'product_shipping_class');
                if (! is_wp_error($shipping_class_term) && $shipping_class_term) {
                    $shipping_class = $shipping_class_term->name;
                }
            }

            // Add simple product or variable parent
            $normalizedProducts[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'weight' => (float) $product->get_weight(),
                'tags' => $tags,
                'shipping_class' => $shipping_class,
            ];

            // Add product variations if applicable
            if ($product->get_type() === 'variable') {
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $normalizedProducts[] = [
                            'id' => $variation->get_id(),
                            'name' => $variation->get_name(),
                            'weight' => (float) $variation->get_weight(),
                            'tags' => $tags, // Variations share the parent's tags
                            'shipping_class' => $variation->get_shipping_class() ?: $shipping_class,
                        ];
                    }
                }
            }
        }

        return $normalizedProducts;
    }

    /**
     * Get all WooCommerce shipping zones and methods formatted for RuleHook sync
     *
     * @return array Normalized shipping zones array
     */
    private function getNormalizedShippingZones(): array
    {
        $normalizedZones = [];
        $zones = \WC_Shipping_Zones::get_zones();

        // Add each defined shipping zone
        foreach ($zones as $zone_data) {
            $zone = new \WC_Shipping_Zone($zone_data['id']);
            $locations = [];

            // Get zone locations
            foreach ($zone->get_zone_locations() as $location) {
                if ($location->type === 'country') {
                    $locations[] = $location->code;
                } elseif ($location->type === 'state') {
                    $locations[] = $location->code;
                } elseif ($location->type === 'postcode') {
                    $locations[] = $location->code;
                }
            }

            // Get shipping methods for this zone
            $methods = [];
            foreach ($zone->get_shipping_methods(true) as $method) {
                $methods[] = [
                    'id' => $method->id.':'.$method->instance_id,
                    'title' => $method->title ?: $method->get_title(),
                    'cost' => $this->getShippingMethodCost($method),
                ];
            }

            $normalizedZones[] = [
                'id' => $zone->get_id(),
                'name' => $zone->get_zone_name(),
                'locations' => ! empty($locations) ? $locations : ['*'],
                'methods' => $methods,
            ];
        }

        // Add the "Rest of the World" zone
        $rest_of_world = new \WC_Shipping_Zone(0);
        $row_methods = [];

        foreach ($rest_of_world->get_shipping_methods(true) as $method) {
            $row_methods[] = [
                'id' => $method->id.':'.$method->instance_id,
                'title' => $method->title ?: $method->get_title(),
                'cost' => $this->getShippingMethodCost($method),
            ];
        }

        $normalizedZones[] = [
            'id' => 0,
            'name' => $rest_of_world->get_zone_name(),
            'locations' => ['*'],
            'methods' => $row_methods,
        ];

        return $normalizedZones;
    }

    /**
     * Get the cost of a shipping method
     *
     * @param  \WC_Shipping_Method  $method  Shipping method object
     * @return float Shipping method cost
     */
    private function getShippingMethodCost($method): float
    {
        // Different shipping methods store cost in different ways
        if ($method->id === 'flat_rate' && isset($method->instance_settings['cost'])) {
            return (float) $method->instance_settings['cost'];
        } elseif ($method->id === 'free_shipping') {
            return 0.0;
        } elseif (isset($method->cost)) {
            return (float) $method->cost;
        } elseif (isset($method->instance_settings) && isset($method->instance_settings['cost'])) {
            return (float) $method->instance_settings['cost'];
        }

        return 0.0;
    }
}
