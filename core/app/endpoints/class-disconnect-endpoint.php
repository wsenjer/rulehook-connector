<?php

namespace RuleHook\Core\App\Endpoints;

use RuleHook\Core\Constants;

class Disconnect_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        delete_option(Constants::TEAM_ID_KEY);
        delete_option(Constants::API_KEY_KEY);

        $this->ok();
    }

    public function action()
    {
        return 'rulehook_disconnect';
    }
}
