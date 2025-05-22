<?php

namespace RuleHook\Core\Actions;

class Subtitle_Action extends Abstract_Action {

	public function execute()
	{
		add_action('woocommerce_after_shipping_rate', [$this, 'add_subtitle']);
	}

	public function add_subtitle($method)
	{
		$instance_id = $method->get_instance_id();
		$meta_data = $method->get_meta_data();

		if (!isset($meta_data['instance_id'])) {
			return;
		}

		if (intval($meta_data['instance_id']) !== intval($instance_id)) {
			return;
		}

		if (!isset($meta_data['matched_rules_ids'])) {
			return;
		}

		if (!in_array($this->rule_id, $meta_data['matched_rules_ids'])) {
			return;
		}

		$subtitle = $this->payload['subtitle'];

		echo "<br><span style='color: #ccc;'>$subtitle</span>";
	}

}
