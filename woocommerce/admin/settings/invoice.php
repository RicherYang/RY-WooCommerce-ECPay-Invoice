<?php

final class RY_WEI_WC_Admin_Setting_Invoice
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_WC_Admin_Setting_Invoice
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init()
    {
        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections'], 11);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_ecpay_invoice', [$this, 'check_option']);
        add_filter('ry_setting_section_tools', '__return_false');
        add_action('ry_setting_section_ouput_tools', [$this, 'output_tools'], 11);
    }

    public function add_sections($sections)
    {
        if (isset($sections['tools'])) {
            $add_idx = array_search('tools', array_keys($sections));
            $sections = array_slice($sections, 0, $add_idx) + [
                'ecpay_invoice' => __('ECPay invoice', 'ry-woocommerce-ecpay-invoice'),
            ] + array_slice($sections, $add_idx);
        } else {
            $sections['ecpay_invoice'] = __('ECPay invoice', 'ry-woocommerce-ecpay-invoice');
            $sections['tools'] = __('Tools', 'ry-woocommerce-ecpay-invoice');
        }

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ('ecpay_invoice' == $current_section) {
            if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
                echo '<div class="notice notice-error"><p><strong>RY ECPay Invoice for WooCommerce</strong> ' . esc_html__('Required PHP function `openssl_encrypt` and `openssl_decrypt`.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
            }

            $settings = include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/settings/settings-invoice.php';
        }

        return $settings;
    }

    public function check_option()
    {
        $enable_list = apply_filters('enable_ry_invoice', []);
        if (1 == count($enable_list)) {
            if ($enable_list != ['ecpay']) {
                WC_Admin_Settings::add_error(__('Not recommended enable two invoice module/plugin at the same time!', 'ry-woocommerce-ecpay-invoice'));
            }
        } elseif (1 < count($enable_list)) {
            WC_Admin_Settings::add_error(__('Not recommended enable two invoice module/plugin at the same time!', 'ry-woocommerce-ecpay-invoice'));
        }

        if (!RY_WEI_WC_Invoice::instance()->is_testmode()) {
            if (empty(RY_WEI::get_option('ecpay_MerchantID')) || empty(RY_WEI::get_option('ecpay_HashKey')) || empty(RY_WEI::get_option('ecpay_HashIV'))) {
                WC_Admin_Settings::add_error(__('ECPay invoice method failed to enable!', 'ry-woocommerce-ecpay-invoice'));
            }
        }

        if (!preg_match('/^[a-z0-9]*$/i', RY_WEI::get_option('order_prefix', ''))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed', 'ry-woocommerce-ecpay-invoice'));
            RY_WEI::update_option('order_prefix', '');
        }

        $delay_days = (int) RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days < 0 || $delay_days > 15) {
            WC_Admin_Settings::add_error(__('Delay day only can between 1 and 15 days.', 'ry-woocommerce-ecpay-invoice'));
            RY_WEI::update_option('get_delay_days', 0);
        }
    }

    public function output_tools()
    {
        global $hide_save_button;

        $hide_save_button = true;

        if ('ecpay_official_invoice_transfer' === ($_POST['ecpay_official_invoice_transfer'] ?? '')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing , WordPress.Security.ValidatedSanitizedInput.MissingUnslash , WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $this->official_invoice_transfer();

            echo '<div class="updated inline"><p>' . esc_html__('Data transfer complated.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }

        if ('ecpay_official_invoice_transfer_delete' === ($_POST['ecpay_official_invoice_transfer_delete'] ?? '')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing , WordPress.Security.ValidatedSanitizedInput.MissingUnslash , WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $this->official_invoice_transfer_delete();

            echo '<div class="updated inline"><p>' . esc_html__('Data transfer complated.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }

        if ('ecpay_official_invoice_delete' === ($_POST['ecpay_official_invoice_delete'] ?? '')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing , WordPress.Security.ValidatedSanitizedInput.MissingUnslash , WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $this->official_invoice_delete();

            echo '<div class="updated inline"><p>' . esc_html__('Data delete complated.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }

        include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/view/html-setting-tools.php';
    }

    protected function official_invoice_transfer()
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_type', 'personal' FROM $wpdb->postmeta WHERE meta_key = '_billing_invoice_type' AND meta_value = 'p'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_type', 'company' FROM $wpdb->postmeta WHERE meta_key = '_billing_invoice_type' AND meta_value = 'c'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_type', 'donate' FROM $wpdb->postmeta WHERE meta_key = '_billing_invoice_type' AND meta_value = 'd'");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'none' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '0'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'ecpay_host' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '1'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'MOICA' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '2'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'phone_barcode' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '3'");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_no', meta_value FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_num'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_no', meta_value FROM $wpdb->postmeta WHERE meta_key = '_billing_customer_identifier'");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_donate_no', meta_value FROM $wpdb->postmeta WHERE meta_key = '_billing_love_code'");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_number', meta_value FROM $wpdb->postmeta WHERE `meta_key` = '_ecpay_invoice_number' and `meta_value` != ''
                and post_id in (SELECT post_id FROM $wpdb->postmeta WHERE `meta_key` = '_ecpay_invoice_status' and `meta_value` = '1')");
    }

    protected function official_invoice_transfer_delete()
    {
        global $wpdb;

        $key_transfer = [
            '_billing_invoice_type' => '_invoice_type',
            '_billing_carruer_type' => '_invoice_carruer_type',
            '_billing_carruer_num' => '_invoice_carruer_no',
            '_billing_customer_identifier' => '_invoice_no',
            '_billing_love_code' => '_invoice_donate_no',
            '_ecpay_invoice_number' => '_invoice_number',
        ];
        foreach ($key_transfer as $from => $to) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->postmeta, [
                'meta_key' => $to, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            ], [
                'meta_key' => $from, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            ]);
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($wpdb->postmeta, [
            'meta_key' => '_ecpay_invoice_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        ]);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($wpdb->postmeta, [
            'meta_key' => '_invoice_number', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => '', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        ]);

        $type_transfer = [
            'p' => 'personal',
            'd' => 'company',
            'd' => 'donate',
        ];
        foreach ($type_transfer as $from => $to) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->postmeta, [
                'meta_value' => $to, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            ], [
                'meta_key' => '_invoice_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => $from, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            ]);
        }

        $carruer_type_transfer = [
            '0' => 'none',
            '1' => 'ecpay_host',
            '2' => 'MOICA',
            '3' => 'phone_barcode',
        ];
        foreach ($carruer_type_transfer as $from => $to) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->postmeta, [
                'meta_value' => $to, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            ], [
                'meta_key' => '_invoice_carruer_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => $from, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            ]);
        }
    }

    protected function official_invoice_delete()
    {
        global $wpdb;

        $keys = [
            '_billing_invoice_type',
            '_billing_carruer_type',
            '_billing_carruer_num',
            '_billing_customer_identifier',
            '_billing_love_code',
            '_ecpay_invoice_number',
            '_ecpay_invoice_status',
        ];
        foreach ($keys as $key) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete($wpdb->postmeta, [
                'meta_key' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            ]);
        }
    }
}
