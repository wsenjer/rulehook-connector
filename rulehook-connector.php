<?php
/**
 * The plugin bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       RuleHook Connector
 * Plugin URI:        https://wpruby.com/plugin/woocommerce-rulehook-connector
 * Description:       The easiest way to add table rates shipping to your WooCommerce store.
 * Version:           2.0.6
 * Requires PHP:      7.1
 * Requires at least: 4.0
 * Tested up to:      6.8
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simple-table-rates
 * Domain Path:       /languages
 */

namespace RuleHook;

use RuleHook\Core\Core;

if ( ! defined( 'WPINC' ) ) {
	die;
}


class RuleHook_Connector {

	protected static $_instance = null;

	/**
	 * @return self
	 */
	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct()
	{
		Core::get_instance();
	}
}



require_once dirname(__FILE__ ) . '/includes/autoloader.php';

RuleHook_Connector::get_instance();
