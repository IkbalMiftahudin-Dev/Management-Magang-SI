<?php

return [
    'url' => rtrim((string) env('SSO_URL', ''), '/'),
    'client_id' => env('SSO_CLIENT_ID', ''),
    'client_secret' => env('SSO_CLIENT_SECRET', ''),
    'redirect_uri' => env('SSO_REDIRECT_URI', ''),
    'post_logout_redirect_uri' => env('SSO_POST_LOGOUT_REDIRECT_URI'),
    'jwks_cache_ttl' => (int) env('SSO_JWKS_CACHE_TTL', 300),
    'refresh_leeway_seconds' => (int) env('SSO_REFRESH_LEEWAY_SECONDS', 60),
    'http_timeout_seconds' => (int) env('SSO_HTTP_TIMEOUT_SECONDS', 10),
];
