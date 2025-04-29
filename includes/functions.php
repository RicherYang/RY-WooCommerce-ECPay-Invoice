<?php

function rywei_link_error_to_msg($error)
{
    static $error_msg = [];
    if (empty($error_msg)) {
        $error_msg = [
            'Unknown key' => __('Unknown key', 'ry-woocommerce-ecpay-invoice'),
            'Locked key' => __('Locked key', 'ry-woocommerce-ecpay-invoice'),
            'Unknown target url' => __('Unknown target url', 'ry-woocommerce-ecpay-invoice'),
            'Used key' => __('Used key', 'ry-woocommerce-ecpay-invoice'),
            'Is tried' => __('Is tried', 'ry-woocommerce-ecpay-invoice'),
        ];
    }

    return $error_msg[$error] ?? $error;
}

function rywei_invoice_type_to_name($invoice_type)
{
    static $type_name = [];
    if (empty($type_name)) {
        $type_name = [
            'personal' => _x('personal', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
            'company' => _x('company', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
            'donate' => _x('donate', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
        ];
    }

    return $type_name[$invoice_type] ?? $invoice_type;
}

function rywei_carruer_type_to_name($carruer_type)
{
    static $type_name = [];
    if (empty($type_name)) {
        $type_name = [
            'none' => _x('none', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
            'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
            'smilepay_host' => _x('smilepay_host', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
            'MOICA' => _x('MOICA', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
            'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
        ];
    }

    return $type_name[$carruer_type] ?? $carruer_type;
}
