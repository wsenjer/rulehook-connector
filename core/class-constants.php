<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


class Constants
{
    const UTIL_CURRENT_VERSION = '1.0.1';

    const API_URL = 'https://app.rulehook.com/api';

    const RULE_HOOK_URL = 'https://app.rulehook.com';

    const API_KEY_KEY = 'rulehook_api_key';

    const TEAM_ID_KEY = 'rulehook_team_id';

    const LAST_SYNC_TIME_KEY = 'rulehook_last_sync_time';

    const PRODUCTS_SYNCED_KEY = 'rulehook_products_synced';

    const SHIPPING_CLASSES_SYNCED_KEY = 'rulehook_shipping_classes_synced';

    const CATEGORIES_SYNCED_KEY = 'rulehook_categories_synced';

    const DEV_MODE_KEY = 'rulehook_dev_mode';
}
