<?php

namespace RuleHook\Core;

use RuleHook\Core\App\Endpoints\Endpoints_Factory;
use RuleHook\Core\App\Frontend\App_Page;
use RuleHook\Core\Sync\Store_Sync_Service;

class Core
{
    private static $_instance;

    /**
     * @return self
     */
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public function __construct()
    {
        if (! $this->is_woocommerce_active()) {
            return;
        }

        App_Page::get_instance();
        Endpoints_Factory::boot();
        $this->load_hooks();
    }

    private function load_hooks()
    {
        add_filter('woocommerce_shipping_methods', [$this, 'add_shipping_method']);
        add_action('woocommerce_shipping_init', [$this, 'init_shipping_method']);

        // Add cron hook for syncing
        add_action('rulehook_sync_event', [$this, 'sync_store_data']);

        // Schedule the cron if not already scheduled
        if (! wp_next_scheduled('rulehook_sync_event')) {
            wp_schedule_event(time(), 'daily', 'rulehook_sync_event');
        }
    }

    // Add this method to core/class-core.php
    public function sync_store_data()
    {
        // Get the Store_Sync_Service instance and call sync
        $store_sync = Store_Sync_Service::get_instance();
        $result = $store_sync->sync();


    }

    public function add_shipping_method($methods)
    {
        $methods['rulehook'] = Shipping_Method::class;

        return $methods;
    }

    public function init_shipping_method()
    {
        require_once plugin_dir_path(__FILE__).'class-shipping-method.php';

    }

    private function is_woocommerce_active(): bool
    {
        return $this->is_plugin_active('woocommerce/woocommerce.php');
    }

    private function is_plugin_active(string $slug): bool
    {
        $active_plugins = (array) get_option('active_plugins', []);
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
        }

        return in_array($slug, $active_plugins) || array_key_exists($slug, $active_plugins);
    }
}
