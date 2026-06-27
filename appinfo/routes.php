<?php
return [
    'routes' => [
        ['name' => 'settings_api#get_settings', 'url' => '/api/settings', 'verb' => 'GET'],
        ['name' => 'settings_api#save_settings', 'url' => '/api/settings', 'verb' => 'POST'],
        ['name' => 'settings_api#preview_cleanup', 'url' => '/api/preview', 'verb' => 'POST'],
        ['name' => 'settings_api#run_cleanup', 'url' => '/api/cleanup', 'verb' => 'POST'],
    ]
];