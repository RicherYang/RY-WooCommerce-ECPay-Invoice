<?php

final class RY_WEI_WC_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_WC_Admin
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

        add_action('woocommerce_settings_start', [$this, 'add_license_notice']);

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections'], 12);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_ry_key', [$this, 'activate_key']);
    }

    public function get_settings_page($settings)
    {
        $settings[] = include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/settings/ry-tools-settings.php';

        return $settings;
    }

    public function add_license_notice(): void
    {
        global $current_section, $current_tab;

        if ('rytools' === $current_tab && 'ry_key' === $current_section) {
            return;
        }

        if (!RY_WEI_License::instance()->is_activated()) {
            echo '<div class="notice notice-info"><p><strong>RY ECPay Invoice for WooCommerce</strong> ' . esc_html__('Your license is not active!', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }
    }

    public function add_sections($sections)
    {
        unset($sections['ry_key']);
        $sections['ry_key'] = __('License key', 'ry-woocommerce-ecpay-invoice');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ('ry_key' === $current_section) {
            add_action('woocommerce_admin_field_ry_wei_version_info', [$this, 'show_version_info']);
            if (empty($settings)) {
                $settings = [];
            }
            $settings = array_merge($settings, include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/settings/settings-ry-key.php');

            $expire = RY_WEI_License::instance()->get_expire();
            if (!empty($expire)) {
                $setting_idx = array_search(RY_WEI::OPTION_PREFIX . 'license_key', array_column($settings, 'id'));
                $settings[$setting_idx]['desc'] = sprintf(
                    /* translators: %s: Expiration date of pro license */
                    __('License Expiration Date %s', 'ry-woocommerce-ecpay-invoice'),
                    date_i18n(get_option('date_format'), $expire),
                );
            }
        }

        return $settings;
    }

    public function show_version_info()
    {
        $version = RY_WEI::get_option('version');
        $version_info = RY_WEI::get_transient('version_info');
        if (empty($version_info)) {
            $version_info = RY_WEI_LinkServer::instance()->check_version();
            if ($version_info) {
                RY_WEI::set_transient('version_info', $version_info, HOUR_IN_SECONDS);
            }
        }

        include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/view/html-version-info.php';
    }

    public function activate_key()
    {
        if (!empty(RY_WEI_License::instance()->get_license_key())) {
            RY_WEI::delete_transient('version_info');
            $json = RY_WEI_LinkServer::instance()->activate_key();

            if (false === $json) {
                WC_Admin_Settings::add_error('RY ECPay Invoice for WooCommerce: ' . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
            } else {
                if (is_array($json)) {
                    if (empty($json['data'])) {
                        RY_WEI_License::instance()->delete_license();
                        WC_Admin_Settings::add_error('RY ECPay Invoice for WooCommerce: '
                            . sprintf(
                                /* translators: %s: Error message */
                                __('Verification error: %s', 'ry-woocommerce-ecpay-invoice'),
                                rywei_link_error_to_msg($json['error']),
                            ));
                    } else {
                        RY_WEI_License::instance()->set_license_data($json['data']);
                        return true;
                    }
                } else {
                    WC_Admin_Settings::add_error('RY ECPay Invoice for WooCommerce: ' . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
                }
            }
        }

        RY_WEI_License::instance()->delete_license_key();
    }
}
