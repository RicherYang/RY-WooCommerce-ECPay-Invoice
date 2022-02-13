<?php
/**
 * Plugin Name: RY WooCommerce ECPay Invoice
 * Plugin URI: https://ry-plugin.com/ry-woocommerce-ecpay-invoice
 * Version: 1.3.8
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: Richer Yang
 * Author URI: https://richer.tw/
 * License: GPLv2 or later
 *
 * Text Domain: ry-woocommerce-ecpay-invoice
 * Domain Path: /languages
 *
 * WC requires at least: 5
 * WC tested up to: 6.2.0
 */

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WEI_VERSION', '1.3.8');
define('RY_WEI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WEI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WEI_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.main.php';

register_activation_hook(__FILE__, ['RY_WEI', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WEI', 'plugin_deactivation']);

add_action('init', ['RY_WEI', 'init'], 11);
