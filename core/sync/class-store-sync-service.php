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
        $payload['coupons'] = $this->getCoupons();

        $payload['product_categories'] = $this->getProductCategories();
        $payload['product_tags'] = $this->getProductTags();
        $payload['shipping_classes'] = $this->getShippingClasses();
        $payload['user_roles'] = $this->getUserRoles();
        $payload['product_attributes'] = $this->getProductAttributes();

        return $payload;

    }

    private function getNormalizedProducts(int $limit = 50000): array
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
                            'price' => intval($variation->get_price()) * 100, // Convert to cents
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

    private function getProductCategories(): array
    {
        $categories = [];
        $args = [
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ];

        $terms = get_terms($args);
        foreach ($terms as $term) {
            if (! is_wp_error($term)) {
                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                ];
            }
        }

        return $categories;

    }

    public function getProductTags(): array
    {
        $tags = [];
        $args = [
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ];

        $terms = get_terms($args);
        foreach ($terms as $term) {
            if (! is_wp_error($term)) {
                $tags[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                ];
            }
        }

        return $tags;

    }

    public function getShippingClasses(): array
    {
        $shipping_classes = [];
        $args = [
            'taxonomy' => 'product_shipping_class',
            'hide_empty' => false,
        ];

        $terms = get_terms($args);
        foreach ($terms as $term) {
            if (! is_wp_error($term)) {
                $shipping_classes[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                ];
            }
        }

        return $shipping_classes;

    }

    public function getUserRoles(): array
    {
        $roles = [];
        $wp_roles = wp_roles()->roles;

        foreach ($wp_roles as $role => $details) {
            $roles[] = [
                'id' => $role,
                'name' => $details['name'],
            ];
        }

        return $roles;
    }

    public function getProductAttributes(): array
    {
        $product_attributes = [];
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        foreach ($attribute_taxonomies as $attribute) {
            $product_attributes[] = [
                'id' => intval($attribute->attribute_id),
                'name' => $attribute->attribute_label,
                'terms' => $this->getTaxonomy('pa_'.$attribute->attribute_name),
            ];
        }

        return $product_attributes;

    }

    private function getTaxonomy(string $taxonomy): array
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);

        $result = [];
        foreach ($terms as $term) {
            if (! is_wp_error($term)) {
                $result[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                ];
            }
        }

        return $result;

    }
}
