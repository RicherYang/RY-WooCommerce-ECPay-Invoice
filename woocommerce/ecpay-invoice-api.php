<?php
defined('RY_WEI_VERSION') or exit('No direct script access allowed');

class RY_WEI_Invoice_Api extends RY_ECPay
{
    public static $api_test_url = [
        'get' => 'https://einvoice-stage.ecpay.com.tw/Invoice/Issue',
        'invalid' => 'https://einvoice-stage.ecpay.com.tw/Invoice/IssueInvalid',
        'checkMobile' => 'https://einvoice-stage.ecpay.com.tw/Query/CheckMobileBarCode',
        'checkDonate' => 'https://einvoice-stage.ecpay.com.tw/Query/CheckLoveCode'
    ];

    public static $api_url = [
        'get' => 'https://einvoice.ecpay.com.tw/Invoice/Issue',
        'invalid' => 'https://einvoice.ecpay.com.tw/Invoice/IssueInvalid',
        'checkMobile' => 'https://einvoice.ecpay.com.tw/Query/CheckMobileBarCode',
        'checkDonate' => 'https://einvoice.ecpay.com.tw/Query/CheckLoveCode'
    ];

    public static function get($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        if ($order->get_meta('_invoice_number')) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $country = $order->get_billing_country();
        $countries = WC()->countries->get_countries();
        $full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

        $state = $order->get_billing_state();
        $states = WC()->countries->get_states($country);
        $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

        $args = [
            'MerchantID' => $MerchantID,
            'RelateNumber' => self::generate_trade_no($order->get_id(), RY_WEI::get_option('order_prefix')),
            'CustomerID' => '',
            'CustomerIdentifier' => '',
            'CustomerName' => $order->get_billing_last_name() . $order->get_billing_first_name(),
            'CustomerAddr' => $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
            'CustomerPhone' => '',
            'CustomerEmail' => $order->get_billing_email(),
            'Print' => 0,
            'Donation' => 0,
            'LoveCode' => '',
            'CarruerType' => '',
            'CarruerNum' => '',
            'TaxType' => 1,
            'SalesAmount' => intval(round($order->get_total(), 0)),
            'InvoiceRemark' => $order->get_id(),
            'ItemName' => [],
            'ItemCount' => [],
            'ItemWord' => [],
            'ItemPrice' => [],
            'ItemTaxType' => [],
            'ItemAmount' => [],
            'InvType' => '07',
            'TimeStamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
        ];
        $args['TimeStamp'] = $args['TimeStamp']->format('U');

        switch ($order->get_meta('_invoice_type')) {
            case 'personal':
                switch ($order->get_meta('_invoice_carruer_type')) {
                    case 'none':
                        $args['Print'] = 1;
                        break;
                    case 'ecpay_host':
                        $args['CarruerType'] = 1;
                        break;
                    case 'MOICA':
                        $args['CarruerType'] = 2;
                        $args['CarruerNum'] = $order->get_meta('_invoice_carruer_no');
                        break;
                    case 'phone_barcode':
                        $args['CarruerType'] = 3;
                        $args['CarruerNum'] = str_replace('+', ' ', $order->get_meta('_invoice_carruer_no'));
                        break;
                }
                break;
            case 'company':
                $args['Print'] = 1;
                $args['CustomerIdentifier'] = $order->get_meta('_invoice_no');
                $company = $order->get_billing_company();
                if ($company) {
                    $args['CustomerName'] = $company;
                }
                break;
            case 'donate':
                $args['Donation'] = 1;
                $args['LoveCode'] = $order->get_meta('_invoice_donate_no');
                break;
        }

        $total_amount = 0;
        $items = $order->get_items();
        if (count($items)) {
            foreach ($items as $item) {
                $item_total = intval(round($item->get_total()));
                $args['ItemName'][] = $item->get_name();
                $args['ItemCount'][] = $item->get_quantity();
                $args['ItemWord'][] = __('item word', 'ry-woocommerce-ecpay-invoice');
                $args['ItemPrice'][] = round($item_total / $item->get_quantity(), 2);
                $args['ItemTaxType'][] = 1;
                $args['ItemAmount'][] = $item_total;
                $total_amount += $item_total;
            }
        }

        $shipping_fee = $order->get_shipping_total();
        if ($shipping_fee != 0) {
            $args['ItemName'][] = __('shipping fee', 'ry-woocommerce-ecpay-invoice');
            $args['ItemCount'][] = 1;
            $args['ItemWord'][] = __('item word', 'ry-woocommerce-ecpay-invoice');
            $args['ItemPrice'][] = $shipping_fee;
            $args['ItemTaxType'][] = 1;
            $args['ItemAmount'][] = $shipping_fee;
            $total_amount += $shipping_fee;
        }

        $total_fee = $args['SalesAmount'] - $total_amount;
        if ($total_fee != 0) {
            $args['ItemName'][] = __('fee', 'ry-woocommerce-ecpay-invoice');
            $args['ItemCount'][] = 1;
            $args['ItemWord'][] = __('item word', 'ry-woocommerce-ecpay-invoice');
            $args['ItemPrice'][] = $total_fee;
            $args['ItemTaxType'][] = 1;
            $args['ItemAmount'][] = $total_fee;
        }

        foreach ($args as &$value) {
            if (is_array($value)) {
                $value = implode('|', $value);
            }
        }
        unset($value);

        $args['InvoiceRemark'] = apply_filters('ry_wei_invoice_remark', $args['InvoiceRemark'], $args, $order);

        foreach (['CustomerName', 'CustomerAddr', 'CustomerEmail', 'InvoiceRemark', 'ItemName', 'ItemWord', 'ItemRemark'] as $key) {
            if (isset($args[$key])) {
                $args[$key] = self::urlencode($args[$key]);
            }
        }

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['get'];
        } else {
            $post_url = self::$api_url['get'];
        }

