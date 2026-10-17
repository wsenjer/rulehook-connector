<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Whether RuleHook should handle the store's own shipping rates (protocol 2).
 *
 * RuleHook reports the store's opt-in as `external_rates_enabled` on every
 * evaluate and sync response. Until it has said "yes", the connector behaves
 * exactly like 1.1.1, so updating the plugin changes nothing by itself.
 */
class External_Rates
{
    /**
     * Long enough to outlast the daily sync, which refreshes it for stores
     * whose RuleHook shipping method is off and so never call /evaluate.
     */
    const STATUS_TTL = 2 * DAY_IN_SECONDS;

    /**
     * The mode is read once per request, so a response that flips it can't
     * switch protocols halfway through one calculation.
     *
     * @var bool|null
     */
    private static $enabled_this_request = null;

    public static function is_enabled(): bool
    {
        if (self::$enabled_this_request === null) {
            self::$enabled_this_request = self::stored_status() === 'yes';
        }

        return self::$enabled_this_request;
    }

    /**
     * Caches the flag from a RuleHook response. A response without the flag
     * comes from a server where the feature is off, so it counts as "no".
     * The transient is only rewritten when the status changes or is an hour
     * old, so checkouts don't write to the database every time.
     */
    public static function remember_from_response($response): void
    {
        if (! is_array($response)) {
            return;
        }

        $status = (isset($response['external_rates_enabled']) && $response['external_rates_enabled'] === true) ? 'yes' : 'no';
        $stored = get_transient(Constants::EXTERNAL_RATES_ENABLED_KEY);
        $previous = self::stored_status();

        if ($previous === $status && is_array($stored) && (int) ($stored['at'] ?? 0) > time() - HOUR_IN_SECONDS) {
            return;
        }

        set_transient(Constants::EXTERNAL_RATES_ENABLED_KEY, ['status' => $status, 'at' => time()], self::STATUS_TTL);

        // Rates WooCommerce cached under the old protocol would otherwise be
        // served until the cart or address changes.
        if ($previous !== null && $previous !== $status && class_exists('\WC_Cache_Helper')) {
            \WC_Cache_Helper::get_transient_version('shipping', true);
        }
    }

    /**
     * @return string|null "yes", "no", or null when unknown
     */
    public static function stored_status(): ?string
    {
        $stored = get_transient(Constants::EXTERNAL_RATES_ENABLED_KEY);

        if (! is_array($stored) || ! in_array($stored['status'] ?? null, ['yes', 'no'], true)) {
            return null;
        }

        return $stored['status'];
    }

    public static function forget(): void
    {
        delete_transient(Constants::EXTERNAL_RATES_ENABLED_KEY);
        self::$enabled_this_request = null;
    }

    public static function timeout(): int
    {
        return max(1, (int) apply_filters('rulehook_external_rates_timeout', Constants::EXTERNAL_RATES_TIMEOUT));
    }
}
