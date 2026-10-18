<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Whether the store has opted in to shipping margin, so order outcomes may be
 * sent to RuleHook.
 *
 * RuleHook reports the opt-in as `margin_sync_enabled` on evaluate and sync
 * responses, the same way as `external_rates_enabled`. Until it has said
 * "yes", nothing about orders is sent or stored.
 */
class Margin_Sync
{
    /**
     * Long enough to outlast the daily sync, which refreshes it for stores
     * whose checkouts don't call /evaluate.
     */
    const STATUS_TTL = 2 * DAY_IN_SECONDS;

    public static function is_enabled(): bool
    {
        return self::stored_status() === 'yes';
    }

    /**
     * Caches the flag from a RuleHook response. A response without the flag
     * comes from a server where the feature is off, so it counts as "no".
     */
    public static function remember_from_response($response): void
    {
        if (! is_array($response)) {
            return;
        }

        self::remember((isset($response['margin_sync_enabled']) && $response['margin_sync_enabled'] === true) ? 'yes' : 'no');
    }

    /**
     * The transient is only rewritten when the status changes or is an hour
     * old, so checkouts don't write to the database every time.
     *
     * @param  string  $status  "yes" or "no"
     */
    public static function remember(string $status): void
    {
        $stored = get_transient(Constants::MARGIN_SYNC_ENABLED_KEY);

        if (self::stored_status() === $status && is_array($stored) && (int) ($stored['at'] ?? 0) > time() - HOUR_IN_SECONDS) {
            return;
        }

        set_transient(Constants::MARGIN_SYNC_ENABLED_KEY, ['status' => $status, 'at' => time()], self::STATUS_TTL);
    }

    /**
     * @return string|null "yes", "no", or null when unknown
     */
    public static function stored_status(): ?string
    {
        $stored = get_transient(Constants::MARGIN_SYNC_ENABLED_KEY);

        if (! is_array($stored) || ! in_array($stored['status'] ?? null, ['yes', 'no'], true)) {
            return null;
        }

        return $stored['status'];
    }

    public static function forget(): void
    {
        delete_transient(Constants::MARGIN_SYNC_ENABLED_KEY);
    }
}
