<?php
// ERP settings for website orders (used by RestaurantOrder::getSettings())
return [
    // Website orders are invoiced to this customer account (acc_accounts.id) and sold from this branch
    'customer_account_id' => 6482,
    'branch_id' => 1,
    // The restnext website, for "View on website" links in the ERP
    'website_url' => 'http://localhost:3000',
];
