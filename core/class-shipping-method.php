<?php

namespace RuleHook\Core;


class Shipping_Method extends \WC_Shipping_Method {

	private $handling_fees = [
		'type'  => 'amount',
		'value' => 0
	];

	private $debug_messages = [];

    private $calculation_method = 'sum';
    private $rules = [];
    private $hide_other_methods = 'no';
    private $debug_mode = 'no';


	public function __construct( $instance_id = 0 ) {
		$this->instance_id  = absint( $instance_id );
		$this->id           = 'simple_table_rates';
		$this->method_title = __( 'RuleHook Connector', 'simple-table-rates' );
		$this->method_description = __( 'Set shipping prices based on many rules such as weight, price, dimensions, user roles and much more.', 'simple-table-rates' );
		$this->title        = __( 'RuleHook Connector', 'simple-table-rates' );

		$this->supports = [
			'shipping-zones',
			'instance-settings',
		];

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled            = $this->get_option( 'enabled' );
		$this->handling_fees      = $this->get_option( 'handling_fees' );
		$this->calculation_method = $this->get_option( 'calculation_method' );
		$this->rules              = $this->get_option( 'rules' );
		$this->hide_other_methods = $this->get_option( 'hide_other_methods' );
		$this->debug_mode         = $this->get_option( 'debug_mode' );


		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
		add_filter( 'woocommerce_package_rates', [ $this, 'hide_shipping_when_table_rate_is_available' ], 10, 2 );

	}

