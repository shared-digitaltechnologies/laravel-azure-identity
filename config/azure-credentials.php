<?php

return [

    /* ----------------------------------------------------------------
     *  AZURE CREDENTIAL DRIVER
     * ----------------------------------------------------------------
     *
     * Here, you can select which kind of credential you want to use
     * to fetch access tokens.
     *
     * Possible values:
     *
     *  - cli : Use the az cli to get tokens.
     *  - managed_identity : Use the managed identity credentials.
     *                       (only works for deployed webapps)
     *  - secret : Fetch access tokens using the OAuth2
     *             client-credential flow with the 'client_secret'.
     *  - certificate : Fetch access tokens using the OAuth2
     *                  client-credential flow with the client
     *                  certificate.
     *  - password : Fetches access token using the OAuth2
     *               password credential flow (needs username and
     *               password to be set in the environment variables)
     *  - default : Automatically determine the right driver
     *              based on the available information
     */
    "driver" => env('AZURE_CREDENTIAL_DRIVER', 'default'),

    /* -----------------------------------------------------------------
     *  MANAGED IDENTITY INFO
     * -----------------------------------------------------------------
     *
     * The following information is needed for the managed_identity
     * driver. The environment variables are set such that it will
     * automatically work if you deploy this application to Azure Webapps
     */

    "identity_endpoint" => env('IDENTITY_ENDPOINT', env('MSI_ENDPOINT')),
    "identity_header" => env('IDENTITY_HEADER'),

    "resource_id" => env('AZURE_RESOURCE_ID'), // optional

    /* -----------------------------------------------------------------
     *  AUTH2 CREDENTIALS ('secret', 'password', 'certificate')
     * -----------------------------------------------------------------
     *
     * The following information is needed for the 'secret', 'certificate'
     * and 'password' drivers. The environment variables are the default
     * environment variables that Azure uses for their other SDKs.
     */

    // Required for all OAuth2 drivers:

    "tenant_id" => env('AZURE_TENANT_ID'),
    "client_id" => env('AZURE_CLIENT_ID'),

    // Required for 'secret' (optional for 'password')

    "client_secret" => env('AZURE_CLIENT_SECRET'),

    // One of the following is required for 'certificate':

    "client_certificate" => env('AZURE_CLIENT_CERTIFICATE'),
    "client_certificate_jwk" => env('AZURE_CLIENT_CERTIFICATE_JWK'),
    "client_certificate_path" => env('AZURE_CLIENT_CERTIFICATE_PATH'),
    "client_certificate_password" => env('AZURE_CLIENT_CERTIFICATE_PASSWORD', ''),

    // Required for 'password'.

    "username" => env('AZURE_USERNAME'),
    "password" => env('AZURE_PASSWORD'),

    /* -----------------------------------------------------------------
     *  CLI CREDENTIALS
     * -----------------------------------------------------------------
     *
     * The following information may be needed when using the `cli`
     * credential. This is largely dependent on how you configured your
     * `az` tool locally.
     *
     * If you configured your az cli tool normally, it should work
     * out of the box without changing these defaults.
     */

    "subscription_id" => env('AZURE_SUBSCRIPTION_ID'),
    "additionally_allowed_tenants" => env('AZURE_ADDITIONALLY_ALLOWED_TENANTS'),
    "az_path" => env('AZURE_CLI_PATH', 'az'),

    /* -----------------------------------------------------------------
     *  CACHE SETTINGS
     * -----------------------------------------------------------------
     *
     * Here, you may configure the access token cache used to re-use
     * access tokens.
     */

    "cache" => [
        // The cache store used to cache the tokens. (no value means it uses the default store)
        "store" => env('AZURE_CREDENTIALS_CACHE_STORE'),
        // A prefix for the cached token keys.
        "prefix" => env('AZURE_CREDENTIALS_CACHE_PREFIX', 'azure_credentials:token:'),
        // The default ttl for a cached access token (if it could not be derived from the token response)
        "ttl" => env('AZURE_CREDENTIALS_CACHE_TTL', '1 hour'),
        // Some extra leeway between the access token being invalidated in the cache and the time that the token
        // actually expires.
        "ttl_leeway" => env('AZURE_CREDENTIALS_CACHE_LEEWAY', '1 minute'),
    ]
];
