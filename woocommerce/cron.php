<?php

final class RY_WEI_WC_Cron
{
    public static function add_action()
    {
        add_action('ry_wei_auto_get_invoice', [__CLASS__, 'get_invoice'], 10, 2);
        add_action('ry_wei_auto_get_delay_invoice', [__CLASS__, 'get_delay_invoice'], 10, 2);

        add_action('ry_wei_auto_cancel_invoice', [__CLASS__, 'cancel_invoice']);
        add_action('ry_wei_auto_invalid_invoice', [__CLASS__, 'invalid_invoice']);
    }

    public static function get_invoice($order_ID)
    {
        RY_WEI_WC_Invoice_Api::instance()->get($order_ID);
    }

    public static function get_delay_invoice($order_ID)
    {
        RY_WEI_WC_Invoice_Api::instance()->get_delay($order_ID);
    }

    public static function cancel_invoice($order_ID)
    {
        RY_WEI_WC_Invoice_Api::instance()->cancel_delay($order_ID);
    }

    public static function invalid_invoice($order_ID)
    {
        RY_WEI_WC_Invoice_Api::instance()->invalid($order_ID);
    }
}
