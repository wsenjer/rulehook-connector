<?php

namespace RuleHook\Core;

use RuleHook\Core\Actions\Cancel_Action;
use RuleHook\Core\Actions\Stop_Action;


class Calculator {

	/** @var Settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}


	/**
	 * @param array $package
	 *
	 * @return Rate $package
	 */
	public function calculate( array $package ): Rate {
		$rate = new Rate();
		$rate->setId( $this->settings->getInstanceId() )->setLabel( $this->settings->getTitle() );

		$matchedRules = [];

		foreach ( $this->settings->getRules() as $rule ) {
			if ( ! $rule->match( $package ) ) {
				continue;
			}

			$shouldStop = array_filter($rule->getActions(), function ($action) { return $action instanceof Stop_Action; });
			$shouldCancel = array_filter($rule->getActions(), function ($action) { return $action instanceof Cancel_Action; });

			if (!empty($shouldCancel)) {
				return $rate->addMatchedRule($rule)->setCost(-1);
			}

			if (!empty($shouldStop) || $this->settings->getCalculationMethod() === 'break') {
					$rate->setCost( $rule->getPrice() + $this->get_handling_fees($rule->getPrice()) );
					$rate->addMatchedRule($rule);
					return $rate;
			}

			if ( in_array($this->settings->getCalculationMethod(), ['sum', 'cheapest', 'highest']) ) {
				$matchedRules[] = $rule;
			}
		}

		if ( count( $matchedRules ) === 0 ) {
			return $rate;
		}

		if ($this->settings->getCalculationMethod() === 'sum') {
			$costs = array_reduce( $matchedRules, function ( $carry, $matchedRule ) {
				return $carry + $matchedRule->getPrice();
			}, 0 );
			$rate->setMatchedRules($matchedRules);
			$rate->setCost( $costs + $this->get_handling_fees($costs) );
			return $rate;
		}


		$matchedRule = null;

		if ($this->settings->getCalculationMethod() === 'cheapest') {
			$matchedRule = array_reduce($matchedRules, function($result, $rule) {
				if($result->getPrice() < $rule->getPrice()) {
					return $result;
				}
				return $rule;
			},$matchedRules[0]);
		}

		if ($this->settings->getCalculationMethod() === 'highest') {
			$matchedRule = array_reduce($matchedRules, function($result, $rule) {
				if($result->getPrice() > $rule->getPrice()) {
					return $result;
				}
				return $rule;
			},$matchedRules[0]);
		}

		$rate->addMatchedRule($matchedRule);
		$rate->setCost( $matchedRule->getPrice() + $this->get_handling_fees($matchedRule->getPrice()) );

		return $rate;
	}


	private function get_handling_fees(float $cost): float
	{
		if ($this->settings->getHandlingFees() === 0) {
			return 0;
		}

		if ($this->settings->getHandlingFeesType() === 'amount') {
			return round(floatval($this->settings->getHandlingFees()), 2);
		}

		return round($cost * ($this->settings->getHandlingFees() / 100), 2);
	}

}
