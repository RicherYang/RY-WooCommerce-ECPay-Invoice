<?php

$order_statuses = wc_get_order_statuses();
$paid_status = [];
foreach (wc_get_is_paid_statuses() as $status) {
    $paid_status[] = $order_statuses['wc-' . $status];
}
$paid_status = implode(', ', $paid_status);

return [
    [
        'title' => __('Base options', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'base_options',
        'type' => 'title',
    ],
    [
        'title' => __('Enable/Disable', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'enabled_invoice',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable ECPay invoice method', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('Debug log', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'ecpay_invoice_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'ry-woocommerce-ecpay-invoice') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log API / IPN information, inside %s', 'ry-woocommerce-ecpay-invoice'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_ecpay_invoice') . '</code>',
            )
            . '<p class="description" style="margin-bottom:2px">' . __('Note: this may log personal information.', 'ry-woocommerce-ecpay-invoice') . '</p>',
    ],
    [
        'title' => __('Order no prefix', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed.', 'ry-woocommerce-ecpay-invoice'),
        'desc_tip' => true,
    ],
    [
        'title' => __('Show invoice number', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'show_invoice_number',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Show invoice number in Frontend order list', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('Move billing company', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'move_billing_company',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Move billing company to invoice area', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend',
    ],
    [
        'title' => __('Invoice options', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'invoice_options',
        'type' => 'title',
    ],
    [
        'title' => __('support paper type (B2C)', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'support_carruer_type_none',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('You need print invoice and seed to orderer.', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('Company invoice carruer mode (B2B2C)', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'company_carruer_mode',
        'type' => 'select',
        'default' => 'host',
        'options' => [
            'host' => _x('Cloud host (ecpay carruer)', 'company mode', 'ry-woocommerce-ecpay-invoice'),
            'print' => _x('Print', 'company mode', 'ry-woocommerce-ecpay-invoice'),
        ],
    ],
    [
        'title' => __('check number with api', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'check_number_with_api',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Use ECPay API to check the carruer number or donate number is right.', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('user SKU as product name', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'use_sku_as_name',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('If product no SKU, back to use product name', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('Get mode', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'get_mode',
        'type' => 'select',
        'default' => 'manual',
        'options' => [
            'manual' => _x('Manual', 'get mode', 'ry-woocommerce-ecpay-invoice'),
            'auto_paid' => _x('Auto ( when order paid )', 'get mode', 'ry-woocommerce-ecpay-invoice'),
            'auto_completed' => _x('Auto ( when order completed )', 'get mode', 'ry-woocommerce-ecpay-invoice'),
        ],
        'desc' => sprintf(
            /* translators: %s: paid status */
            __('Order paid status: %s', 'ry-woocommerce-ecpay-invoice'),
            $paid_status,
        ),
    ],
    [
        'title' => __('Skip foreign orders', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'skip_foreign_order',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Disable auto get invoice for order billing country and shipping country are not in Taiwan.', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('Delay get days', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'get_delay_days',
        'type' => 'text',
        'default' => '0',
        'desc' => '如設定為 <strong>0</strong> 天表示立即開立。<br>'
            . '將於達成自動開立的條件下連結至綠界的系統，並設定延遲 N 天後<strong>自動完成</strong>開立發票的相關動作。<br>'
            . '受限於綠界 API 的限制，於設定自動開立到發票完成開立的這段期間中，只能至綠界的管理後台進行待開立發票的取消動作。',
    ],
    [
        'title' => __('Invalid mode', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'invalid_mode',
        'type' => 'select',
        'default' => 'manual',
        'options' => [
            'manual' => _x('Manual', 'invalid mode', 'ry-woocommerce-ecpay-invoice'),
            'auto_cancell' => _x('Auto ( when order status cancelled OR refunded )', 'invalid mode', 'ry-woocommerce-ecpay-invoice'),
        ],
    ],
    [
        'title' => __('Amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'amount_abnormal_mode',
        'type' => 'select',
        'default' => '',
        'options' => [
            '' => _x('No action', 'amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
            'product' => _x('Add one product to match order amount', 'amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
            'order' => _x('Change order total amount', 'amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
        ],
    ],
    [
        'title' => __('fix amount product name', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'amount_abnormal_product',
        'type' => 'text',
        'default' => __('Discount', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'id' => 'invoice_options',
        'type' => 'sectionend',
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'api_options',
        'type' => 'title',
    ],
    [
        'title' => __('ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'ecpay_invoice_testmode',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice')
            . '<p class="description" style="margin-bottom:2px">' . __('Note: Recommend using this for development purposes only.', 'ry-woocommerce-ecpay-invoice') . '<p>',
    ],
    [
        'title' => __('MerchantID', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'ecpay_MerchantID',
        'type' => 'text',
        'default' => '',
    ],
    [
        'title' => __('HashKey', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'ecpay_HashKey',
        'type' => 'text',
        'default' => '',
    ],
    [
        'title' => __('HashIV', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'ecpay_HashIV',
        'type' => 'text',
        'default' => '',
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend',
    ],
];
