<?php

return [
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => env('MQTT_PORT', 1881),
    'client_id' => env('MQTT_CLIENT_ID', 'reverb-websocket-subscriber'),
    'username' => env('MQTT_USERNAME', null),
    'password' => env('MQTT_PASSWORD', null),
    'keep_alive' => 60,
];
