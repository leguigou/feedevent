<?php

return [
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Feedevent'),
        'email' => env('ADMIN_EMAIL', 'admin@feedevent.fr'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'settings' => [
        'site.name' => env('APP_NAME', 'Feedevent'),
        'site.support_email' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'site.default_city' => env('SITE_DEFAULT_CITY', ''),
        'site.registration_enabled' => env('REGISTRATION_ENABLED', true),

        'llm.provider' => env('LLM_PROVIDER', 'openrouter'),
        'llm.api_key' => env('OPENROUTER_API_KEY', env('DEEPSEEK_API_KEY', '')),
        'llm.base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'llm.model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-chat'),
        'llm.vision_model' => env('OPENROUTER_VISION_MODEL', 'meta-llama/llama-3.2-11b-vision-instruct'),
        'llm.temperature' => env('LLM_TEMPERATURE', 0.1),
        'llm.max_tokens' => env('LLM_MAX_TOKENS', 2000),

        'facebook.enabled' => env('FACEBOOK_ENABLED', false),
        'facebook.app_id' => env('FACEBOOK_APP_ID', ''),
        'facebook.app_secret' => env('FACEBOOK_APP_SECRET', ''),
        'facebook.redirect_uri' => env('FACEBOOK_REDIRECT_URI', ''),
        'facebook.graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v23.0'),
        'facebook.system_access_token' => env('FACEBOOK_SYSTEM_ACCESS_TOKEN', ''),
    ],

    'secret_keys' => [
        'llm.api_key',
        'facebook.app_secret',
        'facebook.system_access_token',
    ],
];
