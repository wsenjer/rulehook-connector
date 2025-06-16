<?php

namespace RuleHook\Core\App\Frontend;

use RuleHook\Core\App\Endpoints\Endpoints_Factory;
use RuleHook\Core\Constants;

class App_Page
{
    protected static $_instance = null;

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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook)
    {
        if (! isset($_GET['page']) || $_GET['page'] !== 'wc-settings') {
            return;
        }
        if (! isset($_GET['tab']) || $_GET['tab'] !== 'shipping') {
            return;
        }
        if (! (isset($_GET['section']) && $_GET['section'] === 'rulehook')) {
            return;
        }

        wp_enqueue_script('rulehook-app', plugin_dir_url(__FILE__).'app/dist/index.js', [], Constants::UTIL_CURRENT_VERSION, true);

        wp_localize_script('rulehook-app', 'rulehook',
            [
                'nonces' => Endpoints_Factory::get_endpoints_nonce(),
                'store_currency_symbol' => get_woocommerce_currency_symbol(),
                'plugin_uri' => plugin_dir_url(__FILE__),
            ]
        );

        wp_enqueue_style('rulehook-css', plugin_dir_url(__FILE__).'app/dist/style.css', [], Constants::UTIL_CURRENT_VERSION);

    }
}
