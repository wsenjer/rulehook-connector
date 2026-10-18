<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


class Constants
{
    const UTIL_CURRENT_VERSION = '2.1.0';

    const API_URL = 'https://rulehook.com/api';

    const RULE_HOOK_URL = 'https://rulehook.com';

    const API_KEY_KEY = 'rulehook_api_key';

    const TEAM_ID_KEY = 'rulehook_team_id';

    const LAST_SYNC_TIME_KEY = 'rulehook_last_sync_time';

    const PRODUCTS_SYNCED_KEY = 'rulehook_products_synced';

    const SHIPPING_CLASSES_SYNCED_KEY = 'rulehook_shipping_classes_synced';

    const CATEGORIES_SYNCED_KEY = 'rulehook_categories_synced';

    const DEV_MODE_KEY = 'rulehook_dev_mode';

    /**
     * Transient caching the store's external-rates opt-in, as last reported
     * by RuleHook: ['status' => 'yes'|'no', 'at' => timestamp]. Missing
     * means unknown.
     */
    const EXTERNAL_RATES_ENABLED_KEY = 'rulehook_external_rates_enabled';

    /**
     * Transient caching the store's shipping margin opt-in
     * (`margin_sync_enabled`), in the same shape as
     * EXTERNAL_RATES_ENABLED_KEY.
     */
    const MARGIN_SYNC_ENABLED_KEY = 'rulehook_margin_sync_enabled';

    /**
     * Option holding the secret that order ids are hashed with before they
     * are sent. It never leaves the store.
     */
    const ORDER_REF_SALT_KEY = 'rulehook_order_ref_salt';

    /**
     * Seconds to wait for /evaluate when RuleHook handles every rate. On a
     * timeout the store's own rates are shown unchanged.
     */
    const EXTERNAL_RATES_TIMEOUT = 3;

    /**
     * The API base URL. Define RULEHOOK_API_URL in wp-config.php, or use the
     * rulehook_api_url filter, to point the connector at another server.
     */
    public static function api_url(): string
    {
        $url = defined('RULEHOOK_API_URL') ? (string) RULEHOOK_API_URL : self::API_URL;

        return (string) apply_filters('rulehook_api_url', $url);
    }
}
