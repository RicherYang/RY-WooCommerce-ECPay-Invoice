<?php
defined('RY_WEI_VERSION') or exit('No direct script access allowed');

return [
    [
        'title' => __('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'wei_options',
        'type' => 'title'
    ],
    [
        'title' => __('License key', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'pro_Key',
        'type' => 'text',
        'default' => ''
    ],
    [
        'type' => 'rywei_version_info',
    ],
    [
        'id' => 'wei_options',
        'type' => 'sectionend'
    ]
];
