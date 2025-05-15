<?php

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

        if (version_compare($now_version, '2.0.7', '<')) {
            RY_WEI::update_option('version', '2.0.7', true);
        }
    }
}
