<?php
// Database + API settings
return [
    'db_host' => '13.140.214.164',
    // ERP (blitzztech) database; the website tables live here with the rs_ prefix
    'db_name' => 'erp_db',
    'db_user' => 'erp_user',
    'db_pass' => 'SA$12345',
    // Leave empty to disable. If set, the website must send the same key (restnext .env.local -> RESTAURANT_API_KEY)
    'api_key' => '',
    // Origins allowed to call the API ('*' for any)
    'cors_origin' => '*',
    // Where the ERP's own files are served (dish images saved in the ERP are relative paths like /blitzztech/assets/...)
    'public_url' => 'https://erp.blitzztech.com',
    'delivery_fee' => 150,
    'free_delivery_over' => 3000,
];
