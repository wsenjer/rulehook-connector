<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Constants;
use RuleHook\Core\External_Rates;
use RuleHook\Core\Margin_Sync;

class Disconnect_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        delete_option(Constants::TEAM_ID_KEY);
        delete_option(Constants::API_KEY_KEY);
        External_Rates::forget();
        Margin_Sync::forget();

        $this->ok();
    }

    public function action()
    {
        return 'rulehook_disconnect';
    }
}
