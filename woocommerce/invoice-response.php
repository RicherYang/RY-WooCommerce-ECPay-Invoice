<?php

class RY_WEI_WC_Invoice_Response extends RY_WEI_EcPay
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_WC_Invoice_Response
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    public function do_init()
    {
        add_action('woocommerce_api_ry_wei_delay_callback', [$this, 'check_callback']);

        add_action('valid_wei_callback_request', [$this, 'doing_callback']);
    }

    public function check_callback()
    {
        if (!empty($_POST)) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $ipn_info = wp_unslash($_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_wei_callback_request', $ipn_info);
            } else {
                $this->die_error();
            }
        } else {
            $this->die_error();
        }
    }

    protected function ipn_request_is_valid($ipn_info)
    {
        if (isset($ipn_info['inv_mer_id'])) {
            RY_WEI_WC_Invoice::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();

            if ($ipn_info['inv_mer_id'] == $MerchantID) {
                return true;
            } else {
                RY_WT_WC_ECPay_Gateway::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, []);
            }
        }
        return false;
    }

    public function doing_callback($ipn_info)
    {
        $order_ID = $this->get_order_id($ipn_info, RY_WEI::get_option('order_prefix', ''));
        if ($order = wc_get_order($order_ID)) {
            if (isset($ipn_info['inv_error']) && !empty($ipn_info['inv_error'])) {
                $order->add_order_note(sprintf(
                    /* translators: %s Error messade */
                    __('Issue invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                    $ipn_info['inv_error'],
                ));
            } else {
                if (isset($ipn_info['invoicenumber'], $ipn_info['invoicecode'], $ipn_info['invoicedate'], $ipn_info['invoicetime'])) {
                    if (!empty($ipn_info['invoicenumber']) && $order->get_meta('_invoice_ecpay_RelateNumber') == $ipn_info['od_sob']) {
                        if (apply_filters('ry_wei_add_api_success_notice', true)) {
                            $order->add_order_note(
                                __('Invoice number', 'ry-woocommerce-ecpay-invoice') . ': ' . $ipn_info['invoicenumber'] . "\n"
                                . __('Invoice random number', 'ry-woocommerce-ecpay-invoice') . ': ' . $ipn_info['invoicecode'] . "\n"
                                . __('Invoice create time', 'ry-woocommerce-ecpay-invoice') . ': ' . $ipn_info['invoicedate'] . ' ' . $ipn_info['invoicetime'] . "\n",
                            );
                        }

                        $order->update_meta_data('_invoice_number', $ipn_info['invoicenumber']);
                        $order->update_meta_data('_invoice_random_number', $ipn_info['invoicecode']);
                        $order->update_meta_data('_invoice_date', $ipn_info['invoicedate'] . ' ' . $ipn_info['invoicetime']);
                        $order->save();
                        $this->die_success();
                    }
                }
            }
        }
        $this->die_error();
    }
}
