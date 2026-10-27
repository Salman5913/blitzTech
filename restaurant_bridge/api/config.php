<?php
// Database + API settings
return [
    'db_host' => 'localhost',
    // ERP (demos3) database; the website tables live here with the rs_ prefix
    'db_name' => 'demofinac',
    'db_user' => 'root',
    'db_pass' => '',
    // Leave empty to disable. If set, the website must send the same key (restnext .env.local -> RESTAURANT_API_KEY)
    'api_key' => '',
    // Origins allowed to call the API ('*' for any)
    'cors_origin' => '*',
    // Where the ERP's own files are served (dish images saved in the ERP are relative paths like /demos3/assets/...)
    'public_url' => 'http://localhost',
    'delivery_fee' => 150,
    'free_delivery_over' => 3000,
];
