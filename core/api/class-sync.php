<?php

namespace RuleHook\Core\Api;

/**
 * RuleHook Sync
 *
 * Handles synchronization of store metadata
 */
class Sync
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
     * Sync store metadata with RuleHook
     *
     * @param  array  $storeData  Store metadata to sync
     * @return bool Whether the sync was successful
     *
     * @throws Exception When the API request fails
     */
    public function syncStoreMetadata(array $storeData): bool
    {
        $response = $this->client->request('POST', 'v1/store-sync', $storeData);

        return isset($response['success']) && $response['success'] === true;
    }
}
