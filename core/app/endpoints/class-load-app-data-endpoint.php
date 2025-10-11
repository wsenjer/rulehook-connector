<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


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
        $shippingClassesSynced = get_option(Constants::SHIPPING_CLASSES_SYNCED_KEY);
        $categoriesSynced = get_option(Constants::CATEGORIES_SYNCED_KEY);

        $shippingMethodSettings = get_option('woocommerce_rulehook_settings');

        return [
            'teamId' => get_option(Constants::TEAM_ID_KEY),
            'isConnected' => ! empty(get_option(Constants::API_KEY_KEY)),
            'storeId' => parse_url(home_url(), PHP_URL_HOST),
            'lastSyncTime' => $lastSyncedTime.' ago',
            'productsSynced' => $productsSynced !== false ? $productsSynced : 0,
            'shippingClassesSynced' => $shippingClassesSynced !== false ? $shippingClassesSynced : 0,
            'categoriesSynced' => $categoriesSynced !== false ? $categoriesSynced : 0,
            'devMode' => (bool) get_option(Constants::DEV_MODE_KEY),
            'shippingMethodEnabled' => $shippingMethodSettings['enabled'] === 'yes',
            'connectionUrl' => $this->getConnectionUrl(),
        ];

    }

    private function getConnectionUrl(): string
    {
        return Constants::RULE_HOOK_URL.'/connect?site_url='.urlencode(home_url()).'&site_name='.urlencode(get_bloginfo('name')).'&source=woocommerce';
    }

    public function action()
    {
        return 'rulehook_load_app_data';
    }
}
