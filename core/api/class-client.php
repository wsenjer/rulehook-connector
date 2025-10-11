<?php

namespace RuleHook\Core\Api;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * RuleHook API Client
 *
 * Handles communication with the RuleHook API
 */
class Client
{
    /**
     * @var string The API base URL
     */
    private string $baseUrl;

    /**
     * @var string The API access token
     */
    private string $accessToken;

    /**
     * @var int Default request timeout in seconds
     */
    private $timeout = 10;

    /**
     * @param  string  $baseUrl  The API base URL
     * @param  string  $accessToken  The API access token
     * @param  int  $timeout  Request timeout in seconds
     */
    public function __construct(string $baseUrl, string $accessToken, int $timeout = 10)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->accessToken = $accessToken;
        $this->timeout = $timeout;
    }

    /**
     * Make a request to the RuleHook API
     *
     * @param  string  $method  HTTP method (GET or POST)
     * @param  string  $endpoint  API endpoint (without leading slash)
     * @param  array  $data  Request data
     * @return array Decoded response data
     *
     * @throws Exception When the request fails
     */
    public function request(string $method, string $endpoint, array $data = [])
    {
        $url = $this->baseUrl.'/'.ltrim($endpoint, '/');
        $method = strtoupper($method);

        $args = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->accessToken,
                'X-Session-ID' => $this->getOrCreateSessionId(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ];

        if ($method === 'POST') {
            $args['body'] = json_encode($data);
            $response = wp_remote_post($url, $args);
        } else {
            if (! empty($data)) {
                $url = add_query_arg($data, $url);
            }
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            throw new Exception(
                esc_html('API request failed: '. $response->get_error_message()),
                500
            );
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($statusCode < 200 || $statusCode >= 300) {
            $decodedBody = json_decode($body, true);
            $errorMessage = isset($decodedBody['error'])
                ? $decodedBody['error']
                : 'API error: HTTP '.$statusCode;

            throw new Exception(
                esc_html($errorMessage),
                esc_html($statusCode),
                null,
                esc_html($decodedBody)
            );
        }

        $decodedResponse = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }

        return $decodedResponse;
    }

    public function validateApiKey(string $storeId, string $storeName): array
    {
        $response = $this->request('POST', '/v1/validate-api-key',
            [
                'apiKey' => $this->accessToken,
                'storeId' => $storeId,
                'storeName' => $storeName,
                'platform' => 'woocommerce',
            ]);

        return $response;

    }

    private function getOrCreateSessionId()
    {
        if (! isset($_SESSION)) {
            session_start();
        }

        if (empty($_SESSION['rulehook_session_id'])) {
            $_SESSION['rulehook_session_id'] = wp_generate_uuid4();
        }

        return sanitize_key($_SESSION['rulehook_session_id']);
    }
}
