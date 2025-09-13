<?php

include_once RY_WEI_PLUGIN_DIR . 'includes/ry-global/abstract-link-server.php';

final class RY_WEI_LinkServer extends RY_Abstract_Link_Server
{
    protected static $_instance = null;

    protected $plugin_slug = 'ry-woocommerce-ecpay-invoice';

    public static function instance(): RY_WEI_LinkServer
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function get_user_agent()
    {
        return sprintf(
            'RY_WEI %s (WordPress/%s WooCommerce/%s)',
            RY_WEI_VERSION,
            get_bloginfo('version'),
            WC_VERSION,
        );
    }
}
