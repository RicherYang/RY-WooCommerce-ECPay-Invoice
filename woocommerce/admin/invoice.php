<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

final class RY_WEI_WC_Admin_Invoice
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_WC_Admin_Invoice
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init()
    {
        include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/class-wc-meta-box-invoice-data.php';
        include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/settings/invoice.php';
        RY_WEI_WC_Admin_Setting_Invoice::instance();

        add_filter('enable_ry_invoice', [$this, 'add_enable_ry_invoice']);
        add_action('admin_enqueue_scripts', [$this, 'add_scripts']);

        add_action('woocommerce_admin_order_data_after_billing_address', ['WRY_WEI_MetaBox_Invoice_Data', 'output']);
        add_action('woocommerce_update_order', [$this, 'save_order_update']);

        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled()) {
            if ('edit' !== ($_GET['action'] ?? '')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended , WordPress.Security.ValidatedSanitizedInput.MissingUnslash , WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_invoice_column'], 11);
                add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'show_invoice_column'], 11, 2);
            }
        } else {
            add_filter('manage_shop_order_posts_columns', [$this, 'add_invoice_column'], 11);
            add_action('manage_shop_order_posts_custom_column', [$this, 'show_invoice_column'], 11, 2);
        }
    }

    public function add_enable_ry_invoice($enable)
    {
        $enable[] = 'ecpay';

        return $enable;
    }

    public function add_scripts()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if (in_array($screen_id, ['shop_order', 'edit-shop_order', 'woocommerce_page_wc-settings', 'woocommerce_page_wc-orders'])) {
            $asset_info = include RY_WEI_PLUGIN_DIR . 'assets/admin/ry-invoice.asset.php';
            wp_enqueue_script('ry-wei-admin-invoice', RY_WEI_PLUGIN_URL . 'assets/admin/ry-invoice.js', $asset_info['dependencies'], $asset_info['version'], true);
            wp_enqueue_style('ry-wei-admin-invoice', RY_WEI_PLUGIN_URL . 'assets/admin/ry-invoice.css', [], $asset_info['version']);

            wp_localize_script('ry-wei-admin-invoice', 'RyWeiAdminInvoiceParams', [
                'i18n' => [
                    'get' => __('Issue invoice.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                    'invalid' => __('Invalid invoice.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                    'cancel' => __('Cancel delay invoice data.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                ],
                '_nonce' => [
                    'get' => wp_create_nonce('get-invoice'),
                    'invalid' => wp_create_nonce('invalid-invoice'),
                    'cancel' => wp_create_nonce('cancel-invoice'),
                ],
            ]);
        }
    }

    public function save_order_update($order_ID)
    {
        if ($order = wc_get_order($order_ID)) {
            if (isset($_POST['_invoice_type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                remove_action('woocommerce_update_order', [$this, 'save_order_update']);
                $order->update_meta_data('_invoice_type', strtolower(sanitize_locale_name($_POST['_invoice_type'] ?? ''))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $order->update_meta_data('_invoice_carruer_type', strtolower(sanitize_locale_name($_POST['_invoice_carruer_type'] ?? ''))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $order->update_meta_data('_invoice_carruer_no', strtoupper(sanitize_locale_name($_POST['_invoice_carruer_no'] ?? ''))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $order->update_meta_data('_invoice_no', strtolower(sanitize_locale_name($_POST['_invoice_no'] ?? ''))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $order->update_meta_data('_invoice_donate_no', strtolower(sanitize_locale_name($_POST['_invoice_donate_no'] ?? ''))); // phpcs:ignore WordPress.Security.NonceVerification.Missing

                $invoice_number = strtoupper(sanitize_locale_name($_POST['_invoice_number'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                if (!empty($invoice_number)) {
                    $order->update_meta_data('_invoice_number', $invoice_number);
                    $order->update_meta_data('_invoice_random_number', strtolower(sanitize_locale_name($_POST['_invoice_random_number'] ?? ''))); // phpcs:ignore WordPress.Security.NonceVerification.Missing

                    $date = strtolower(sanitize_locale_name($_POST['_invoice_date'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $hour = intval($_POST['_invoice_date_hour'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $minute = intval($_POST['_invoice_date_minute'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $second = intval($_POST['_invoice_date_second'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $date = gmdate('Y-m-d H:i:s', strtotime($date . ' ' . $hour . ':' . $minute . ':' . $second));
                    $order->update_meta_data('_invoice_date', $date);
                }
                $order->save();
                add_action('woocommerce_update_order', [$this, 'save_order_update']);
            }
        }
    }

    public function add_invoice_column($columns)
    {
        if (!isset($columns['invoice-number'])) {
            $add_index = array_search('order_status', array_keys($columns)) + 1;
            $pre_array = array_splice($columns, 0, $add_index);
            $array = [
                'invoice-number' => __('Invoice number', 'ry-woocommerce-ecpay-invoice'),
            ];
            $columns = array_merge($pre_array, $array, $columns);
        }
        return $columns;
    }

    public function show_invoice_column($column, $order)
    {
        if ('invoice-number' == $column) {
            if (!is_object($order)) {
                global $the_order;
                $order = $the_order;
            }

            $invoice_number = $order->get_meta('_invoice_number');
            if ('zero' == $invoice_number) {
                esc_html_e('Zero no invoice', 'ry-woocommerce-ecpay-invoice');
            } elseif ('negative' == $invoice_number) {
                esc_html_e('Negative no invoice', 'ry-woocommerce-ecpay-invoice');
            } elseif ('delay' == $invoice_number) {
                esc_html_e('Delay get invoice', 'ry-woocommerce-ecpay-invoice');
            } else {
                echo esc_html($order->get_meta('_invoice_number'));
            }
        }
    }
}
