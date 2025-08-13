<?php
return [
    // This is the guard used when logging in as the impersonated user.
    'guard' => env('FILAMENT_IMPERSONATE_GUARD', 'web'),

    // After impersonating this is where we'll redirect you to.
    'redirect_to' => env('FILAMENT_IMPERSONATE_REDIRECT', '/'),

    // We wire up a route for the "leave" button. You can change the middleware stack here if needed.
    'leave_middleware' => env('FILAMENT_IMPERSONATE_LEAVE_MIDDLEWARE', 'web'),

    // Add a prefix for routes - Useful for apps installed with a subdirectory
    'route_prefix' => env('FILAMENT_IMPERSONATE_ROUTE_PREFIX', null),

    'allow_soft_deleted' => env('FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED', false),

    'banner' => [
        // Available hooks: https://filamentphp.com/docs/3.x/support/render-hooks#available-render-hooks
        'render_hook' => env('FILAMENT_IMPERSONATE_BANNER_RENDER_HOOK', 'panels::body.start'),

        // Currently supports 'dark', 'light' and 'auto'.
        'style' => env('FILAMENT_IMPERSONATE_BANNER_STYLE', 'dark'),

        // Turn this off if you want `absolute` positioning, so the banner scrolls out of view
        'fixed' => env('FILAMENT_IMPERSONATE_BANNER_FIXED', true),

        // Currently supports 'top' and 'bottom'.
        'position' => env('FILAMENT_IMPERSONATE_BANNER_POSITION', 'top'),

        'styles' => [
            'light' => [
                'text' => '#1f2937',
                'background' => '#f3f4f6',
                'border' => '#e8eaec',
            ],
            'dark' => [
                'text' => '#f3f4f6',
                'background' => '#1f2937',
                'border' => '#374151',
            ],
        ]
    ],
];
