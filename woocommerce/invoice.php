<?php

final class RY_WEI_Invoice
{
    public static $log_enabled = false;
    public static $log = false;

    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/ecpay-invoice-api.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/ecpay-invoice-response.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/class-wc-meta-box-invoice-data.php';

            self::$log_enabled = 'yes' === RY_WEI::get_option('invoice_log', 'no');

            RY_WEI_Invoice_Response::init();

            switch (RY_WEI::get_option('get_mode')) {
                case 'auto_paid':
                    $paid_statuses = wc_get_is_paid_statuses();
                    foreach ($paid_statuses as $status) {
                        add_action('woocommerce_order_status_' . $status, [__CLASS__, 'auto_get_invoice']);
                    }
                    break;
                case 'auto_completed':
                    $completed_statuses = ['completed'];
                    foreach ($completed_statuses as $status) {
                        add_action('woocommerce_order_status_' . $status, [__CLASS__, 'auto_get_invoice']);
                    }
                    break;
            }
            add_action('ry_wei_auto_get_invoice', ['RY_WEI_Invoice_Api', 'get'], 10, 2);
            add_action('ry_wei_auto_get_delay_invoice', ['RY_WEI_Invoice_Api', 'get_delay'], 10, 2);

            if ('auto_cancell' == RY_WEI::get_option('invalid_mode')) {
                add_action('woocommerce_order_status_cancelled', ['RY_WEI_Invoice_Api', 'invalid']);
                add_action('woocommerce_order_status_refunded', ['RY_WEI_Invoice_Api', 'invalid']);
            }

