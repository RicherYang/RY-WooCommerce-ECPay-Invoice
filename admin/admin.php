<?php

include_once RY_WEI_PLUGIN_DIR . 'includes/ry-global/abstract-admin.php';

final class RY_WEI_Admin extends RY_Abstract_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        parent::do_init();

        $this->license = RY_WEI_License::instance();
        add_filter('ry-plugin/license_list', [$this, 'add_license']);

        if ($this->license->is_activated()) {
            $this->license->check_expire_cron();
        }
    }

    public function add_license($license_list): array
    {
        $license_list[] = [
            'name' => $this->license::$main_class::PLUGIN_NAME,
            'license' => $this->license,
            'version' => RY_WEI_VERSION,
            'basename' => RY_WEI_PLUGIN_BASENAME,
        ];

        return $license_list;
    }
}
