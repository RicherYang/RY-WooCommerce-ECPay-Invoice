<?php

defined('ABSPATH') or exit;

final class RY_WEI_update
{
    public static function update()
    {
        $now_version = RY_WEI::get_option('version', '0.0.0');

        if (RY_WEI_VERSION === $now_version) {
            return;
        }

        if (version_compare($now_version, '2.0.0', '<')) {
            wp_unschedule_hook(RY_WEI::OPTION_PREFIX . 'check_update');
            RY_WEI::update_option('ecpay_invoice_log', RY_WEI::update_option('invoice_log', 'no'), true);
            RY_WEI::update_option('ecpay_invoice_testmode', RY_WEI::update_option('ecpay_testmode', 'no'), true);

            RY_WEI::update_option('version', '2.0.0', true);
        }

        if (version_compare($now_version, '2.0.1', '<')) {
            RY_WEI::delete_option('enabled_invoice');

            RY_WEI::update_option('version', '2.0.1', true);
        }

        if (version_compare($now_version, '2.2.5', '<')) {
            if (RY_WEI::get_option('ecpay_MerchantID') !== false) {
                RY_WEI::update_option('apikey', [
                    'MerchantID' => RY_WEI::get_option('ecpay_MerchantID'),
                    'HashKey' => RY_WEI::get_option('ecpay_HashKey'),
                    'HashIV' => RY_WEI::get_option('ecpay_HashIV'),
                ], false);
                RY_WEI::delete_option('ecpay_MerchantID');
                RY_WEI::delete_option('ecpay_HashKey');
                RY_WEI::delete_option('ecpay_HashIV');
            }
            if (RY_WEI::get_option('ecpay_invoice_testmode') !== false) {
                RY_WEI::update_option('testmode', RY_WEI::get_option('ecpay_invoice_testmode'));
                RY_WEI::delete_option('ecpay_invoice_testmode');
            }

            RY_WEI::update_option('version', '2.2.5', true);
        }
    }
}