            if (is_admin()) {
                add_filter('enable_ry_invoice', [__CLASS__, 'add_enable_ry_invoice']);
                add_action('admin_enqueue_scripts', [__CLASS__, 'add_scripts']);

                add_filter('manage_shop_order_posts_columns', [__CLASS__, 'add_admin_invoice_column'], 11);
                add_action('manage_shop_order_posts_custom_column', [__CLASS__, 'show_admin_invoice_column'], 11);
                add_action('woocommerce_admin_order_data_after_billing_address', ['WC_Meta_Box_Invoice_Data', 'output']);
                add_action('woocommerce_update_order', [__CLASS__, 'save_order_update']);

                add_action('wp_ajax_RY_WEI_get', [__CLASS__, 'get_invoice']);
                add_action('wp_ajax_RY_WEI_invalid', [__CLASS__, 'invalid_invoice']);
                add_action('wp_ajax_RY_WEI_cancel_delay', [__CLASS__, 'cancel_delay_invoice']);
            } else {
                add_filter('default_checkout_invoice_company_name', [__CLASS__, 'set_default_invoice_company_name']);
                if ('yes' == RY_WEI::get_option('show_invoice_number', 'no')) {
                    add_filter('woocommerce_account_orders_columns', [__CLASS__, 'add_invoice_column']);
                    add_action('woocommerce_my_account_my_orders_column_invoice-number', [__CLASS__, 'show_invoice_column']);
                }
            }
        }
    }

    public static function auto_get_invoice($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $skip_shipping = apply_filters('ry_wei_skip_autoget_invoice_shipping', []);

        if (!empty($skip_shipping)) {
            foreach ($order->get_items('shipping') as $item) {
                if (in_array($item->get_method_id(), $skip_shipping)) {
                    return false;
                }
            }
        }

        $delay_days = (int) RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days == 0) {
            WC()->queue()->schedule_single(time() + 3, 'ry_wei_auto_get_invoice', [$order_id], '');
        } else {
            WC()->queue()->schedule_single(time() + 3, 'ry_wei_auto_get_delay_invoice', [$order_id], '');
        }
    }

    public static function add_enable_ry_invoice($enable)
    {
        $enable[] = 'ecpay';

        return $enable;
    }

    public static function add_scripts()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if (in_array($screen_id, ['shop_order', 'edit-shop_order', 'woocommerce_page_wc-settings'])) {
            wp_enqueue_script('ry-wei-admin-script', RY_WEI_PLUGIN_URL . 'style/admin/ry_ecpay_invoice.js', ['jquery'], RY_WEI_VERSION);
            wp_enqueue_style('ry-wei-admin-style', RY_WEI_PLUGIN_URL . 'style/admin/ry_ecpay_invoice.css', [], RY_WEI_VERSION);

            wp_localize_script('ry-wei-admin-script', 'ry_wei_script', [
                'get_loading_text'=> __('Get invoice.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                'invalid_loading_text'=> __('Invalid invoice.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                'cancel_delay_loading_text'=> __('Cancel delay invoice data.<br>Please wait.', 'ry-woocommerce-ecpay-invoice')
            ]);
        }
    }

    public static function add_admin_invoice_column($columns)
    {
        if (!isset($columns['invoice-number'])) {
            $add_index = array_search('order_status', array_keys($columns)) + 1;
            $pre_array = array_splice($columns, 0, $add_index);
            $array = [
                'invoice-number' => __('Invoice number', 'ry-woocommerce-ecpay-invoice')
            ];
            $columns = array_merge($pre_array, $array, $columns);
        }
        return $columns;
    }

    public static function show_admin_invoice_column($column)
    {
        if ($column == 'invoice-number') {
            global $the_order;

            $invoice_number = $the_order->get_meta('_invoice_number');
            if ($invoice_number == 'zero') {
                echo __('Zero no invoice', 'ry-woocommerce-ecpay-invoice');
            } elseif ($invoice_number == 'negative') {
                echo __('Negative no invoice', 'ry-woocommerce-ecpay-invoice');
            } elseif ($invoice_number == 'delay') {
                echo __('Delay get invoice', 'ry-woocommerce-ecpay-invoice');
            } else {
                echo $the_order->get_meta('_invoice_number');
            }
        }
    }

    public static function get_invoice()
    {
        $order_ID = (int) $_POST['id'];

        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        RY_WEI_Invoice_Api::get($order);
    }

    public static function invalid_invoice()
    {
        $order_ID = (int) $_POST['id'];

        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        RY_WEI_Invoice_Api::invalid($order);
    }

    public static function cancel_delay_invoice()
    {
        $order_ID = (int) $_POST['id'];

        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        RY_WEI_Invoice_Api::cancel_delay($order);
    }

    public static function get_ecpay_api_info()
    {
        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'no')) {
            $MerchantID = '2000132';
            $HashKey = 'ejCk326UnaZWKisg';
            $HashIV = 'q9jcZX8Ib9LM8wYk';
        } else {
            $MerchantID = RY_WEI::get_option('ecpay_MerchantID');
            $HashKey = RY_WEI::get_option('ecpay_HashKey');
            $HashIV = RY_WEI::get_option('ecpay_HashIV');
        }

        return [$MerchantID, $HashKey, $HashIV];
    }

    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled || $level == 'error') {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }

            self::$log->log($level, $message, [
                'source' => 'ry_ecpay_invoice',
                '_legacy' => true
            ]);
        }
    }

    public static function set_default_invoice_company_name()
    {
        if (is_user_logged_in()) {
            $customer = new WC_Customer(get_current_user_id(), true);

            return $customer->get_billing_company();
        }

        return '';
    }

    public static function save_order_update($order_id)
    {
        if ($order = wc_get_order($order_id)) {
            if (isset($_POST['_invoice_type'])) {
                $order->update_meta_data('_invoice_type', wc_clean(wp_unslash($_POST['_invoice_type'])));
                $order->update_meta_data('_invoice_carruer_type', wc_clean(wp_unslash($_POST['_invoice_carruer_type'])));
                $order->update_meta_data('_invoice_carruer_no', wc_clean(wp_unslash($_POST['_invoice_carruer_no'])));
                $order->update_meta_data('_invoice_no', wc_clean(wp_unslash($_POST['_invoice_no'])));
                $order->update_meta_data('_invoice_donate_no', wc_clean(wp_unslash($_POST['_invoice_donate_no'])));

                $invoice_number = wc_clean(wp_unslash($_POST['_invoice_number']));
                if (!empty($invoice_number)) {
                    $order->update_meta_data('_invoice_number', $invoice_number);
                    $order->update_meta_data('_invoice_random_number', wc_clean(wp_unslash($_POST['_invoice_random_number'])));
                    $date = gmdate('Y-m-d H:i:s', strtotime($_POST['_invoice_date'] . ' ' . (int) $_POST['_invoice_date_hour'] . ':' . (int) $_POST['_invoice_date_minute'] . ':' . (int) $_POST['_invoice_date_second']));
                    $order->update_meta_data('_invoice_date', $date);
                }
                $order->save_meta_data();
            }
        }
    }

    public static function add_invoice_column($columns)
    {
        $add_index = array_search('order-total', array_keys($columns)) + 1;
        $pre_array = array_splice($columns, 0, $add_index);
        $array = [
            'invoice-number' => __('Invoice number', 'ry-woocommerce-ecpay-invoice')
        ];
        return array_merge($pre_array, $array, $columns);
    }

    public static function show_invoice_column($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        if (!in_array($invoice_number, ['delay', 'zero', 'negative'])) {
            echo $invoice_number;
        }
    }
}

RY_WEI_Invoice::init();
