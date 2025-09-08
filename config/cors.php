<?php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET','POST','OPTIONS'],
    'allowed_origins' => ['*'], // GET needs to work from wallpaper (possibly file:// via helper)
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];

