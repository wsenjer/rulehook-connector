<?php

namespace RuleHook\Core\Actions;

if ( ! defined( 'ABSPATH' ) ) exit;


class Rename_Method_Action extends Abstract_Action
{
    public function execute()
    {
        add_filter('str_shipping_rate_label_'.$this->rule_id, [$this, 'rename_method'], 10, 2);
    }

    public function rename_method($label)
    {
        return $this->payload['title'];
    }
}
