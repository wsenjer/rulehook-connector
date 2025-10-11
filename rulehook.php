<?php

/**
 * The plugin bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       RuleHook Connector
 * Plugin URI:        https://rulehook.com
 * Description:       The easiest way to add table rates shipping to your WooCommerce store.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 5.0
 * Tested up to:      6.8
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rulehook
 * Domain Path:       /languages
 */

namespace RuleHook;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Core;



class RuleHook_Connector
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
        Core::get_instance();
    }
}

require_once dirname(__FILE__).'/includes/autoloader.php';

RuleHook_Connector::get_instance();
