<?php

namespace RuleHook\Core\Actions;

abstract class Abstract_Action
{
    public $rule_id = -1;

    public $payload = [];

    abstract public function execute();
}
