<?php

namespace RuleHook\Core;


class Settings {

	private $instance_id = 0;

	private $title = '';

	private $handling_fees = 0;

	private $handling_fees_type = 'amount';

	private $calculation_method = 'sum';

	private $debug_mode = false;

	private $hide_other_methods = false;

	private $rules = [];

	public function __construct(int $instance_id, array $settings )
	{
		$this->setDebugMode( $settings['debug_mode'] === 'yes' )
		     ->setCalculationMethod( $settings['calculation_method'] )
		     ->setHandlingFees( round( floatval( $settings['handling_fees']['value'] ), 2 ) )
		     ->setHandlingFeesType( $settings['handling_fees']['type'] )
		     ->setTitle( $settings['title'] )
			 ->setHideOtherMethods($settings['hide_other_methods'] === 'yes')
			 ->setInstanceId($instance_id);

		foreach ( $settings['rules'] as $key => $settings_rule ) {



		}

	}

	/**
	 *
	 * @return Settings
	 */
	public function addRule(  $rules ): Settings {
		$this->rules[] = $rules;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @param string $title
	 *
	 * @return Settings
	 */
	public function setTitle( string $title ): Settings {
		$this->title = $title;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getHandlingFees(): float {
		return $this->handling_fees;
	}

	/**
	 * @param float $handling_fees
	 *
	 * @return Settings
	 */
	public function setHandlingFees( float $handling_fees ): Settings {
		$this->handling_fees = $handling_fees;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getInstanceId(): int {
		return $this->instance_id;
	}

	/**
	 * @param int $instance_id
	 *
	 * @return Settings
	 */
	public function setInstanceId( int $instance_id ): Settings {
		$this->instance_id = $instance_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHandlingFeesType(): string {
		return $this->handling_fees_type;
	}

	/**
	 * @param string $handling_fees_type
	 *
	 * @return Settings
	 */
	public function setHandlingFeesType( string $handling_fees_type ): Settings {
		$this->handling_fees_type = $handling_fees_type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCalculationMethod(): string {
		return $this->calculation_method;
	}

	/**
	 * @param string $calculation_method
	 *
	 * @return Settings
	 */
	public function setCalculationMethod( string $calculation_method ): Settings {
		$this->calculation_method = $calculation_method;

		return $this;
	}

	/**
	 */
	public function getRules(): array {
		return $this->rules;
	}

	/**
	 *
	 * @return Settings
	 */
	public function setRules( array $rules ): Settings {
		$this->rules = $rules;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isDebugMode(): bool {
		return $this->debug_mode;
	}

	/**
	 * @param bool $debug_mode
	 *
	 * @return Settings
	 */
	public function setDebugMode( bool $debug_mode ): Settings {
		$this->debug_mode = $debug_mode;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isHideOtherMethods(): bool {
		return $this->hide_other_methods;
	}

	/**
	 * @param bool $hide_other_methods
	 *
	 * @return Settings
	 */
	public function setHideOtherMethods( bool $hide_other_methods ): Settings {
		$this->hide_other_methods = $hide_other_methods;

		return $this;
	}



}
