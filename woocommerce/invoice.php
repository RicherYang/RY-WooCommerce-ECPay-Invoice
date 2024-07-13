<?php

final class RY_WEI_WC_Invoice extends RY_WEI_Model
{
    protected static $_instance = null;

    protected $model_type = 'ecpay_invoice';

    public static function instance(): RY_WEI_WC_Invoice
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();

        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WEI_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php';
        include_once RY_WEI_PLUGIN_DIR . 'woocommerce/invoice-api.php';
        include_once RY_WEI_PLUGIN_DIR . 'woocommerce/invoice-response.php';

        RY_WEI_WC_Invoice_Response::instance();

        switch (RY_WEI::get_option('get_mode')) {
            case 'auto_paid':
                $paid_statuses = wc_get_is_paid_statuses();
                foreach ($paid_statuses as $status) {
                    add_action('woocommerce_order_status_' . $status, [$this, 'auto_get_invoice']);
                }
                break;
            case 'auto_completed':
                $completed_statuses = ['completed'];
                foreach ($completed_statuses as $status) {
                    add_action('woocommerce_order_status_' . $status, [$this, 'auto_get_invoice']);
                }
                break;
        }

        if ('auto_cancell' === RY_WEI::get_option('invalid_mode')) {
            add_action('woocommerce_order_status_cancelled', [$this, 'auto_delete_invoice']);
            add_action('woocommerce_order_status_refunded', [$this, 'auto_delete_invoice']);
        }

        if (is_admin()) {
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/ajax.php';
            RY_WEI_WC_Admin_Ajax::instance();

            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/invoice.php';
            RY_WEI_WC_Admin_Invoice::instance();
        } else {
            add_filter('default_checkout_invoice_company_name', [$this, 'set_default_invoice_company_name']);
            if ('yes' === RY_WEI::get_option('show_invoice_number', 'no')) {
                add_filter('woocommerce_account_orders_columns', [$this, 'add_invoice_column']);
                add_action('woocommerce_my_account_my_orders_column_invoice-number', [$this, 'show_invoice_column']);
            }
        }
    }

    public function auto_get_invoice($order_ID)
    {
        $order = wc_get_order($order_ID);
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

        if ('yes' === RY_WEI::get_option('skip_foreign_order', 'no')) {
            if('TW' !== $order->get_billing_country()) {
                if($order->needs_shipping_address()) {
                    if('TW' !== $order->get_shipping_country()) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        $delay_days = (int) RY_WEI::get_option('get_delay_days', 0);
        if (0 === $delay_days) {
            WC()->queue()->schedule_single(time() + 10, 'ry_wei_auto_get_invoice', [$order_ID], '');
        } else {
            WC()->queue()->schedule_single(time() + 10, 'ry_wei_auto_get_delay_invoice', [$order_ID], '');
        }
    }

    public function auto_delete_invoice($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        $invoice_number = $order->get_meta('_invoice_number');
        if ($invoice_number) {
            if ('zero' == $invoice_number) {
            } elseif ('negative' == $invoice_number) {
            } elseif ('delay' == $invoice_number) {
                WC()->queue()->schedule_single(time() + 10, 'ry_wei_auto_cancel_invoice', [$order_ID], '');
            } else {
                WC()->queue()->schedule_single(time() + 10, 'ry_wei_auto_invalid_invoice', [$order_ID], '');
            }
        }
    }

    public function set_default_invoice_company_name()
    {
        if (is_user_logged_in()) {
            $customer = new WC_Customer(get_current_user_id(), true);

            return $customer->get_billing_company();
        }

        return '';
    }

    public function add_invoice_column($columns)
    {
        $add_index = array_search('order-total', array_keys($columns)) + 1;
        $pre_array = array_splice($columns, 0, $add_index);
        $array = [
            'invoice-number' => __('Invoice number', 'ry-woocommerce-ecpay-invoice'),
        ];
        return array_merge($pre_array, $array, $columns);
    }

    public function show_invoice_column($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        if (!in_array($invoice_number, ['delay', 'zero', 'negative'])) {
            echo esc_html($invoice_number);
        }
    }

    public function get_api_info()
    {
        if ($this->is_testmode()) {
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
}
