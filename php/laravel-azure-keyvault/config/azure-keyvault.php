<?php

return [

    /* ------------------------------------------------------------------
     *  CREDENTIALS
     * ------------------------------------------------------------------
     *
     * These settings configure how you authenticate to the key vault.
     *
     */

    // The name of the credential driver used to get access tokens for the key vault.
    "credential_driver" => env('AZURE_KEYVAULT_CREDENTIAL_DRIVER'),


    /* -----------------------------------------------------------------
     *  CACHE
     * -----------------------------------------------------------------
     *
     * These settings configure how you would cache the key-vault.
     *
     */

    "cache" => [
        // The cache store used to cache the key vault data. No value means the default store.
        "store" => env('AZURE_KEYVAULT_CACHE_STORE'),
        // Prefix for all key vault cache items.
        "prefix" => env('AZURE_KEYVAULT_CACHE_PREFIX', 'azure-keyvault:'),
        // The default ttl for key vault items.
        "ttl" => env('AZURE_KEYVAULT_TTL', '1 hour')
    ]

];
