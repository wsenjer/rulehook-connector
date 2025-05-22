<?php
/**
 * Class SampleTest
 *
 * @package Woocommerce_Restricted_Shipping_And_Payment_Pro
 */

use WPRuby_Table_Rates\Core\Calculator;
use WPRuby_Table_Rates\Core\Rate;

/**
 * Sample test case.
 */
class WP_Rules_Actions_Test extends WP_UnitTestCase {

	public function test_rename_method_action()
	{
		$settings_args = [
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 1,
					],
					'price' => 5,
					'actions' => [
						[
							'action' => 'rename_method',
							'value' => ['title' => 'Changed Title']
						]
					]
				]
			]
		];

		$product = [ 'weight' =>  0.5];

		$rate = $this->calculate($settings_args, $product);

		foreach ($rate->getMatchedRules() as $matchedRule) {
			foreach ($matchedRule->getActions() as $action) {
				$action->execute();
			}
		}

		$this->assertEquals($rate->getCost(), 5);
		$this->assertEquals($rate->getLabel(), 'Changed Title');
	}


	public function test_cancel_action()
	{
		$settings_args = [
			'calculation_method' => 'sum',
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 1,
					],
					'price' => 5,
					'actions' => [
						[
							'action' => 'cancel',
							'value' => []
						]
					]
				]
			]
		];

		$product = [ 'weight' =>  0.5];

		$rate = $this->calculate($settings_args, $product);

		foreach ($rate->getMatchedRules() as $matchedRule) {
			foreach ($matchedRule->getActions() as $action) {
				$action->execute();
			}
		}

		$this->assertEquals($rate->getCost(), -1);
	}

	public function test_stop_action()
	{
		$settings_args = [
			'calculation_method' => 'sum',
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 100,
					],
					'price' => 5,
					'actions' => [
						[
							'action' => 'stop',
							'value' => []
						]
					]
				],
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 100,
					],
					'price' => 5,
					'actions' => []
				]
			]
		];

		$product = [ 'weight' =>  6];

		$rate = $this->calculate($settings_args, $product);

		foreach ($rate->getMatchedRules() as $matchedRule) {
			foreach ($matchedRule->getActions() as $action) {
				$action->execute();
			}
		}

		$this->assertEquals($rate->getCost(), 5);
	}


	private function get_package( $product_args = [], $package_args = [] ) :array
	{
		if (isset($product_args['id'])) {
			$product = new WC_Product($product_args['id']);
		} else {
			$product = Tests_Helper::add_product( $product_args );
		}
		return Tests_Helper::prepare_package( $product, $package_args );
	}

	private function calculate(array $settings_args, array $product, array $package_args = []): Rate
	{
		$settings = Tests_Helper::get_settings($settings_args);

		$calculator = new Calculator($settings);

		return $calculator->calculate($this->get_package($product, $package_args));
	}


}
