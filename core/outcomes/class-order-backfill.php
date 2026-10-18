<?php

namespace RuleHook\Core\Outcomes;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Exception;
use RuleHook\Core\Constants;
use RuleHook\Core\Margin_Sync;

/**
 * "Import past orders" (margin spec §4.6), so a store that has just opted in
 * gets a shipping margin report without waiting a month.
 *
 * Orders from the last 395 days, the oldest RuleHook accepts, are sent as
 * `source: "backfill"`, which RuleHook never counts against the store's
 * monthly order limit: completed, refunded and cancelled ones with that
 * event, and processing and on-hold ones as placed, so their later status
 * change is sent live. Orders already sent live are skipped. Each batch of
 * up to 100 orders is its own Action Scheduler action, so a large store never
 * holds one request for long, and progress is kept in an option so the admin
 * page can show it.
 */
class Order_Backfill
{
    const HOOK = 'rulehook_backfill_order_outcomes';

    /** RuleHook rejects events more than 400 days old. */
    const DAYS = 395;

    const BATCH = 100;

    const STATE_KEY = 'rulehook_order_backfill';

    const STATUSES = ['completed', 'refunded', 'cancelled', 'processing', 'on-hold'];

    const RUNNING = 'running';

    const DONE = 'done';

    const STOPPED = 'stopped';

    const FAILED = 'failed';

    public static function register(): void
    {
        add_action(self::HOOK, [self::class, 'run_batch'], 10, 1);
    }

    /**
     * Starts an import, unless one is already running.
     *
     * @return array the import's state
     *
     * @throws \RuntimeException when the store can't send orders
     */
    public static function start(): array
    {
        if ((string) get_option(Constants::API_KEY_KEY) === '') {
            throw new \RuntimeException('Connect the store to RuleHook first.');
        }

        if (! Margin_Sync::is_enabled()) {
            throw new \RuntimeException('Turn on Shipping Margin for this store in RuleHook first.');
        }

        $state = self::state();

        if (($state['status'] ?? null) === self::RUNNING) {
            return $state;
        }

        $since = time() - self::DAYS * DAY_IN_SECONDS;

        $state = [
            'status' => self::RUNNING,
            'since' => $since,
            'total' => count(self::order_ids($since, -1)),
            'page' => 1,
            'done' => 0,
            'skipped' => 0,
            'sent' => 0,
            'started_at' => time(),
            'finished_at' => null,
            'error' => null,
        ];
        self::save($state);

        self::queue(1);

        return $state;
    }

    /**
     * The Action Scheduler job: one page of orders, then the next page is
     * queued. A failed send is retried with the same backoff as live events.
     */
    public static function run_batch($attempt = 1): void
    {
        $state = self::state();

        if (($state['status'] ?? null) !== self::RUNNING) {
            return;
        }

        $api_key = (string) get_option(Constants::API_KEY_KEY);

        // The store may have opted out, or disconnected, since it started.
        if (! Margin_Sync::is_enabled() || $api_key === '') {
            self::finish($state, self::STOPPED, 'Shipping Margin was turned off for this store, so the import stopped.');

            return;
        }

        $ids = self::order_ids((int) $state['since'], self::BATCH, (int) $state['page']);
        $outcomes = [];
        $skipped = 0;

        foreach ($ids as $id) {
            $order = wc_get_order($id);

            // Sent live already: RuleHook has it.
            if (! $order instanceof \WC_Order || $order->get_meta(Order_Outcomes::LAST_EVENT_META)) {
                $skipped++;

                continue;
            }

            foreach (self::events($order) as [$status, $occurred_at]) {
                $outcomes[] = Outcome_Payload::build($order, $status, $occurred_at);
            }
        }

        if ($outcomes !== []) {
            try {
                $response = (new Client(Constants::api_url(), $api_key, 30))->request('POST', 'v1/order-outcomes', [
                    'store_domain' => wp_parse_url(home_url(), PHP_URL_HOST),
                    'outcomes' => $outcomes,
                ]);
            } catch (Exception $e) {
                $delay = Order_Outcomes::RETRY_DELAYS[$attempt - 1] ?? null;
                $code = (int) $e->getCode();

                if (($code === 0 || $code === 408 || $code === 429 || $code >= 500) && $delay !== null) {
                    self::queue((int) $attempt + 1, $delay);

                    return;
                }

                self::finish($state, self::FAILED, sprintf('RuleHook didn\'t accept the orders (HTTP %d): %s', $code, $e->getMessage()));

                return;
            }

            if (is_array($response) && ($response['reason'] ?? null) === 'not_opted_in') {
                Margin_Sync::remember('no');
                self::finish($state, self::STOPPED, 'Shipping Margin was turned off for this store, so the import stopped.');

                return;
            }
        }

        $state['done'] += count($ids) - $skipped;
        $state['skipped'] += $skipped;
        $state['sent'] += count($outcomes);
        $state['page']++;

        if (count($ids) < self::BATCH) {
            self::finish($state, self::DONE);

            return;
        }

        self::save($state);
        self::queue(1);
    }

