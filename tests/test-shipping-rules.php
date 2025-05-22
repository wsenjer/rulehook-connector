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
class WP_Shipping_Rules_Test extends WP_UnitTestCase {

	public function test_weight_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 1,
					],
					'price' => 5,
				]
			]
		];

		$product = [ 'weight' =>  0.5];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_cart_total_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'cart_total',
					'value' => [
						'price_from' => 10,
						'price_to' => 20,
					],
					'price' => 5,
				]
			]
		];


		$product = [ 'price' =>  15];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_quantity_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'quantity',
					'value' => [
						'quantity_from' => 10,
						'quantity_to' => 20,
					],
					'price' => 5,
				]
			]
		];


		$product = [ 'price' =>  15];

		$rate = $this->calculate($settings_args, $product, ['quantity' => 12]);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_total_dimensions_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'total_dimensions',
					'value' => [
						'dimensions_from' => 50,
						'dimensions_to' => 70,
					],
					'price' => 5,
				]
			]
		];


		$product = [ 'length' =>  15, 'width' => 15, 'height' => 25];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_volume_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'volume',
					'value' => [
						'volume_from' => 5000,
						'volume_to' => 6000,
					],
					'price' => 5,
				]
			]
		];


		$product = [ 'length' =>  15, 'width' => 15, 'height' => 25];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_shipping_class_rule() {

		$shipping_class = wp_insert_term( Tests_Helper::randomString(), 'product_shipping_class' );
		$settings_args = [
			'rules' => [
				[
					'type' => 'shipping_class',
					'value' => [
						'shipping_class' => $shipping_class['term_id'],
					],
					'price' => 5,
				]
			]
		];

		$product = [ 'shipping_classes' => [$shipping_class['term_id']]];
		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_product_category_rule() {

		$product_category = wp_insert_term( Tests_Helper::randomString(), 'product_cat' );
		$settings_args = [
			'rules' => [
				[
					'type' => 'product_category',
					'value' => [
						'category' => $product_category['term_id'],
					],
					'price' => 5,
				]
			]
		];

		$product = [ 'categories' => [$product_category['term_id']]];
		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_product_tag_rule() {

		$product_tag = wp_insert_term( Tests_Helper::randomString(), 'product_tag' );
		$settings_args = [
			'rules' => [
				[
					'type' => 'product_tag',
					'value' => [
						'tag' => $product_tag['term_id'],
					],
					'price' => 5,
				]
			]
		];

		$product = [ 'tags' => [$product_tag['term_id']]];
		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_user_role_rule() {

		$user_id = wp_insert_user([
			'user_pass' => Tests_Helper::randomString(15),
			'user_login' => Tests_Helper::randomString(7),
			'user_email' => sprintf('%s@example.com', Tests_Helper::randomString(7)),
			'role' => 'customer'
		]);

		$this->actAs($user_id);

		$settings_args = [
			'rules' => [
				[
					'type' => 'user_role',
					'value' => [
						'role' => 'customer',
					],
					'price' => 5,
				]
			]
		];

		$rate = $this->calculate($settings_args, [], ['customer_id' => $user_id]);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_time_of_day_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'time_of_day',
					'value' => [
						'time_from' => date('H:i', strtotime('-1 hour')),
						'time_to' => date('H:i', strtotime('+1 hour')),
					],
					'price' => 5,
				]
			]
		];

		$rate = $this->calculate($settings_args, []);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_day_of_week_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'day_of_week',
					'value' => [
						'day' => intval(date('N')),
					],
					'price' => 5,
				]
			]
		];

		$rate = $this->calculate($settings_args, []);

		$settings_args = [
			'rules' => [
				[
					'type' => 'day_of_week',
					'value' => [
						'day' => date('N', strtotime('+1 day')),
					],
					'price' => 5,
				]
			]
		];
		$rate = $this->calculate($settings_args, []);

		$this->assertEquals($rate->getCost(), -1);
	}

	public function test_product_rule() {

		$product = Tests_Helper::add_product();
		$settings_args = [
			'rules' => [
				[
					'type' => 'product',
					'value' => [
						[
							'code' => $product->get_id(),
							'label' => '',
						]
					],
					'price' => 5,
				]
			]
		];

		$rate = $this->calculate($settings_args, ['id' => $product->get_id()]);

		$this->assertEquals($rate->getCost(), 5);
	}

	public function test_coupon_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'coupon',
					'value' => [
						'coupon' => 'TENPERCENTOFF',
					],
					'price' => 5,
				]
			]
		];

		$rate = $this->calculate($settings_args, [], ['applied_coupons' => ['TENPERCENTOFF']]);

		$this->assertEquals($rate->getCost(), 5);
	}


	public function test_cheapest_calculation_method_rule() {

		$settings_args = [
			'calculation_method' => 'cheapest',
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 999,
					],
					'price' => 50,
				],
				[
					'type' => 'cart_total',
					'value' => [
						'price_from' => 0,
						'price_to' => 999,
					],
					'price' => 40,
				]
			]
		];

		$product = [ 'weight' =>  0.5, 'price' =>  15];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 40);
	}

	public function test_most_expensive_calculation_method_rule() {

		$settings_args = [
			'calculation_method' => 'highest',
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 999,
					],
					'price' => 50,
				],
				[
					'type' => 'cart_total',
					'value' => [
						'price_from' => 0,
						'price_to' => 999,
					],
					'price' => 40,
				]
			]
		];

		$product = [ 'weight' =>  0.5, 'price' =>  15];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 50);
	}

	public function test_break_calculation_method_rule() {

		$settings_args = [
			'calculation_method' => 'break',
			'rules' => [
				[
					'type' => 'weight',
					'value' => [
						'weight_from' => 0,
						'weight_to' => 999,
					],
					'price' => 50,
				],
				[
					'type' => 'cart_total',
					'value' => [
						'price_from' => 0,
						'price_to' => 999,
					],
					'price' => 40,
				]
			]
		];

		$product = [ 'weight' =>  0.5, 'price' =>  15];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), 50);
	}

	public function test_payment_method_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'payment_method',
					'value' => [
						'payment_method' => 'bacs',
					],
					'price' => 5,
				]
			]
		];

		WC()->session->set('chosen_payment_method', 'bacs');
		$product = [ 'weight' =>  0.5];


		WC()->session->set('chosen_payment_method', 'paypal');

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals($rate->getCost(), -1);
	}



	public function test_stock_status_rule() {

		$settings_args = [
			'rules' => [
				[
					'type' => 'product_stock_status',
					'value' => [
						'stock_status' => 'onbackorder',
					],
					'price' => 5,
				]
			]
		];

		$product = [ 'stock_status' =>  'onbackorder'];

		$rate = $this->calculate($settings_args, $product);

		$this->assertEquals(5, $rate->getCost());
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

	private function actAs(int $user_id)
	{
		wp_clear_auth_cookie();
		wp_set_current_user ( $user_id );
		wp_set_auth_cookie  ( $user_id );
	}

}
