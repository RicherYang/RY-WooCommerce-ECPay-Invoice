<?php
defined('RY_WEI_VERSION') or exit('No direct script access allowed');

final class RY_WEI_admin
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            if (!defined('RY_WT_VERSION')) {
                add_filter('woocommerce_get_settings_pages', [__CLASS__, 'get_settings_page']);
            }
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections'], 12);

            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_ry_key', [__CLASS__, 'activate_key']);
            add_action('woocommerce_update_options_rytools_ecpay_invoice', [__CLASS__, 'check_option']);
        }
    }

    public static function get_settings_page($settings)
    {
        $settings[] = include(RY_WEI_PLUGIN_DIR . 'woocommerce/settings/class-settings-ry-wei.php');

        return $settings;
    }

    public static function add_sections($sections)
    {
        unset($sections['ry_key']);
        $sections['ry_key'] = __('License key', 'ry-woocommerce-ecpay-invoice');

        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'ry_key') {
            add_action('woocommerce_admin_field_rywei_version_info', [__CLASS__, 'show_version_info']);
            if (empty($settings)) {
                $settings = [];
            }
            $settings = array_merge($settings, include(RY_WEI_PLUGIN_DIR . 'woocommerce/settings/settings-ry-key.php'));

            $pro_data = RY_WEI::get_option('pro_Data');
            if (is_array($pro_data) && isset($pro_data['expire'])) {
                foreach ($settings as $key => $setting) {
                    if (isset($setting['id']) && $setting['id'] == RY_WEI::$option_prefix . 'pro_Key') {
                        $settings[$key]['desc'] = sprintf(
                            /* translators: %s: Expiration date of pro license */
                            __('License Expiration Date %s', 'ry-woocommerce-ecpay-invoice'),
                            date_i18n(get_option('date_format'), $pro_data['expire'])
                        );
                    }
                }
            }
        }
        return $settings;
    }

    public static function show_version_info($value)
    {
        $version = RY_WEI::get_option('version');
        $version_info = RY_WEI_link_server::check_version();

        include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/view/html-version-info.php';
    }

    public static function activate_key()
    {
        if (!empty(RY_WEI::get_option('pro_Key'))) {
            $json = RY_WEI_link_server::activate_key();

            if ($json === false) {
                WC_Admin_Settings::add_error(__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') . ': '
                    . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
            } else {
                if (is_array($json) && isset($json['data'])) {
                    if (empty($json['data'])) {
                        WC_Admin_Settings::add_error(__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') . ': '
                            . sprintf(
                                /* translators: %s: Error message */
                                __('Verification error: %s', 'ry-woocommerce-ecpay-invoice'),
                                __($json['error'], 'ry-woocommerce-ecpay-invoice')
                            ));

                        /* Error message list. For make .pot */
                        __('Unknown key', 'ry-woocommerce-ecpay-invoice');
                        __('Locked key', 'ry-woocommerce-ecpay-invoice');
                        __('Unknown target url', 'ry-woocommerce-ecpay-invoice');
                        __('Used key', 'ry-woocommerce-ecpay-invoice');
                        __('Is tried', 'ry-woocommerce-ecpay-invoice');
                    } else {
                        RY_WEI::update_option('pro_Data', $json['data']);
                        return true;
                    }
                } else {
                    WC_Admin_Settings::add_error__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') . ': '
                    . (__('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
                }
            }
        }

        RY_WEI::check_expire();
        RY_WEI::update_option('pro_Key', '');
    }

    public static function check_option()
    {
        if ('yes' == RY_WEI::get_option('enabled_invoice', 'no')) {
            if ('yes' != RY_WEI::get_option('ecpay_testmode', 'yes')) {
                if (empty(RY_WEI::get_option('ecpay_MerchantID')) || empty(RY_WEI::get_option('ecpay_HashKey')) || empty(RY_WEI::get_option('ecpay_HashIV'))) {
                    WC_Admin_Settings::add_error(__('ECPay invoice method failed to enable!', 'ry-woocommerce-ecpay-invoice'));
                    RY_WEI::update_option('enabled_invoice', 'no');
                }
            }

            if (!is_callable('openssl_encrypt') || !is_callable('openssl_decrypt')) {
                WC_Admin_Settings::add_error(__('ECPay invoice method failed to enable!', 'ry-woocommerce-ecpay-invoice')
                    . __('Required PHP function openssl_encrypt and openssl_decrypt.', 'ry-woocommerce-ecpay-invoice'));
                RY_WEI::update_option('enabled_invoice', 'no');
            }
        }

        if (!preg_match('/^[a-z0-9]*$/i', RY_WEI::get_option('order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed allowed', 'ry-woocommerce-ecpay-invoice'));
            RY_WEI::update_option('order_prefix', '');
        }

        $delay_days = RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days < 0 || $delay_days > 15) {
            WC_Admin_Settings::add_error(__('Delay day only can between 0 and 15 days.', 'ry-woocommerce-ecpay-invoice'));
            RY_WEI::update_option('get_delay_days', 0);
        }
    }
}

RY_WEI_admin::init();