    /**
     * @return array|null the latest import's state, for the admin page
     */
    public static function state(): ?array
    {
        $state = get_option(self::STATE_KEY);

        return is_array($state) ? $state : null;
    }

    /**
     * The order's events, as it stands now. A refund after completion sends
     * both, so RuleHook counts it as a completed order with shipping refunded
     * rather than one refunded before it was completed.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function events(\WC_Order $order): array
    {
        $iso = fn ($date) => $date instanceof \WC_DateTime ? gmdate('Y-m-d\TH:i:s\Z', $date->getTimestamp()) : null;
        $created = $iso($order->get_date_created()) ?? gmdate('Y-m-d\TH:i:s\Z');
        $modified = max($iso($order->get_date_modified()) ?? $created, $created);
        $completed = $iso($order->get_date_completed());
        $completed = $completed === null ? null : max($completed, $created);

        switch ($order->get_status()) {
            case 'completed':
                return [['completed', $completed ?? $modified]];
            case 'refunded':
                // A second later at least, so the refund is the latest event
                $refunded = $completed !== null && $modified <= $completed ? gmdate('Y-m-d\TH:i:s\Z', strtotime($completed) + 1) : $modified;

                return $completed === null ? [['refunded', $refunded]] : [['completed', $completed], ['refunded', $refunded]];
            case 'cancelled':
                return [['cancelled', $modified]];
            case 'processing':
            case 'on-hold':
                return [['placed', $created]];
            default:
                return [];
        }
    }

    /**
     * @return array<int, int>
     */
    private static function order_ids(int $since, int $limit, int $page = 1): array
    {
        return wc_get_orders([
            'type' => 'shop_order',
            'status' => array_map(fn ($status) => 'wc-'.$status, self::STATUSES),
            'date_created' => '>'.$since,
            'orderby' => 'ID',
            'order' => 'ASC',
            'limit' => $limit,
            'paged' => $page,
            'return' => 'ids',
        ]);
    }

    private static function finish(array $state, string $status, ?string $error = null): void
    {
        $state['status'] = $status;
        $state['error'] = $error;
        $state['finished_at'] = time();
        self::save($state);

        if ($error !== null && function_exists('wc_get_logger')) {
            wc_get_logger()->info('Order import '.$status.': '.$error, ['source' => 'rulehook-order-outcomes']);
        }
    }

    private static function save(array $state): void
    {
        update_option(self::STATE_KEY, $state, false);
    }

    private static function queue(int $attempt, int $delay = 0): void
    {
        if ($delay > 0 && function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + $delay, self::HOOK, [$attempt], Order_Outcomes::GROUP);
        } elseif (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::HOOK, [$attempt], Order_Outcomes::GROUP);
        }
    }
}
