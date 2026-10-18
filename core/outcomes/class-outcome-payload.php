<?php

namespace RuleHook\Core\Outcomes;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Constants;

/**
 * One order event for POST /v1/order-outcomes (margin spec §4.1).
 *
 * Every event carries the order's full snapshot, so RuleHook can rebuild the
 * order from whichever events reach it. Nothing personal is included: the
 * order id is hashed with a secret that stays in the store, and the
 * destination is the country, state and postcode prefix only (§4.4).
 */
class Outcome_Payload
{
    const MAX_SHIPPING_LINES = 10;

    const MAX_ITEMS = 200;

    public static function build(\WC_Order $order, string $status, string $occurred_at): array
    {
        $created = $order->get_date_created();
        $session_id = (string) $order->get_meta(Order_Outcomes::SESSION_META);
        $shipping_lines = self::shipping_lines($order);

        return array_merge(
            [
                'order_ref' => self::order_ref($order),
                'status' => $status,
                'occurred_at' => $occurred_at,
                'order_created_at' => $created ? gmdate('Y-m-d\TH:i:s\Z', $created->getTimestamp()) : $occurred_at,
                'source' => 'live',
                'session_id' => $session_id !== '' ? $session_id : null,
                'currency' => strtoupper((string) $order->get_currency()),
                'shipping_lines' => $shipping_lines,
                'shipping_refunded' => round((float) $order->get_total_shipping_refunded(), 4),
                'coupon_free_shipping' => self::coupon_free_shipping($order, $shipping_lines),
                'destination' => self::destination($order),
            ],
            self::items($order)
        );
    }

    /**
     * hex(HMAC-SHA256(store salt, order id)). Saved on the order, so the
     * merchant can find an order from a ref RuleHook shows them.
     */
    public static function order_ref(\WC_Order $order): string
    {
        $ref = (string) $order->get_meta(Order_Outcomes::ORDER_REF_META);

        if (preg_match('/^[a-f0-9]{64}$/', $ref)) {
            return $ref;
        }

        $ref = hash_hmac('sha256', (string) $order->get_id(), self::salt());
        $order->update_meta_data(Order_Outcomes::ORDER_REF_META, $ref);
        $order->save_meta_data();

        return $ref;
    }

    /**
     * 32 random bytes, made on first use and never sent to RuleHook.
     */
    private static function salt(): string
    {
        $salt = (string) get_option(Constants::ORDER_REF_SALT_KEY, '');

        if (! preg_match('/^[a-f0-9]{64}$/', $salt)) {
            // add_option() does nothing if another request saved one first.
            add_option(Constants::ORDER_REF_SALT_KEY, bin2hex(random_bytes(32)), '', false);
            $salt = (string) get_option(Constants::ORDER_REF_SALT_KEY, '');
        }

        return hex2bin($salt);
    }

    /**
     * The chosen rates. The rate id is saved on the item at checkout
     * (Order_Outcomes::remember_rate_id); for older orders, or ones made in
     * wp-admin, it falls back to WooCommerce's usual `method_id:instance_id`.
     */
    private static function shipping_lines(\WC_Order $order): array
    {
        $lines = [];

        foreach ($order->get_items('shipping') as $item) {
            if (! $item instanceof \WC_Order_Item_Shipping) {
                continue;
            }

            $method_id = (string) $item->get_method_id();

            if ($method_id === '' || mb_strlen($method_id) > 255) {
                continue;
            }

            $instance_id = (string) $item->get_instance_id();
            $rate_id = (string) $item->get_meta(Order_Outcomes::RATE_ID_META);
            $rulehook = $method_id === 'rulehook';

            if ($rate_id === '' || mb_strlen($rate_id) > 255) {
                $rate_id = (! $rulehook && $instance_id !== '' && $instance_id !== '0') ? $method_id.':'.$instance_id : $method_id;
            }

            $lines[] = [
                'source' => $rulehook ? 'rulehook' : 'external',
                'method_id' => $method_id,
                'instance_id' => ($rulehook || $instance_id === '' || $instance_id === '0') ? null : mb_substr($instance_id, 0, 255),
                'rate_id' => $rate_id,
                'label' => mb_substr(wp_strip_all_tags((string) $item->get_method_title()), 0, 255),
                // Shipping totals are stored excluding tax.
                'charged' => max(0, round((float) $item->get_total(), 4)),
            ];
        }

        return array_slice($lines, 0, self::MAX_SHIPPING_LINES);
    }

