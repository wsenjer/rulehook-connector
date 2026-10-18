<?php

namespace RuleHook\Core\Outcomes;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Exception;
use RuleHook\Core\Constants;
use RuleHook\Core\Margin_Sync;

/**
 * Tells RuleHook what happened to each order, for shipping margin (margin
 * spec §4–§5): when it's placed, and when it's completed, refunded or
 * cancelled.
 *
 * Nothing runs until RuleHook reports the store has opted in. Events are sent
 * from Action Scheduler, never during checkout, and a failed send is retried
 * a few times with backoff, then dropped.
 */
class Order_Outcomes
{
    const SEND_HOOK = 'rulehook_send_order_outcome';

    const GROUP = 'rulehook';

    /**
     * Seconds to wait before each retry. One more than this many failures
     * and the event is dropped.
     */
    const RETRY_DELAYS = [MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS, 2 * HOUR_IN_SECONDS];

    /**
     * The X-Session-ID sent on /evaluate during checkout. Saved on the order,
     * because later status changes happen in wp-admin, with no shopper.
     */
    const SESSION_META = '_rulehook_session_id';

    const ORDER_REF_META = '_rulehook_order_ref';

    /**
     * When the "placed" event was queued, so a retried payment doesn't
     * queue it again.
     */
    const PLACED_META = '_rulehook_outcome_placed_at';

    /**
     * On each shipping item: the id of the rate the shopper chose, which
     * WooCommerce doesn't keep.
     */
    const RATE_ID_META = '_rulehook_rate_id';

    /**
     * WooCommerce statuses that send an event, and the event they send.
     */
    const STATUS_EVENTS = [
        'completed' => 'completed',
        'refunded' => 'refunded',
        'cancelled' => 'cancelled',
    ];

    public static function register(): void
    {
        add_action('woocommerce_checkout_create_order_shipping_item', [self::class, 'remember_rate_id'], 10, 2);
        add_action('woocommerce_checkout_order_processed', [self::class, 'classic_order_placed'], 10, 3);
        add_action('woocommerce_store_api_checkout_order_processed', [self::class, 'order_placed']);
        add_action('woocommerce_order_status_changed', [self::class, 'status_changed'], 10, 4);
        add_action('woocommerce_order_partially_refunded', [self::class, 'partially_refunded']);
        add_action(self::SEND_HOOK, [self::class, 'send'], 10, 4);
    }

    /**
     * Runs for both classic and block checkout.
     */
    public static function remember_rate_id($item, $package_key): void
    {
        try {
            if (! Margin_Sync::is_enabled() || ! $item instanceof \WC_Order_Item_Shipping || ! WC()->session) {
                return;
            }

            $chosen = WC()->session->get('chosen_shipping_methods');

            if (is_array($chosen) && isset($chosen[$package_key]) && is_scalar($chosen[$package_key])) {
                $item->add_meta_data(self::RATE_ID_META, (string) $chosen[$package_key], true);
            }
        } catch (\Throwable $e) {
            // Never break checkout.
        }
    }

    public static function classic_order_placed($order_id, $posted_data = [], $order = null): void
    {
        self::order_placed($order instanceof \WC_Order ? $order : wc_get_order($order_id));
    }

    public static function order_placed($order): void
    {
        try {
            if (! Margin_Sync::is_enabled() || ! $order instanceof \WC_Order || $order->get_meta(self::PLACED_META)) {
                return;
            }

            $occurred_at = self::now();
            $session_id = Client::currentSessionId();

            if ($session_id !== null) {
                $order->update_meta_data(self::SESSION_META, $session_id);
            }

            $order->update_meta_data(self::PLACED_META, $occurred_at);
            $order->save_meta_data();

            self::queue($order->get_id(), 'placed', $occurred_at);
        } catch (\Throwable $e) {
            // Never break checkout.
        }
    }

    public static function status_changed($order_id, $from, $to, $order = null): void
    {
        if (! isset(self::STATUS_EVENTS[$to]) || ! Margin_Sync::is_enabled()) {
            return;
        }

        try {
            self::queue((int) $order_id, self::STATUS_EVENTS[$to], self::now());
        } catch (\Throwable $e) {
            // A status change must never fail because of RuleHook.
        }
    }

    /**
     * A partial refund doesn't change the status, so the order's latest event
     * is sent again with the new `shipping_refunded`. Being later, it
     * replaces the one RuleHook has.
     */
    public static function partially_refunded($order_id): void
    {
        if (! Margin_Sync::is_enabled()) {
            return;
        }

        try {
            $order = wc_get_order($order_id);

            if (! $order instanceof \WC_Order) {
                return;
            }

            self::queue($order->get_id(), self::STATUS_EVENTS[$order->get_status()] ?? 'placed', self::now());
        } catch (\Throwable $e) {
            // A refund must never fail because of RuleHook.
        }
    }

    /**
     * The Action Scheduler job. The snapshot is built now rather than when
     * the event was queued, so a retry sends the order as it is.
     */
    public static function send($order_id, $status, $occurred_at, $attempt = 1): void
    {
        $api_key = (string) get_option(Constants::API_KEY_KEY);

        // The store may have opted out, or disconnected, since this was queued.
        if (! Margin_Sync::is_enabled() || $api_key === '') {
            return;
        }

        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order) {
            return;
        }

        try {
            $outcome = Outcome_Payload::build($order, (string) $status, (string) $occurred_at);
            $client = new Client(Constants::api_url(), $api_key);
            $response = $client->request('POST', 'v1/order-outcomes', [
                'store_domain' => wp_parse_url(home_url(), PHP_URL_HOST),
                'outcomes' => [$outcome],
            ]);

            if (is_array($response) && ($response['reason'] ?? null) === 'not_opted_in') {
                Margin_Sync::remember('no');
            }
        } catch (Exception $e) {
            self::retry_or_drop((int) $order_id, (string) $status, (string) $occurred_at, (int) $attempt, (int) $e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            self::log(sprintf('Order %d %s event not sent: %s', $order_id, $status, $e->getMessage()));
        }
    }

    /**
     * Network errors, timeouts, rate limits and server errors are retried.
     * Any other error (a rejected payload, a revoked key) won't fix itself.
     */
    private static function retry_or_drop(int $order_id, string $status, string $occurred_at, int $attempt, int $code, string $message): void
    {
        $retryable = $code === 0 || $code === 408 || $code === 429 || $code >= 500;
        $delay = self::RETRY_DELAYS[$attempt - 1] ?? null;

        if ($retryable && $delay !== null) {
            self::queue($order_id, $status, $occurred_at, $attempt + 1, $delay);

            return;
        }

        self::log(sprintf('Order %d %s event dropped after %d attempt(s): HTTP %d %s', $order_id, $status, $attempt, $code, $message));
    }

    private static function queue(int $order_id, string $status, string $occurred_at, int $attempt = 1, int $delay = 0): void
    {
        $args = [$order_id, $status, $occurred_at, $attempt];

        if ($delay > 0 && function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + $delay, self::SEND_HOOK, $args, self::GROUP);
        } elseif (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::SEND_HOOK, $args, self::GROUP);
        }
    }

    private static function now(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    private static function log(string $message): void
    {
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->info($message, ['source' => 'rulehook-order-outcomes']);
        }
    }
}
