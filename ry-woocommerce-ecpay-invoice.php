<?php

/**
 * Plugin Name: RY ECPay Invoice for WooCommerce
 * Plugin URI: https://ry-plugin.com/ry-woocommerce-ecpay-invoice
 * Description: WooCommerce order invoice for ecpay
 * Version: 2.0.9
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Author: Richer Yang
 * Author URI: https://richer.tw/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Update URI: https://ry-plugin.com/ry-woocommerce-ecpay-invoice
 *
 * Text Domain: ry-woocommerce-ecpay-invoice
 * Domain Path: /languages
 *
 * WC requires at least: 8
 */

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WEI_VERSION', '2.0.9');
define('RY_WEI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WEI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WEI_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('RY_WEI_PLUGIN_LANGUAGES_DIR', plugin_dir_path(__FILE__) . '/languages');

require_once RY_WEI_PLUGIN_DIR . 'includes/main.php';

register_activation_hook(__FILE__, ['RY_WEI', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WEI', 'plugin_deactivation']);

function RY_WEI(): RY_WEI
{
    return RY_WEI::instance();
}

RY_WEI();
