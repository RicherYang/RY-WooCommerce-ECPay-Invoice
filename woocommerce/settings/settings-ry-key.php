<?php

return [
    [
        'title' => 'RY ECPay Invoice for WooCommerce',
        'id' => 'wei_options',
        'type' => 'title'
    ],
    [
        'title' => __('License key', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'license_key',
        'type' => 'text',
        'default' => ''
    ],
    [
        'id' => 'ry_wei_version_info',
        'type' => 'ry_wei_version_info',
    ],
    [
        'id' => 'wei_options',
        'type' => 'sectionend'
    ]
];
