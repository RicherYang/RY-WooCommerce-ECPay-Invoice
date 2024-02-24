<?php

final class RY_WEI
{
    public const OPTION_PREFIX = 'RY_WEI_';

    public static $options = [];

    private static $initiated = false;
    private static $activate_status = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            load_plugin_textdomain('ry-woocommerce-ecpay-invoice', false, plugin_basename(dirname(RY_WEI_PLUGIN_BASENAME)) . '/languages');

            if (!defined('WC_VERSION')) {
                return;
            }

            include_once RY_WEI_PLUGIN_DIR . 'include/license.php';
            include_once RY_WEI_PLUGIN_DIR . 'include/link-server.php';
            include_once RY_WEI_PLUGIN_DIR . 'include/updater.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/notes/license-auto-deactivate.php';

            self::$activate_status = RY_WEI_License::valid_key();

            include_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.update.php';
            RY_WEI_update::update();

            if (is_admin()) {
                include_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.admin.php';
                if (!self::$activate_status) {
                    add_action('woocommerce_settings_start', [RY_WEI_admin::instance(), 'add_license_notice']);
                }
            }

            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/invoice-basic.php';
            if (self::$activate_status) {
                include_once RY_WEI_PLUGIN_DIR . 'include/cron.php';
                include_once RY_WEI_PLUGIN_DIR . 'woocommerce/settings/class-settings.invoice.php';

                if ('yes' === self::get_option('enabled_invoice', 'no')) {
                    include_once RY_WEI_PLUGIN_DIR . 'woocommerce/invoice.php';
                }
            }
        }
    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::OPTION_PREFIX . $option, $default);
    }

    public static function update_option($option, $value, $autoload = null): bool
    {
        return update_option(self::OPTION_PREFIX . $option, $value, $autoload);
    }

    public static function delete_option($option): bool
    {
        return delete_option(self::OPTION_PREFIX . $option);
    }

    public static function get_transient($transient)
    {
        return get_transient(self::OPTION_PREFIX . $transient);
    }

    public static function set_transient($transient, $value, $expiration = 0)
    {
        return set_transient(self::OPTION_PREFIX . $transient, $value, $expiration);
    }

    public static function delete_transient($transient)
    {
        return delete_transient(self::OPTION_PREFIX . $transient);
    }

    public static function plugin_activation() {}

    public static function plugin_deactivation()
    {
        wp_unschedule_hook(self::OPTION_PREFIX . 'check_expire');
        wp_unschedule_hook(self::OPTION_PREFIX . 'check_update');
    }
}
