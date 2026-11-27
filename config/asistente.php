<?php
return [
    'enabled' => (bool) env('ASISTENTE_ENABLED', false),
    'url' => env('ASISTENTE_URL', 'http://127.0.0.1:8001/chat'),
    'token' => env('ASISTENTE_TOKEN'),
    'timeout' => 60,
];
