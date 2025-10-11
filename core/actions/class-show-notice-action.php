<?php

namespace RuleHook\Core\Actions;

if ( ! defined( 'ABSPATH' ) ) exit;


class Show_Notice_Action extends Abstract_Action
{
    public function execute()
    {
        $type = $this->payload['style'] ?? 'info';
        $message = $this->payload['message'];

        wc_add_notice($message, $type);
    }
}
