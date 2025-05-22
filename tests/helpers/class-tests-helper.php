<?php

use WPRuby_Table_Rates\Core\Settings;

class Tests_Helper
{
    public static function get_settings(array $settings_args = []): Settings
    {
        $args = wp_parse_args($settings_args, [
            'title' => 'Table Rate Shipping',
            'debug_mode' => 'no',
            'hide_other_methods' => 'no',
            'calculation_method' => 'sum',
            'handling_fees' => [
                'value' => 0,
                'type' => 'amount',
            ],
            'rules' => [],
        ]);

        return new Settings(1, $args);
    }

    public static function add_product(array $args = [])
    {
        $args = wp_parse_args($args, [
            'price' => 1,
            'weight' => 1,
            'downloadable' => false,
            'virtual' => false,
            'length' => 1,
            'width' => 1,
            'height' => 1,
            'shipping_classes' => [],
            'categories' => [],
            'tags' => [],
            'attributes' => [
                'name' => 'color',
                'value' => 'red',
            ],
        ]);
        $product = new WC_Product;
        $product->set_price($args['price']);
        $product->set_weight($args['weight']);
        $product->set_downloadable($args['downloadable']);
        $product->set_virtual($args['virtual']);
        $product->set_length($args['length']);
        $product->set_width($args['width']);
        $product->set_height($args['height']);
        $product->save();

        if (isset($args['categories'])) {
            $product->set_category_ids($args['categories']);
        }

        if (isset($args['stock_status'])) {
            $product->set_stock_status($args['stock_status']);
        }

        if (isset($args['shipping_classes'])) {
            foreach ($args['shipping_classes'] as $shipping_class) {
                $product->set_shipping_class_id($shipping_class);
            }
        }

        if (isset($args['tags'])) {
            $product->set_tag_ids($args['tags']);
        }

        if (isset($args['attributes'])) {
            $color_at = wc_create_attribute([
                'id' => 1,
                'name' => 'color',
            ]);
            $wc_attribute = new WC_Product_Attribute;
            $wc_attribute->set_id($color_at);
            $wc_attribute->set_options([$args['attributes']['value']]);
            $attributes = [$wc_attribute];
            $product->set_attributes($attributes);
            $product->save();
        }

        return $product;
    }

    public static function prepare_package(WC_Product $product, array $args = [])
    {
        $args = wp_parse_args($args, [
            'applied_coupons' => ['free-shipping'],
            'customer_id' => 1,
            'country' => 'US',
            'city' => 'london',
            'postcode' => '5000',
            'quantity' => 1,
        ]);

        return [
            'contents' => [
                $product->get_id() => [
                    'data' => $product,
                    'quantity' => $args['quantity'],
                ],
            ],
            'contents_cost' => $product->get_price(),
            'cart_subtotal' => $product->get_price(),
            'applied_coupons' => $args['applied_coupons'],
            'user' => [
                'ID' => $args['customer_id'],
            ],
            'destination' => [
                'country' => $args['country'],
                'city' => $args['city'],
                'postcode' => $args['postcode'],
            ],
        ];
    }

    public static function randomString($length = 10): string
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }
}