    /**
     * Value and weight totals, and product ids for server-side packing.
     * Weight is item weight × quantity in grams, as RuleHook's weight rules
     * see it, or null when no product has a weight.
     */
    private static function items(\WC_Order $order): array
    {
        $subtotal = 0.0;
        $total = 0.0;
        $count = 0;
        $grams = 0.0;
        $has_weight = false;
        $items = [];

        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $quantity = (int) $item->get_quantity();
            $subtotal += (float) $item->get_subtotal();
            $total += (float) $item->get_total();
            $count += max(0, $quantity);

            $product = $item->get_product();
            $weight = $product ? $product->get_weight() : '';

            if ($weight !== '' && $weight !== null && is_numeric($weight)) {
                $has_weight = true;
                $grams += wc_get_weight((float) $weight, 'g') * max(0, $quantity);
            }

            // The same id /evaluate sends: the variation's, for a variation.
            $product_id = (int) ($item->get_variation_id() ?: $item->get_product_id());

            if ($product_id > 0 && $quantity >= 1 && count($items) < self::MAX_ITEMS) {
                $items[] = ['product_id' => $product_id, 'quantity' => $quantity];
            }
        }

        return [
            'items_subtotal' => round(max(0, $subtotal), 4),
            'items_total' => round(max(0, $total), 4),
            'items_count' => $count,
            'total_weight_grams' => $has_weight ? (int) round($grams) : null,
            'items' => $items,
        ];
    }

    /**
     * True when a coupon on the order gives free shipping and the shopper
     * paid nothing for it. Coupon codes themselves are never sent.
     */
    private static function coupon_free_shipping(\WC_Order $order, array $shipping_lines): bool
    {
        if ($shipping_lines === [] || array_sum(array_column($shipping_lines, 'charged')) > 0) {
            return false;
        }

        foreach ($order->get_coupon_codes() as $code) {
            $coupon = new \WC_Coupon($code);

            if ($coupon->get_id() && $coupon->get_free_shipping()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The shipping address, or the billing address for an order without
     * one, cut to country, state and postcode prefix.
     */
    private static function destination(\WC_Order $order): array
    {
        $type = $order->get_shipping_country() ? 'shipping' : 'billing';
        $country = strtoupper((string) $order->{"get_{$type}_country"}());
        $state = (string) $order->{"get_{$type}_state"}();
        $postcode = (string) $order->{"get_{$type}_postcode"}();

        if (! preg_match('/^[A-Z]{2}$/', $country)) {
            // An order with no address at all, for example a virtual one.
            $country = strtoupper((string) WC()->countries->get_base_country());
            $state = '';
            $postcode = '';
        }

        return [
            'country' => $country,
            'state' => $state !== '' ? mb_substr($state, 0, 100) : null,
            'postcode_prefix' => self::postcode_prefix($country, $postcode),
        ];
    }

    /**
     * The part of a postcode RuleHook may keep, so a full postcode never
     * leaves the store. Matches the server's PostcodePrefix (spec §4.4).
     */
    public static function postcode_prefix(string $country, string $postcode): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $postcode));

        if ($normalized === '') {
            return null;
        }

        switch (strtoupper($country)) {
            case 'AU':
            case 'NZ':
                $prefix = substr((string) preg_replace('/\D/', '', $normalized), 0, 2);
                break;
            case 'US':
                $prefix = substr((string) preg_replace('/\D/', '', $normalized), 0, 3);
                break;
            case 'GB':
                // The postcode area: the leading letters.
                $prefix = preg_match('/^[A-Z]{1,2}/', $normalized, $matches) ? $matches[0] : '';
                break;
            case 'CA':
            case 'IE':
                $prefix = substr($normalized, 0, 1);
                break;
            default:
                $prefix = substr($normalized, 0, 2);
        }

        return $prefix === '' ? null : $prefix;
    }
}
