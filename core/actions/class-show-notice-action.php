<?php

namespace RuleHook\Core\Actions;

class Show_Notice_Action extends Abstract_Action
{
    public function execute()
    {
        $type = $this->payload['type'];
        $message = $this->payload['message'];

        wc_add_notice($message, $type);
    }
}
