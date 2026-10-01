<?php

return [
    'payment_methods' => [
        'bca_demo' => ['label' => 'Transfer BCA · simulasi', 'bank' => 'BCA', 'account_name' => 'Izzan Digital Printing (SIMULASI)', 'account_number' => '0000-0000-0000 (DUMMY)', 'type' => 'bank'],
        'mandiri_demo' => ['label' => 'Transfer Mandiri · simulasi', 'bank' => 'Mandiri', 'account_name' => 'Izzan Digital Printing (SIMULASI)', 'account_number' => '0000-0000-0000 (DUMMY)', 'type' => 'bank'],
        'bri_demo' => ['label' => 'Transfer BRI · simulasi', 'bank' => 'BRI', 'account_name' => 'Izzan Digital Printing (SIMULASI)', 'account_number' => '0000-0000-0000 (DUMMY)', 'type' => 'bank'],
        'qris_demo' => ['label' => 'QRIS · simulasi', 'type' => 'qris'],
    ],
    'payment_demo' => true,
    'address' => env('PRINTING_STORE_ADDRESS'), 'phone' => env('PRINTING_STORE_PHONE'),
];