        $args = self::add_check_value($args, $HashKey, $HashIV, 'md5', ['InvoiceRemark', 'ItemName', 'ItemWord', 'ItemRemark']);
        RY_WEI_Invoice::log('Create POST: ' . var_export($args, true));

        do_action('ry_wei_get_invoice', $args, $order);
        $response = self::link_server($post_url, $args);
        if (is_wp_error($response)) {
            RY_WEI_Invoice::log('Create failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            return ;
        }

        if ($response['response']['code'] != '200') {
            RY_WEI_Invoice::log('Create failed. Http code: ' . $response['response']['code'] . "\n" . 'url: ' . $post_url, 'error');
            return ;
        }

        RY_WEI_Invoice::log('Create request result: ' . $response['body']);
        parse_str($response['body'], $result);

        if (!is_array($result)) {
            RY_WEI_Invoice::log('Create failed. Response parse failed.', 'error');
            return ;
        }

        $check_value = self::get_check_value($result);
        $tmp_check_value = self::generate_check_value($result, $HashKey, $HashIV, 'md5');
        if ($check_value != $tmp_check_value) {
            RY_WEI_Invoice::log('Create failed. Response check failed. Response:' . $check_value . ' Self:' . $tmp_check_value, 'error');
            return ;
        }

        if (self::get_status($result) != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Get invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                self::get_status_msg($result)
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Invoice number', 'ry-woocommerce-ecpay-invoice') . ': ' . $result['InvoiceNumber'] . "\n"
                . __('Invoice random number', 'ry-woocommerce-ecpay-invoice') . ': ' . $result['RandomNumber'] . "\n"
                . __('Invoice create time', 'ry-woocommerce-ecpay-invoice') . ': ' . $result['InvoiceDate'] . "\n"
            );
        }

        $order->update_meta_data('_invoice_number', $result['InvoiceNumber']);
        $order->update_meta_data('_invoice_random_number', $result['RandomNumber']);
        $order->update_meta_data('_invoice_ecpay_RelateNumber', $args['RelateNumber']);
        $order->save_meta_data();

