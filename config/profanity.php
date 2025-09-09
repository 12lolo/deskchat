<?php

return [
    // Simple, local wordlist for fast server-side filtering.
    // Customize this list in your own deployment. Keep it short to avoid latency.
    'words' => [
        // Add your own language-specific terms below. Examples kept generic.
        'badword',
        'vloek',
        'scheldwoord',
    ],

    // Optional external service configuration (not used by default logic)
    'url'      => env('PROFANITY_API_URL', 'https://vector.profanity.dev'),
    'timeout'  => (int) env('PROFANITY_TIMEOUT', 2),
    'retry'    => (int) env('PROFANITY_RETRY', 1),
    'block'    => filter_var(env('PROFANITY_BLOCK', true), FILTER_VALIDATE_BOOL),
];