	public function init_form_fields() {
		$this->instance_form_fields = [
			'title'         => [
				'title'       => __( 'Method Title', 'simple-table-rates' ),
				'type'        => 'text',
				'description' => __( 'Title of the shipping method at the cart and checkout pages.', 'simple-table-rates' ),
				'default'     => __( 'Shipping', 'simple-table-rates' ),
				'desc_tip'    => false,
			],
			'handling_fees' => [
				'type'    => 'handling_fees',
				'default' => $this->handling_fees,
			],
			'debug_mode'    => [
				'title'       => __( 'Enable Debug Mode', 'simple-table-rates' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable ', 'simple-table-rates' ),
				'description' => __( 'If debug mode is enabled, the shipping method will be activated just for the administrator. The debug mode will display all the debugging data on the cart and the checkout pages.', 'simple-table-rates' ),
				'desc_tip'    => true,
			],

			'calculation_method' => [
				'title'       => __( 'Calculation Method', 'simple-table-rates' ),
				'type'        => 'select',
				'options'     => [
					'sum'      => 'Sum',
					'break'    => 'Break on first match',
					'cheapest' => 'Cheapest',
					'highest'  => 'Most Expensive',
				],
				'default'     => 'sum',
				'description' => $this->calculation_method_description(),
				'desc_tip'    => false,
			],
			'hide_other_methods' => [
				'title'       => __( 'Hide other shipping methods', 'simple-table-rates' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable ', 'simple-table-rates' ),
				'default'     => 'no',
				'description' => __( 'If enabled, the plugin will hide other shipping methods if a table rate is available. You can override this option for each rule by using rule actions.', 'simple-table-rates' ),
				'desc_tip'    => false,
			],

			'rules' => [
				'type'    => 'rules',
				'default' => [],
			],
		];
	}

	private function calculation_method_description() {
		ob_start();
		?>
        <ul>
            <li><strong>Sum</strong>: the plugin will add the price of shipping for all of the matching rules.</li>
            <li><strong>Break on first match</strong>: The plugin will stop the calculations on the first matching rule.</li>
            <li><strong>Cheapest</strong>: The plugin will choose the lowest price from the matched rules.</li>
            <li><strong>Most Expensive</strong>: The plugin will choose the highest price from the matched rules.</li>
        </ul>
		<?php
		return ob_get_clean();
	}

	public function calculate_shipping( $package = [] ) {

		$settings = new Settings( $this->instance_id, $this->instance_settings );

		$this->debug( 'Settings', $this->instance_settings );

		$calculator = new Calculator( $settings );
		$rate       = $calculator->calculate( $package );

		if ( $rate->getCost() === - 1.0 ) {
			$this->debug( "No Rule Matched" );

			return;
		}

		foreach ( $rate->getMatchedRules() as $matchedRule ) {
			$this->debug( 'Matched Rule', $matchedRule->getId() );
			foreach ( $matchedRule->getActions() as $key => $action ) {
				$this->debug( "Executed Action ($key)", [ 'type' => get_class( $action ), 'payload' => $action->payload ] );
				$action->execute();
			}
		}

		$this->debug( 'Final Rate', $rate->getWoocommerceRate() );
		$this->output_debug();

		$this->add_rate( $rate->getWooCommerceRate() );

	}

	/**
	 * Output a message
	 *
	 * @param string $title
	 * @param mixed $data
	 * @param string $type
	 */
	protected function debug( $title, $data = null ) {
		if ( $this->debug_mode !== 'yes' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $data ) {
			$message = $title;
		} else {
			$message = sprintf( '%s: <pre>%s</pre>', $title, json_encode( $data ) );
		}
		$this->debug_messages[ $title ] = $message;

	}

	protected function output_debug() {

		if ( count( $this->debug_messages ) === 0 ) {
			return;
		}

		$message = implode( '', $this->debug_messages );

		$message = 'Debug Info for RuleHook Connector <br><br>' . $message;

		wc_add_notice( $message, 'notice' );
	}

	public function generate_rules_html() {
		$rules = array_map( function ( $rule ) {
			if ( ! isset( $rule['actions'] ) ) {
				$rule['actions'] = [];
			}

			return $rule;
		}, $this->instance_settings['rules'] );
		// @todo
		wp_localize_script( 'wpruby-str-app', 'str_rules', $rules );
		ob_start();
		require_once( dirname( __FILE__ ) . '/app/frontend/views/app.php' );

		return ob_get_clean();
	}

	/**
	 * validate_rules_field function.
	 *
	 * @access public
	 * @return array
	 * @internal param mixed $key
	 */
	public function validate_rules_field() {
		$posted_rules = [];
		if ( isset( $_POST['str_rule'] ) ) {
			$posted_rules = $_POST['str_rule'];
		}
		$rules = [];
		foreach ( $posted_rules as $key => $rule ) {
			if ( $rule['type'] === 'product' && !empty($rule['value']) ) {
				$rule['value'] = array_map( function ( $val ) {
					return json_decode( str_replace( '\\', '', $val ), true );
				}, $rule['value'] );
			}


			if ( ! isset( $rule['value'] ) ) {
				continue;
			}

			if ( empty( $rule['value'] ) ) {
				continue;
			}

			if ( empty( $rule['price'] ) ) {
				$rule['price'] = 0;
			}

			$rule['actions'] = json_decode( str_replace( '\\', '', $rule['actions'] ), true );

			$rules[ $key ] = $rule;
		}
		return $rules;
	}

	/**
	 * @return string
	 */
	public function generate_handling_fees_html() {
		$value = ( isset( $this->instance_settings['handling_fees']['value'] ) ) ? $this->instance_settings['handling_fees']['value'] : 0;
		$type  = ( isset( $this->instance_settings['handling_fees']['type'] ) ) ? $this->instance_settings['handling_fees']['type'] : 'amount';
		ob_start(); ?>
        <tr style="vertical-align: top;">
            <th class="titledesc">
                <label><?php _e( 'Handling Fees', 'simple-table-rates' ) ?></label>
            </th>
            <td class="forminp">
                <fieldset id="str_handling_fees">
                    <input type="text" id="str_handling_fees_value" name="str_handling_fees_value" value="<?php echo esc_attr( $value ); ?>" style="width:70px"/>

                    <select id="str_handling_fees_type" name="str_handling_fees_type" style="width:50px; line-height: 28px;">
                        <option value="amount" <?php selected( $type, 'amount' ) ?>><?php echo get_woocommerce_currency_symbol(); ?></option>
                        <option value="percent" <?php selected( $type, 'percent' ) ?>>%</option>
                    </select>
                </fieldset>
            </td>
        </tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate_handling_fees_field function.
	 *
	 * @access public
	 * @return array
	 * @internal param mixed $key
	 */
	public function validate_handling_fees_field() {
		$handling_fees = [];
		if ( isset( $_POST['str_handling_fees_value'] ) ) {
			$handling_fees['value'] = round( floatval( $_POST['str_handling_fees_value'] ), 2 );
		}
		if ( isset( $_POST['str_handling_fees_type'] ) ) {
			$handling_fees['type'] = $_POST['str_handling_fees_type'];
		}

		return $handling_fees;
	}

	/**
	 * Hide shipping rates when free shipping is available
	 *
	 * @param array $rates Array of rates found for the package
	 * @param array $package The package array/object being shipped
	 *
	 * @return array of modified rates
	 */
	public function hide_shipping_when_table_rate_is_available( $rates, $package ) {
		if ( $this->hide_other_methods !== 'yes' ) {
			return $rates;
		}

		if ( ! $this->is_available( $package ) ) {
			return $rates;
		}

		$new_rates = Helper::filter_table_rates( $rates );

		if ( count( $new_rates ) > 0 ) {
			return $new_rates;
		}

		return $rates;
	}

	public function is_available( $package ) {
		if ( $this->debug_mode === 'yes' && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}

	public function admin_options() {
		require_once( dirname( __FILE__ ) . '/views/admin-options.php' );
	}
}
