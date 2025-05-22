<?php

namespace RuleHook\Core\App\Endpoints;


class Endpoints_Factory {

	public static function boot()
	{
		foreach (self::available_endpoints() as $endpoint)
		{
			if (class_exists($endpoint)) {
				new $endpoint();
			}
		}
	}

	/**
	 * @param string $endpoint
	 *
	 * @return array|string
	 */
	public static function get_endpoints_nonce ($endpoint = '') {

		if ($endpoint !== '') {
			if (class_exists($endpoint)) {
				/** @var Abstract_Endpoint $endpoint */
				$endpointClass = new $endpoint();
				return wp_create_nonce( $endpointClass->action() );
			}
		}

		$nonce = [];
		foreach (self::available_endpoints() as $endpoint) {
			/** @var Abstract_Endpoint $endpoint */
			$endpointClass = new $endpoint();
			$nonce[$endpointClass->action()] = wp_create_nonce( $endpointClass->action() );
		}

		return $nonce;
	}

	/**
	 * @return string[]
	 */
	public static function available_endpoints()
	{
		return [
			Load_App_Data_Endpoint::class,
			Find_Product_Endpoint::class,
		];
	}

}
