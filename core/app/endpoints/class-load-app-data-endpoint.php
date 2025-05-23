<?php

namespace RuleHook\Core\App\Endpoints;

use RuleHook\Core\Constants;

class Load_App_Data_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        $lastSyncedTime = get_option(Constants::LAST_SYNC_TIME_KEY) ?? 'Never';
        if ($lastSyncedTime !== 'Never') {
            $lastSyncedTime = human_time_diff(strtotime($lastSyncedTime));
        }
        $productsSynced = get_option(Constants::PRODUCTS_SYNCED_KEY);
        $shippingZonesSynced = get_option(Constants::SHIPPING_ZONES_SYNCED_KEY);

        $shippingMethodSettings = get_option('woocommerce_rulehook_settings');

        return [
            'teamId' => get_option(Constants::TEAM_ID_KEY),
            'isConnected' => ! empty(get_option(Constants::API_KEY_KEY)),
            'storeId' => parse_url(home_url(), PHP_URL_HOST),
            'lastSyncTime' => $lastSyncedTime.' ago',
            'currency' => get_woocommerce_currency(),
            'productsSynced' => $productsSynced !== false ? $productsSynced : 0,
            'shippingZonesSynced' => $shippingZonesSynced !== false ? $shippingZonesSynced : 0,
            'devMode' => (bool) get_option(Constants::DEV_MODE_KEY),
            'methodTitle' => isset($shippingMethodSettings['title']) ? $shippingMethodSettings['title'] : '',
            'shippingMethodEnabled' => $shippingMethodSettings['enabled'] === 'yes',
        ];

    }

    public function action()
    {
        return 'rulehook_load_app_data';
    }
}
