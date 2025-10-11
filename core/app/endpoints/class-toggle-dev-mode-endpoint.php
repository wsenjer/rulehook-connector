<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Constants;

class Toggle_Dev_Mode_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        $devMode = get_option(Constants::DEV_MODE_KEY);

        update_option(Constants::DEV_MODE_KEY, ! $devMode);

        $this->ok();
    }

    public function action()
    {
        return 'rulehook_toggle_dev_mode';
    }
}
