<?php

defined('ABSPATH') or exit;

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
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[prefix]',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed.', 'ry-woocommerce-ecpay-invoice'),
        'desc_tip' => true,
        'autoload' => false,
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
        'title' => __('Support paper type (B2C)', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'support_carruer_type_none',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('You need print invoice and seed to orderer.', 'ry-woocommerce-ecpay-invoice'),
    ],
    [
        'title' => __('Company invoice carruer mode (B2B2C)', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[company_carruer]',
        'type' => 'select',
        'default' => 'host',
        'options' => [
            'host' => _x('Cloud host (ecpay carruer)', 'company mode', 'ry-woocommerce-ecpay-invoice'),
            'print' => _x('Print', 'company mode', 'ry-woocommerce-ecpay-invoice'),
        ],
        'autoload' => false,
    ],
    [
        'title' => __('User SKU as product name', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[use_sku]',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('If product no SKU, back to use product name', 'ry-woocommerce-ecpay-invoice'),
        'autoload' => false,
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
        'autoload' => false,
    ],
    [
        'title' => __('Delay get days', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'get_delay_days',
        'type' => 'text',
        'default' => '0',
        'desc' => '如設定為 <strong>0</strong> 天表示立即開立。<br>'
            . '將於達成自動開立的條件下連結至綠界的系統，並設定延遲 N 天後<strong>自動完成</strong>開立發票的相關動作。',
        'autoload' => false,
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
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[abnormal_mode]',
        'type' => 'select',
        'default' => '',
        'options' => [
            '' => _x('No action', 'amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
            'product' => _x('Add one product to match order amount', 'amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
            'order' => _x('Change order total amount', 'amount abnormal mode', 'ry-woocommerce-ecpay-invoice'),
        ],
        'autoload' => false,
    ],
    [
        'title' => __('Fix amount product name', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[abnormal_product]',
        'type' => 'text',
        'default' => __('Discount', 'ry-woocommerce-ecpay-invoice'),
        'autoload' => false,
    ],
    [
        'title' => __('Custom track code', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[trackcode]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
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
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[testmode]',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice')
            . '<p class="description" style="margin-bottom:2px">' . __('Note: Recommend using this for development purposes only.', 'ry-woocommerce-ecpay-invoice') . '<p>',
        'autoload' => false, ],
    [
        'title' => __('MerchantID', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[MerchantID]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'title' => __('HashKey', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[HashKey]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'title' => __('HashIV', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::OPTION_PREFIX . 'apiinfo[HashIV]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend',
    ],
];
