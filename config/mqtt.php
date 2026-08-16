<?php

return [
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),

    'ws_url' => env('MQTT_WS_URL', '/mqtt'),
    'browser_user' => env('MQTT_BROWSER_USER', 'tm_browser'),
    'browser_pass' => env('MQTT_BROWSER_PASS', ''),
];
