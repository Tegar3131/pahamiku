<?php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

// Konfigurasi development lokal.
return [
    'DB_HOST'  => 'localhost',
    'DB_NAME'  => 'pahamiku',
    'DB_USER'  => 'root',
    'DB_PASS'  => '',
    'BASE_URL' => 'http://localhost/pahamiku',
];
