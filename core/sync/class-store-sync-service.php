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
        $api_key = get_option(Constants::API_KEY_KEY);
        $client = new Client(Constants::API_URL, $api_key);

        try {
            $sync = new Sync($client);
            $payload = $this->getPayload();
            $result = $sync->syncStoreMetadata($payload);
            if (! empty($result)) {
                $this->updateSyncData($result);

                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log('RuleHook API Error: '.$e->getMessage());

            return false;
        }
    }

    private function getPayload(): array
    {
        $storeBaseLocation = wc_get_base_location();
        $payload = [];
        $payload['team_id'] = get_option(Constants::TEAM_ID_KEY);
        $payload['store_id'] = parse_url(home_url(), PHP_URL_HOST);
        $payload['base_country'] = (isset($storeBaseLocation['country'])) ? $storeBaseLocation['country'] : '';
        $payload['base_state'] = (isset($storeBaseLocation['state'])) ? $storeBaseLocation['state'] : '';
        $payload['currency'] = get_woocommerce_currency();
        $payload['products'] = $this->getNormalizedProducts();
        $payload['shipping_zones'] = $this->getNormalizedShippingZones();
        $payload['coupons'] = $this->getCoupons();


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
                'price' => intval($product->get_price()) * 100, // Convert to cents
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
                            'price' =>  intval($variation->get_price()) * 100, // Convert to cents
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

    private function updateSyncData(array $result): void
    {

        $current_time = current_time('mysql');
        update_option(Constants::LAST_SYNC_TIME_KEY, $current_time);

        $productsSynced = $result['products_synced'] ?? 0;
        update_option(Constants::PRODUCTS_SYNCED_KEY, $productsSynced);

        $zonesSynced = $result['zones_synced'] ?? 0;
        update_option(Constants::SHIPPING_ZONES_SYNCED_KEY, $zonesSynced);

    }

    private function getCoupons(): array
    {
        $coupons = [];
        $args = [
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];

        $coupon_posts = get_posts($args);
        foreach ($coupon_posts as $coupon_post) {
            $coupon = new \WC_Coupon($coupon_post->ID);
            $coupons[] = [
                'id' => $coupon->get_id(),
                'code' => $coupon->get_code(),
                'amount' => intval($coupon->get_amount()) * 100, // Convert to cents
                'type' => $coupon->get_discount_type(),
            ];
        }

        return $coupons;
    }
}
