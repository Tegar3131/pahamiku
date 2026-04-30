<?php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

return [
    'DB_HOST'  => 'sqlXXX.infinityfree.com',
    'DB_NAME'  => 'if0_XXXXXXX_pahamiku',
    'DB_USER'  => 'if0_XXXXXXX',
    'DB_PASS'  => 'GANTI_PASSWORD_DATABASE',
    'BASE_URL' => 'https://namadomainanda.infinityfreeapp.com',
];
