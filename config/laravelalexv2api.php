<?php

return [
    'sp_entity_id_prod' => 'SP-' . env('APP_NNA') . '-prodn1',
    'sp_entity_id_dev' => 'SP-' . env('APP_NNA') . '-recn1',
    'api_url_prod' => env('ALEX_V2_API_URL_PROD', ''),
    'api_url_dev' => env('ALEX_V2_API_URL_DEV', ''),
    'alex_v2_api_password' => env('ALEX_V2_API_PASSWORD', ''),
    'cert_path' => 'alexv2api',
    'server_key_file' => 'server.key',
    'server_pem_file' => 'server.pem',
];
