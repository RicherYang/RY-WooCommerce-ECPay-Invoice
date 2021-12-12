<?php
final class RY_WEI
{
    public static $options = [];
    public static $option_prefix = 'RY_WEI_';

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

            self::$activate_status = RY_WEI_License::valid_key();

            include_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.update.php';
            RY_WEI_update::update();

            if (is_admin()) {
                include_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.admin.php';
            }

            if (self::$activate_status) {
                include_once RY_WEI_PLUGIN_DIR . 'include/cron.php';
                include_once RY_WEI_PLUGIN_DIR . 'woocommerce/settings/class-settings.invoice.php';

                if ('yes' == self::get_option('enabled_invoice', 'no')) {
                    include_once RY_WEI_PLUGIN_DIR . 'woocommerce/class.invoice.php';
                }
            }
        }
    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::$option_prefix . $option, $default);
    }

    public static function update_option($option, $value)
    {
        return update_option(self::$option_prefix . $option, $value);
    }

    public static function delete_option($option)
    {
        return delete_option(self::$option_prefix . $option);
    }

    public static function plugin_activation()
    {
    }

    public static function plugin_deactivation()
    {
        wp_unschedule_hook(self::$option_prefix . 'check_expire');
        wp_unschedule_hook(self::$option_prefix . 'check_update');
    }
}
