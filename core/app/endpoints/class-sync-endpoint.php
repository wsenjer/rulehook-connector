<?php

namespace RuleHook\Core\App\Endpoints;

use RuleHook\Core\Constants;
use RuleHook\Core\Sync\Store_Sync_Service;

class Sync_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        $api_key = get_option(Constants::API_KEY_KEY);
        if (empty($api_key)) {
            $this->abort('API key is required.');
        }

        $syncer = Store_Sync_Service::get_instance()->sync();

        if ($syncer === false) {
            $this->abort('Sync failed.');
        }

        $this->ok();
    }

    public function action()
    {
        return 'rulehook_sync';
    }
}
