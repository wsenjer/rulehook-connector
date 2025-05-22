<?php

namespace RuleHook\Core\App\Endpoints;

use RuleHook\Core\App\Endpoints\Services\Categories;
use RuleHook\Core\App\Endpoints\Services\Users;

class Load_App_Data_Endpoint extends Abstract_Endpoint {

	public function callback( $data ) {
		$payload = [];

		$categories_service = new Categories();

		$payload['product_categories'] = $categories_service->get_product_categories();
		$payload['product_tags']       = $categories_service->get_product_tags();
		$payload['product_attributes']  = $categories_service->get_product_attributes();
		$payload['product_attributes_terms'] = $categories_service->get_product_attributes_terms($payload['product_attributes']);
		$payload['product_stock_status'] = $categories_service->get_product_stock_status();
		$payload['shipping_classes']   = $categories_service->get_shipping_classes();
		$payload['coupons']            = $categories_service->get_coupons();
		$payload['user_roles']         = ( new Users() )->get_roles();
		$payload['payment_methods']    = $this->get_payment_methods();


		return $payload;
	}

	private function get_payment_methods(): array {
		$gateways = WC()->payment_gateways->payment_gateways();
		unset( $gateways['pre_install_woocommerce_payments_promotion'] );

		return array_map( function ( $gateway ) {
			return [ 'id' => $gateway->id, 'title' => strip_tags( $gateway->title ) ];
		}, $gateways );
	}

	public function action() {
		return 'str_load_app_data';
	}
}
