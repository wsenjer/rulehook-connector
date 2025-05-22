<?php
namespace RuleHook\Core;


use RuleHook\Core\App\Endpoints\Endpoints_Factory;
use RuleHook\Core\App\Frontend\App_Page;

class Core {

	private static $_instance;

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
		if (!$this->is_woocommerce_active()) {
			return;
		}

		App_Page::get_instance();
		Endpoints_Factory::boot();
		$this->load_hooks();
	}

	private function load_hooks()
	{
		add_filter( 'woocommerce_shipping_methods', [$this, 'add_shipping_method'] );
		add_action( 'woocommerce_shipping_init', [$this, 'init_shipping_method'] );

	}

	public function add_shipping_method( $methods )
	{
		$methods['rulehook'] = Shipping_Method::class;
		return $methods;
	}


	public function init_shipping_method()
	{
		require_once plugin_dir_path( __FILE__ ) . 'class-shipping-method.php';
	}

	private function is_woocommerce_active() :bool
	{
		return $this->is_plugin_active('woocommerce/woocommerce.php');
	}

	private function is_plugin_active(string $slug) :bool
	{
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() )
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		return in_array( $slug, $active_plugins ) || array_key_exists( $slug, $active_plugins );
	}

}
