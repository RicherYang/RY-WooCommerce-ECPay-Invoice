<?php

final class RY_WEI_Cron
{
    public static function add_action(): void
    {
        add_action(RY_WEI::OPTION_PREFIX . 'check_expire', [__CLASS__, 'check_expire']);

        add_action(RY_WEI::OPTION_PREFIX . 'auto_get_invoice', [__CLASS__, 'get_invoice'], 10, 2);
        add_action(RY_WEI::OPTION_PREFIX . 'auto_get_delay_invoice', [__CLASS__, 'get_delay_invoice'], 10, 2);
        add_action(RY_WEI::OPTION_PREFIX . 'auto_cancel_invoice', [__CLASS__, 'cancel_invoice']);
        add_action(RY_WEI::OPTION_PREFIX . 'auto_invalid_invoice', [__CLASS__, 'invalid_invoice']);

        add_action('ry_wei_auto_get_invoice', [__CLASS__, 'get_invoice'], 10, 2);
        add_action('ry_wei_auto_get_delay_invoice', [__CLASS__, 'get_delay_invoice'], 10, 2);
        add_action('ry_wei_auto_cancel_invoice', [__CLASS__, 'cancel_invoice']);
        add_action('ry_wei_auto_invalid_invoice', [__CLASS__, 'invalid_invoice']);
    }

    public static function check_expire(): void
    {
        RY_WEI_License::instance()->check_expire();
    }

    public static function get_invoice($order_ID): void
    {
        RY_WEI_WC_Invoice_Api::instance()->get($order_ID);
    }

    public static function get_delay_invoice($order_ID): void
    {
        RY_WEI_WC_Invoice_Api::instance()->get_delay($order_ID);
    }

    public static function cancel_invoice($order_ID): void
    {
        RY_WEI_WC_Invoice_Api::instance()->cancel_delay($order_ID);
    }

    public static function invalid_invoice($order_ID): void
    {
        RY_WEI_WC_Invoice_Api::instance()->invalid($order_ID);
    }
}
