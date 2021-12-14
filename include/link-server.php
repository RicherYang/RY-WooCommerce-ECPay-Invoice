<?php
final class RY_WEI_LinkServer
{
    private static $api_url = 'https://ry-plugin.com/wp-json/ry/v2/';
    private static $plugin_type = 'ry-woocommerce-ecpay-invoice';

    public static function check_version()
    {
        $response = wp_remote_get(self::$api_url . 'products/' . self::$plugin_type, [
            'timeout' => 3,
            'httpversion' => '1.1',
            'user-agent' => self::get_user_agent()
        ]);

        return self::decode_response($response);
    }

    public static function activate_key()
    {
        $response = wp_remote_post(self::$api_url . 'license/activate/' . self::$plugin_type, [
            'httpversion' => '1.1',
            'user-agent' => self::get_user_agent(),
            'headers' => [
                'Content-Type' => 'application/json;charset=' . get_bloginfo('charset'),
            ],
            'body' => wp_json_encode([
                'license_key' => RY_WEI_License::get_license_key(),
                'domain' => get_site_url()
            ])
        ]);

        return self::decode_response($response);
    }

    public static function expire_data()
    {
        $response = wp_remote_post(self::$api_url . 'license/expire/' . self::$plugin_type, [
            'httpversion' => '1.1',
            'user-agent' => self::get_user_agent(),
            'headers' => [
                'Content-Type' => 'application/json;charset=' . get_bloginfo('charset'),
            ],
            'body' => wp_json_encode([
                'domain' => get_site_url()
            ])
        ]);

        return self::decode_response($response);
    }

    protected static function decode_response($response)
    {
        if (is_wp_error($response)) {
            return false;
        }

        if (200 != wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return @json_decode($body, true);
    }

    protected static function get_user_agent()
    {
        return sprintf('RY_WEI %s (WordPress/%s WooCommerce/%s)', RY_WEI_VERSION, get_bloginfo('version'), WC_VERSION);
    }
}
