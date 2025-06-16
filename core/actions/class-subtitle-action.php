<?php

namespace RuleHook\Core\Actions;

class Subtitle_Action extends Abstract_Action
{
    public function execute()
    {
        add_action('woocommerce_after_shipping_rate', [$this, 'add_subtitle']);
    }

    public function add_subtitle($method)
    {

        $meta_data = $method->get_meta_data();

        if (! isset($meta_data['actions'])) {
            return;
        }

        if (! in_array('add_subtitle', $meta_data['actions'])) {
            return;
        }

        $subtitle = $this->payload['subtitle'];

        echo "<br><small style='color: #9f9f9f;'>$subtitle</small>";
    }
}
