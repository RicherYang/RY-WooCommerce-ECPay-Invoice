<?php

final class RY_WEI_admin
{
    protected static $_instance = null;

    public static function instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init()
    {
        if (!defined('RY_WT_VERSION')) {
            add_filter('woocommerce_get_settings_pages', [$this, 'get_settings_page']);
        }

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections'], 12);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_ry_key', [$this, 'activate_key']);
    }

    public function get_settings_page($settings)
    {
        $settings[] = include RY_WEI_PLUGIN_DIR . 'woocommerce/settings/class-settings-ry-wei.php';

        return $settings;
    }

    public function add_license_notice(): void
    {
        global $current_section, $current_tab;

        if ('rytools' === $current_tab  && 'ry_key' === $current_section) {
            return ;
        }

        echo '<div class="notice notice-info"><p><strong>RY WooCommerce ECPay Invoice</strong> ' . __('Your license is not active!', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
    }

    public function add_sections($sections)
    {
        unset($sections['ry_key']);
        $sections['ry_key'] = __('License key', 'ry-woocommerce-ecpay-invoice');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'ry_key') {
            add_action('woocommerce_admin_field_ry_wei_version_info', [$this, 'show_version_info']);
            if (empty($settings)) {
                $settings = [];
            }
            $settings = array_merge($settings, include RY_WEI_PLUGIN_DIR . 'woocommerce/settings/settings-ry-key.php');

            $expire = RY_WEI_License::get_expire();
            if (!empty($expire)) {
                foreach ($settings as $key => $setting) {
                    if (isset($setting['id']) && $setting['id'] == RY_WEI::$option_prefix . 'license_key') {
                        $settings[$key]['desc'] = sprintf(
                            /* translators: %s: Expiration date of pro license */
                            __('License Expiration Date %s', 'ry-woocommerce-ecpay-invoice'),
                            date_i18n(get_option('date_format'), $expire)
                        );
                        break;
                    }
                }
            }
        }
        return $settings;
    }

    public function show_version_info()
    {
        $version = RY_WEI::get_option('version');
        $version_info = RY_WEI::get_transient('version_info');
        if (empty($version_info)) {
            $version_info = RY_WEI_LinkServer::check_version();
            if ($version_info) {
                RY_WEI::set_transient('version_info', $version_info, HOUR_IN_SECONDS);
            }
        }

        include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/view/html-version-info.php';
    }

    public function activate_key()
    {
        if (!empty(RY_WEI_License::get_license_key())) {
            RY_WEI::delete_transient('version_info');

            $json = RY_WEI_LinkServer::activate_key();

            if (false === $json) {
                WC_Admin_Settings::add_error('RY WooCommerce ECPay Invoice: '
                    . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
            } else {
                if (is_array($json)) {
                    if (empty($json['data'])) {
                        RY_WEI_License::delete_license();
                        WC_Admin_Settings::add_error('RY WooCommerce ECPay Invoice: '
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
                        RY_WEI_License::set_license_data($json['data']);
                        return true;
                    }
                } else {
                    WC_Admin_Settings::add_error('RY WooCommerce ECPay Invoice: '
                        . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
                }
            }
        }

        RY_WEI_License::delete_license_key();
    }
}

RY_WEI_admin::instance();
