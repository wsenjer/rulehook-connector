<?php

namespace RuleHook\Core;


class Rate {

	private $id = '';

	private $cost = - 1.0;

	private $label = '';

	private $matchedRules = [];

	/**
	 * @param  $matchedRule
	 *
	 * @return Rate
	 */
	public function addMatchedRule(  $matchedRule ): Rate {
		$this->matchedRules[] = $matchedRule;

		return $this;
	}

	public function getWoocommerceRate(): array {
		$matchedRuleIds = array_map( function (  $rule ) {
			return $rule->getId();
		}, $this->matchedRules );

		return [
			'id'        => sprintf( 'simple_table_rates_%d', $this->getId() ),
			'label'     => $this->getLabel(),
			'cost'      => $this->getCost(),
			'meta_data' => [
				'matched_rules_ids' => $matchedRuleIds,
				'instance_id' => $this->getId()
			]
		];
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return Rate
	 */
	public function setId( string $id ): Rate {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		$label = $this->label;
		foreach ( $this->getMatchedRules() as $rule ) {
			$label = apply_filters( "str_shipping_rate_label_" . $rule->getId(), $label );
		}

		return $label;
	}

	/**
	 * @param string $label
	 *
	 * @return Rate
	 */
	public function setLabel( string $label ): Rate {
		$this->label = $label;

		return $this;
	}

	/**
	 * @return []
	 */
	public function getMatchedRules(): array {
		return $this->matchedRules;
	}

	/**
	 * @param [] $matchedRules
	 *
	 * @return Rate
	 */
	public function setMatchedRules( array $matchedRules ): Rate {
		$this->matchedRules = $matchedRules;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getCost(): float {
		return $this->cost;
	}

	/**
	 * @param float $cost
	 *
	 * @return Rate
	 */
	public function setCost( float $cost ) {
		$this->cost = $cost;

		return $this;
	}


}

