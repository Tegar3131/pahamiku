<?php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

// Isi manual untuk server hosting (InfinityFree).
return [
    'DB_HOST'  => '',
    'DB_NAME'  => '',
    'DB_USER'  => '',
    'DB_PASS'  => '',
    'BASE_URL' => '',
];