        do_action('ry_wei_get_invoice_response', $result, $order);
    }

    public static function invalid($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();
        $invoice_number = $order->get_meta('_invoice_number');

        if (!$invoice_number) {
            return false;
        }

        $args = [
            'MerchantID' => $MerchantID,
            'InvoiceNumber' => $invoice_number,
            'Reason' => __('Invalid invoice', 'ry-woocommerce-ecpay-invoice'),
            'TimeStamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
        ];
        $args['TimeStamp'] = $args['TimeStamp']->format('U');

        foreach (['Reason'] as $key) {
            if (isset($args[$key])) {
                $args[$key] = self::urlencode($args[$key]);
            }
        }

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['invalid'];
        } else {
            $post_url = self::$api_url['invalid'];
        }

        $args = self::add_check_value($args, $HashKey, $HashIV, 'md5', ['Reason']);
        RY_WEI_Invoice::log('Invalid POST: ' . var_export($args, true));

        do_action('ry_wei_invalid_invoice', $args, $order);
        $response = self::link_server($post_url, $args);
        if (is_wp_error($response)) {
            RY_WEI_Invoice::log('Invalid failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            return ;
        }

        if ($response['response']['code'] != '200') {
            RY_WEI_Invoice::log('Invalid failed. Http code: ' . $response['response']['code'], 'error');
            return ;
        }

        RY_WEI_Invoice::log('Invalid request result: ' . $response['body']);
        parse_str($response['body'], $result);

        if (!is_array($result)) {
            RY_WEI_Invoice::log('Invalid failed. Response parse failed.', 'error');
            return ;
        }

        $check_value = self::get_check_value($result);
        $tmp_check_value = self::generate_check_value($result, $HashKey, $HashIV, 'md5');
        if ($check_value != $tmp_check_value) {
            RY_WEI_Invoice::log('Invalid failed. Response check failed. Response:' . $check_value . ' Self:' . $tmp_check_value, 'error');
            return ;
        }

        if (self::get_status($result) != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Invalid invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                self::get_status_msg($result)
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Invalid invoice', 'ry-woocommerce-ecpay-invoice') . ': ' . $result['InvoiceNumber']
            );
        }

        $order->delete_meta_data('_invoice_number');
        $order->delete_meta_data('_invoice_random_number');
        $order->delete_meta_data('_Invoice_date');
        $order->save_meta_data();

        do_action('ry_wei_invalid_invoice_response', $result, $order);
    }

    public static function check_mobile_code($code)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $args = [
            'MerchantID' => $MerchantID,
            'BarCode' => str_replace('+', ' ', $code),
            'TimeStamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
        ];
        $args['TimeStamp'] = $args['TimeStamp']->format('U');

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['checkMobile'];
        } else {
            $post_url = self::$api_url['checkMobile'];
        }

        $args = self::add_check_value($args, $HashKey, $HashIV, 'md5');
        RY_WEI_Invoice::log('Check mobile POST: ' . var_export($args, true));

        $response = self::link_server($post_url, $args);
        if (is_wp_error($response)) {
            RY_WEI_Invoice::log('Check mobile failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            return null;
        }

        if ($response['response']['code'] != '200') {
            RY_WEI_Invoice::log('Check mobile failed. Http code: ' . $response['response']['code'], 'error');
            return null;
        }

        RY_WEI_Invoice::log('Check mobile request result: ' . $response['body']);
        parse_str($response['body'], $result);
        if (!is_array($result)) {
            RY_WEI_Invoice::log('Check mobile failed. Parse result failed.', 'error');
            return null;
        }

        return self::get_status($result) == 1 && $result['IsExist'] == 'Y';
    }

    public static function check_donate_no($code)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $args = [
            'MerchantID' => $MerchantID,
            'LoveCode' => $code,
            'TimeStamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
        ];
        $args['TimeStamp'] = $args['TimeStamp']->format('U');

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['checkDonate'];
        } else {
            $post_url = self::$api_url['checkDonate'];
        }

        $args = self::add_check_value($args, $HashKey, $HashIV, 'md5');
        RY_WEI_Invoice::log('Check donate POST: ' . var_export($args, true));

        $response = self::link_server($post_url, $args);
        if (is_wp_error($response)) {
            RY_WEI_Invoice::log('Check donate failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            return null;
        }

        if ($response['response']['code'] != '200') {
            RY_WEI_Invoice::log('Check donate failed. Http code: ' . $response['response']['code'], 'error');
            return null;
        }

        RY_WEI_Invoice::log('Check donate request result: ' . $response['body']);
        parse_str($response['body'], $result);
        if (!is_array($result)) {
            RY_WEI_Invoice::log('Check donate failed. Parse result failed.', 'error');
            return null;
        }

        return self::get_status($result) == 1 && $result['IsExist'] == 'Y';
    }
}
