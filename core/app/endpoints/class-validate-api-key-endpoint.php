<?php

namespace RuleHook\Core\App\Endpoints;

use RuleHook\Core\Api\Client;
use RuleHook\Core\Api\Exception;
use RuleHook\Core\Constants;

class Validate_Api_Key_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {

        if (empty($data['apiKey'])) {
            $payload['message'] = __('API key is required.', 'rulehook-connector');

            return $payload;
        }

        $api_key = sanitize_text_field($data['apiKey']);

        $client = new Client(Constants::API_URL, $api_key);
        $storeDomain = parse_url(home_url(), PHP_URL_HOST);
        $storeName = get_bloginfo('name');
        try {
            $response = $client->validateApiKey($storeDomain, $storeName);
        } catch (Exception $e) {
            $responseData = $e->getResponseData();
            if (isset($responseData['reason'])) {
                $payload['reason'] = $responseData['reason'];
            }
            $payload['message'] = $e->getMessage();

            return $payload;
        }

        if (isset($response['valid']) && $response['valid'] === true) {
            $teamId = $response['teamId'];
            update_option(Constants::TEAM_ID_KEY, $teamId);
            update_option(Constants::API_KEY_KEY, $api_key);
        }

        return $response;

    }

    public function action()
    {
        return 'rulehook_validate_api_key';
    }
}
