<?php
return [
    // This is the guard used when logging in as the impersonated user.
    'default_guard' => env('FILAMENT_IMPERSONATE_GUARD', 'web'),

    // We wire up a route for the "leave" button. You can change the middleware stack here if needed.
    'leave_middleware' => env('FILAMENT_IMPERSONATE_LEAVE_MIDDLEWARE', 'web'),

    'banner' => [
        // Currently supports 'dark' and 'light'.
        'style' => env('FILAMENT_IMPERSONATE_STYILE', 'dark'),

        // Not yet used. We will inject the banner HTML into every page of your app automatically.
        // 'auto-inject' => env('FILAMENT_IMPERSONATE_AUTO_BANNER', false)
    ]
];
