<?php

final class RY_WEI_WC_Admin_Ajax
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_WC_Admin_Ajax
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init()
    {
        add_action('wp_ajax_RY_WEI_get', [$this, 'get_invoice']);
        add_action('wp_ajax_RY_WEI_invalid', [$this, 'invalid_invoice']);
        add_action('wp_ajax_RY_WEI_cancel_delay', [$this, 'cancel_delay_invoice']);
    }

    public function get_invoice()
    {
        check_ajax_referer('get-invoice');

        $order_ID = (int) wp_unslash($_POST['id'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $order = wc_get_order($order_ID);
        if ($order) {
            RY_WEI_WC_Invoice_Api::instance()->get($order);
        }

        wp_die();
    }

    public function invalid_invoice()
    {
        check_ajax_referer('invalid-invoice');

        $order_ID = (int) wp_unslash($_POST['id'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $order = wc_get_order($order_ID);
        if ($order) {
            RY_WEI_WC_Invoice_Api::instance()->invalid($order);
        }

        wp_die();
    }

    public function cancel_delay_invoice()
    {
        check_ajax_referer('cancel-invoice');

        $order_ID = (int) wp_unslash($_POST['id'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $order = wc_get_order($order_ID);
        if ($order) {
            RY_WEI_WC_Invoice_Api::instance()->cancel_delay($order);
        }

        wp_die();
    }
}
