<?php

final class RY_WEI_Updater
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'transient_update_plugins']);

            add_filter('plugins_api', [__CLASS__, 'modify_plugin_details'], 10, 3);
        }
    }

    public static function check_update()
    {
        $time = (int) get_site_transient(RY_WEI::$option_prefix . 'checktime');
        if (HOUR_IN_SECONDS < time() - $time) {
            $update_plugins = get_site_transient('update_plugins');
            set_site_transient('update_plugins', $update_plugins);
        }
    }

    public static function transient_update_plugins($transient)
    {
        $json = RY_WEI_LinkServer::check_version();

        if (is_array($json) && isset($json['new_version'])) {
            set_site_transient(RY_WEI::$option_prefix . 'checktime', time());

            if (version_compare(RY_WEI_VERSION, $json['new_version'], '<')) {
                unset($json['version']);
                unset($json['url']);
                $json['slug'] = 'ry-woocommerce-ecpay-invoice';
                $json['plugin'] = RY_WEI_PLUGIN_BASENAME;

                if (empty($transient)) {
                    $transient = new stdClass();
                }
                $transient->last_checked = time();
                $transient->response[RY_WEI_PLUGIN_BASENAME] = (object) $json;
            } else {
                if (isset($transient->response)) {
                    unset($transient->response[RY_WEI_PLUGIN_BASENAME]);
                }
            }
        }

        return $transient;
    }

    public static function modify_plugin_details($result, $action, $args)
    {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if ($args->slug != 'ry-woocommerce-ecpay-invoice') {
            return $result;
        }

        $response = RY_WEI_LinkServer::get_info();
        if (!empty($response)) {
            return (object) $response;
        }

        return $result;
    }
}

RY_WEI_Updater::init();
