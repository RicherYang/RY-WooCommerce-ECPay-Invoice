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
    }

    public function get_settings_page($settings)
    {
        $settings[] = include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/settings/ry-tools-settings.php';

        return $settings;
    }
}
