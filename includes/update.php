<?php

defined('ABSPATH') or exit;

final class RY_WEI_update
{
    public static function update()
    {
        $now_version = RY_WEI::get_option('version');

        if (false === $now_version) {
            $now_version = '0.0.0';
        }
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

        if (version_compare($now_version, '2.2.6', '<')) {
            if (RY_WEI::get_option('ecpay_MerchantID') !== false) {
                RY_WEI::update_option('apiinfo', [
                    'prefix' => RY_WEI::get_option('order_prefix'),
                    'company_carruer0' => RY_WEI::get_option('company_carruer_mode'),
                    'use_sku' => RY_WEI::get_option('use_sku_as_name'),
                    'abnormal_mode' => RY_WEI::get_option('amount_abnormal_mode'),
                    'abnormal_product' => RY_WEI::get_option('amount_abnormal_product'),
                    'trackcode' => RY_WEI::get_option('used_track'),
                    'testmode' => RY_WEI::get_option('ecpay_invoice_testmode'),
                    'MerchantID' => RY_WEI::get_option('ecpay_MerchantID'),
                    'HashKey' => RY_WEI::get_option('ecpay_HashKey'),
                    'HashIV' => RY_WEI::get_option('ecpay_HashIV'),
                ], false);
                RY_WEI::delete_option('order_prefix');
                RY_WEI::delete_option('company_carruer_mode');
                RY_WEI::delete_option('use_sku_as_name');
                RY_WEI::delete_option('amount_abnormal_mode');
                RY_WEI::delete_option('amount_abnormal_product');
                RY_WEI::delete_option('used_track');
                RY_WEI::delete_option('ecpay_invoice_testmode');
                RY_WEI::delete_option('ecpay_HashKey');
                RY_WEI::delete_option('ecpay_HashIV');
            }
            if (RY_WEI::get_option('skip_foreign_order') !== false) {
                RY_WEI::update_option('skip_foreign_order', RY_WEI::get_option('skip_foreign_order'), true);
            }
            RY_WEI::delete_option('invoice_log');
            RY_WEI::delete_option('ecpay_testmode');

            RY_WEI::update_option('version', '2.2.6', true);
        }
    }
}
