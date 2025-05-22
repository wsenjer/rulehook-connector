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
        if (! isset($_GET['instance_id'])) {
            return;
        }

        wp_enqueue_script('wpruby-str-app', plugin_dir_url(__FILE__).'app/dist/app.js', [], Constants::UTIL_CURRENT_VERSION, true);

        wp_localize_script('wpruby-str-app', 'str_app',
            [
                'nonces' => Endpoints_Factory::get_endpoints_nonce(),
                'store_currency_symbol' => get_woocommerce_currency_symbol(),
            ]
        );

        wp_enqueue_style('wpruby-str-css', plugin_dir_url(__FILE__).'app/dist/css/app.css', [], Constants::UTIL_CURRENT_VERSION);

    }
}
