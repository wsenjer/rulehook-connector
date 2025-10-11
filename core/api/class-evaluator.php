<?php

namespace RuleHook\Core\Api;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Rate;

/**
 * RuleHook Evaluator
 *
 * Handles rate evaluations for cart data
 */
class Evaluator
{
    /**
     * @var Client The API client
     */
    private $client;

    /**
     * @param  Client  $client  The API client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Evaluate shipping rates based on cart data
     *
     * @param  array  $cartData  Cart data to evaluate
     * @return array List of available shipping rates
     *
     * @throws Exception When the API request fails
     */
    public function evaluateRates(array $cartData): array
    {
        $response = $this->client->request('POST', 'v1/evaluate', $cartData);
        if (! isset($response['rates']) || ! is_array($response['rates'])) {
            throw new Exception('Invalid response: missing rates data');
        }

        return $response['rates'];
    }
}
