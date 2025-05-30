<?php

class RY_WEI_WC_Invoice_Api extends RY_WEI_EcPay
{
    protected static $_instance = null;

    protected $api_test_url = [
        'get' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue',
        'getDelay' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue',
        'cancelDelay' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CancelDelayIssue',
        'invalid' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid',
        'checkMobile' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode',
        'checkDonate' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode',
    ];

    protected $api_url = [
        'get' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue',
        'getDelay' => 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue',
        'cancelDelay' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CancelDelayIssue',
        'invalid' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid',
        'checkMobile' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode',
        'checkDonate' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode',
    ];

    public static function instance(): RY_WEI_WC_Invoice_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function get($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        if ($order->get_meta('_invoice_number')) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();

        $data = $this->make_get_data($order, $MerchantID);
        if ($data['SalesAmount'] == 0) {
            $order->update_meta_data('_invoice_number', 'zero');
            $order->save();
            $order->add_order_note(__('Zero total fee without invoice', 'ry-woocommerce-ecpay-invoice'));
            return;
        }
        if ($data['SalesAmount'] < 0) {
            $order->update_meta_data('_invoice_number', 'negative');
            $order->save();
            $order->add_order_note(__('Negative total fee can\'t invoice', 'ry-woocommerce-ecpay-invoice'));
            return;
        }

        $args = $this->build_args($data, $MerchantID);
        do_action('ry_wei_get_invoice', $args, $order);

        RY_WEI_WC_Invoice::instance()->log('Issue invoice for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WEI_WC_Invoice::instance()->is_testmode()) {
            $post_url = $this->api_test_url['get'];
        } else {
            $post_url = $this->api_url['get'];
        }
        $result = $this->link_server($post_url, $args, $HashKey, $HashIV);

        if (null === $result) {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Issue invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg,
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Invoice number', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->InvoiceNo . "\n"
                . __('Invoice random number', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->RandomNumber . "\n"
                . __('Invoice create time', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->InvoiceDate . "\n",
            );
        }

        $order->update_meta_data('_invoice_number', $result->InvoiceNo);
        $order->update_meta_data('_invoice_random_number', $result->RandomNumber);
        $order->update_meta_data('_invoice_date', $result->InvoiceDate);
        $order->update_meta_data('_invoice_ecpay_RelateNumber', $data['RelateNumber']);
        $order->save();

        do_action('ry_wei_get_invoice_response', $result, $order);
    }

    public function get_delay($order_ID)
    {
        $delay_days = (int) RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days <= 0) {
            return $this->get($order_ID);
        }

        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        if ($order->get_meta('_invoice_number')) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();

        $data = $this->make_get_data($order, $MerchantID);
        if ($data['SalesAmount'] == 0) {
            $order->update_meta_data('_invoice_number', 'zero');
            $order->save();
            $order->add_order_note(__('Zero total fee without invoice', 'ry-woocommerce-ecpay-invoice'));
            return;
        }
        if ($data['SalesAmount'] < 0) {
            $order->update_meta_data('_invoice_number', 'negative');
            $order->save();
            $order->add_order_note(__('Negative total fee can\'t invoice', 'ry-woocommerce-ecpay-invoice'));
            return;
        }

        $data['DelayFlag'] = '1';
        $data['DelayDay'] = $delay_days;
        $data['Tsr'] = $data['RelateNumber'];
        $data['PayType'] = '2';
        $data['PayAct'] = 'ECPAY';
        $data['NotifyURL'] = WC()->api_request_url('ry_wei_delay_callback', true);

        $args = $this->build_args($data, $MerchantID);
        do_action('ry_wei_get_invoice', $args, $order);

        RY_WEI_WC_Invoice::instance()->log('Issue delay invoice for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WEI_WC_Invoice::instance()->is_testmode()) {
            $post_url = $this->api_test_url['getDelay'];
        } else {
            $post_url = $this->api_url['getDelay'];
        }
        $result = $this->link_server($post_url, $args, $HashKey, $HashIV);

        if (null === $result) {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Issue delay invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg,
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Issue delay invoice', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->OrderNumber . "\n",
            );
        }

        $order->update_meta_data('_invoice_number', 'delay');
        $order->update_meta_data('_invoice_ecpay_RelateNumber', $data['RelateNumber']);
        $order->save();

        do_action('ry_wei_get_dalay_invoice_response', $result, $order);
    }

    protected function make_get_data($order, $MerchantID)
    {
        $country = $order->get_billing_country();
        $countries = WC()->countries->get_countries();
        $full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

        $state = $order->get_billing_state();
        $states = WC()->countries->get_states($country);
        $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

        $data = [
            'MerchantID' => $MerchantID,
            'ProductServiceID' => RY_WEI::get_option('used_track', ''),
            'RelateNumber' => $this->generate_trade_no($order->get_id(), RY_WEI::get_option('order_prefix', '')),
            'CustomerID' => '',
            'CustomerIdentifier' => '',
            'CustomerName' => $order->get_billing_last_name() . $order->get_billing_first_name(),
            'CustomerAddr' => $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
            'CustomerPhone' => '',
            'CustomerEmail' => $order->get_billing_email(),
            'Print' => '0',
            'Donation' => '0',
            'LoveCode' => '',
            'CarrierType' => '',
            'CarrierNum' => '',
            'TaxType' => '1',
            'SalesAmount' => round($order->get_total() - $order->get_total_refunded(), 0),
            'InvoiceRemark' => '#' . $order->get_order_number(),
            'Items' => [],
            'InvType' => '07',
            'vat' => '1',
        ];

        switch ($order->get_meta('_invoice_type')) {
            case 'personal':
                switch ($order->get_meta('_invoice_carruer_type')) {
                    case 'none':
                        $data['Print'] = '1';
                        break;
                    case 'ecpay_host':
                        $data['CarrierType'] = '1';
                        break;
                    case 'MOICA':
                        $data['CarrierType'] = '2';
                        $data['CarrierNum'] = $order->get_meta('_invoice_carruer_no');
                        break;
                    case 'phone_barcode':
                        $data['CarrierType'] = '3';
                        $data['CarrierNum'] = $order->get_meta('_invoice_carruer_no');
                        break;
                }
                break;
            case 'company':
                $carruer_mode = RY_WEI::get_option('company_carruer_mode', 'print');
                if ($carruer_mode == 'print') {
                    $data['Print'] = '1';
                    $data['CarrierType'] = '';
                } else {
                    $data['CarrierType'] = '1';
                }
                $data['CustomerIdentifier'] = $order->get_meta('_invoice_no');
                $company = $order->get_billing_company();
                if ($company) {
                    $data['CustomerName'] = $company;
                }
                break;
            case 'donate':
                $data['Donation'] = '1';
                $data['LoveCode'] = $order->get_meta('_invoice_donate_no');
                break;
        }

        $total_refunded = $order->get_total_refunded();
        $use_sku = 'yes' === RY_WEI::get_option('use_sku_as_name', 'no');
        $order_items = $order->get_items(['line_item']);
        if (count($order_items)) {
            foreach ($order_items as $order_item) {
                $item_total = $order_item->get_total();
                $item_refunded = $order->get_total_refunded_for_item($order_item->get_id(), $order_item->get_type());
                $total_refunded -= $item_refunded;
                if ('yes' !== get_option('woocommerce_tax_round_at_subtotal')) {
                    $item_total = round($item_total, wc_get_price_decimals());
                    $item_refunded = round($item_refunded, wc_get_price_decimals());
                }

                $item_total = $item_total - $item_refunded;
                $item_qty = $order_item->get_quantity() + $order->get_qty_refunded_for_item($order_item->get_id(), $order_item->get_type());

                if ($item_total == 0 && $item_qty == 0) {
                    continue;
                }

                $data_item = [
                    'ItemName' => '',
                    'ItemCount' => $item_qty == 0 ? 1 : $item_qty,
                    'ItemAmount' => $item_total,
                ];
                if ($use_sku && method_exists($order_item, 'get_product')) {
                    $data_item['ItemName'] = $order_item->get_product()->get_sku();
                }
                if (empty($data_item['ItemName'])) {
                    $data_item['ItemName'] = $order_item->get_name();
                }
                $data['Items'][] = $data_item;
            }
        }
        $fee_items = $order->get_items(['fee']);
        if (count($fee_items)) {
            foreach ($fee_items as $fee_item) {
                $item_total = $fee_item->get_total();
                $item_qty = $fee_item->get_quantity();
                $item_total = round($item_total, wc_get_price_decimals());
                if ($item_total == 0 && $item_qty == 0) {
                    continue;
                }

                $data_item = [
                    'ItemName' => $fee_item->get_name(),
                    'ItemCount' => $item_qty == 0 ? 1 : $item_qty,
                    'ItemAmount' => $item_total,
                ];
                $data['Items'][] = $data_item;
            }
        }

        $shipping_fee = $order->get_shipping_total() - $order->get_total_shipping_refunded();
        $total_refunded -= $order->get_total_shipping_refunded();
        if ($shipping_fee != 0) {
            $data['Items'][] = [
                'ItemName' => __('shipping fee', 'ry-woocommerce-ecpay-invoice'),
                'ItemCount' => 1,
                'ItemAmount' => round($shipping_fee, wc_get_price_decimals()),
            ];
        }

        if ($total_refunded != 0) {
            $data['Items'][] = [
                'ItemName' => __('return fee', 'ry-woocommerce-ecpay-invoice'),
                'ItemCount' => 1,
                'ItemAmount' => round(-$total_refunded, wc_get_price_decimals()),
            ];
        }

        $total_amount = array_sum(array_column($data['Items'], 'ItemAmount'));
        if ($total_amount != $data['SalesAmount']) {
            switch (RY_WEI::get_option('amount_abnormal_mode', '')) {
                case 'product':
                    $data['Items'][] = [
                        'ItemName' => RY_WEI::get_option('amount_abnormal_product', __('Discount', 'ry-woocommerce-ecpay-invoice')),
                        'ItemCount' => 1,
                        'ItemAmount' => round($data['SalesAmount'] - $total_amount, wc_get_price_decimals()),
                    ];
                    break;
                case 'order':
                    $data['SalesAmount'] = round($total_amount, 0);
                    break;
                default:
                    break;
            }
        }

        foreach ($data['Items'] as $key => $item) {
            $data['Items'][$key]['ItemSeq'] = $key + 1;
            $data['Items'][$key]['ItemName'] = mb_substr($item['ItemName'], 0, 80);
            $data['Items'][$key]['ItemAmount'] = round($data['Items'][$key]['ItemAmount'], 0);
            $data['Items'][$key]['ItemCount'] = round($data['Items'][$key]['ItemCount'], 3);
            $data['Items'][$key]['ItemPrice'] = round($data['Items'][$key]['ItemAmount'] / $data['Items'][$key]['ItemCount'], 2);
            $data['Items'][$key]['ItemWord'] = __('parcel', 'ry-woocommerce-ecpay-invoice');
        }

        $data['InvoiceRemark'] = apply_filters('ry_wei_invoice_remark', $data['InvoiceRemark'], $data, $order);
        $data['InvoiceRemark'] = mb_substr($data['InvoiceRemark'], 0, 100);

        return $data;
    }

    public function cancel_delay($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        $ecpay_RelateNumber = $order->get_meta('_invoice_ecpay_RelateNumber');

        if (!$ecpay_RelateNumber) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();
        $data = [
            'MerchantID' => $MerchantID,
            'Tsr' => $ecpay_RelateNumber,
        ];

        $args = $this->build_args($data, $MerchantID);
        do_action('ry_wei_cancel_delay_invoice', $args, $order);

        RY_WEI_WC_Invoice::instance()->log('Invalid delay invoice for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WEI_WC_Invoice::instance()->is_testmode()) {
            $post_url = $this->api_test_url['cancelDelay'];
        } else {
            $post_url = $this->api_url['cancelDelay'];
        }
        $result = $this->link_server($post_url, $args, $HashKey, $HashIV);

        if (null === $result) {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Cancel delay invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg,
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Cancel delay invoice', 'ry-woocommerce-ecpay-invoice'),
            );
        }

        $order->delete_meta_data('_invoice_number');
        $order->delete_meta_data('_invoice_ecpay_RelateNumber');
        $order->save();

        do_action('ry_wei_cancel_delay_invoice_response', $result, $order);
    }

    public function invalid($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        $invoice_number = $order->get_meta('_invoice_number');

        if ('zero' == $invoice_number || 'negative' == $invoice_number) {
            $order->delete_meta_data('_invoice_number');
            $order->save();
            return;
        }

        if (!$invoice_number) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();
        $data = [
            'MerchantID' => $MerchantID,
            'InvoiceNo' => $invoice_number,
            'InvoiceDate' => $order->get_meta('_invoice_date'),
            'Reason' => __('Order cancel', 'ry-woocommerce-ecpay-invoice'),
        ];

        $args = $this->build_args($data, $MerchantID);
        do_action('ry_wei_invalid_invoice', $args, $order);

        RY_WEI_WC_Invoice::instance()->log('Invalid invoice for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WEI_WC_Invoice::instance()->is_testmode()) {
            $post_url = $this->api_test_url['invalid'];
        } else {
            $post_url = $this->api_url['invalid'];
        }
        $result = $this->link_server($post_url, $args, $HashKey, $HashIV);

        if (null === $result) {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Invalid invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg,
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Invalid invoice', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->InvoiceNo,
            );
        }

        $order->delete_meta_data('_invoice_number');
        $order->delete_meta_data('_invoice_random_number');
        $order->delete_meta_data('_invoice_date');
        $order->delete_meta_data('_invoice_ecpay_RelateNumber');
        $order->save();

        do_action('ry_wei_invalid_invoice_response', $result, $order);
    }

    public function check_mobile_code($code)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();

        $data = [
            'MerchantID' => $MerchantID,
            'BarCode' => $code,
        ];
        $args = $this->build_args($data, $MerchantID);

        RY_WEI_WC_Invoice::instance()->log('Check mobile', WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WEI_WC_Invoice::instance()->is_testmode()) {
            $post_url = $this->api_test_url['checkMobile'];
        } else {
            $post_url = $this->api_url['checkMobile'];
        }

        $result = $this->link_server($post_url, $args, $HashKey, $HashIV);

        if (null === $result) {
            return false;
        }

        return $this->get_no_check_status($result);
    }

    public function check_donate_no($code)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WEI_WC_Invoice::instance()->get_api_info();

        $data = [
            'MerchantID' => $MerchantID,
            'LoveCode' => $code,
        ];
        $args = $this->build_args($data, $MerchantID);

        RY_WEI_WC_Invoice::instance()->log('Check donate', WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WEI_WC_Invoice::instance()->is_testmode()) {
            $post_url = $this->api_test_url['checkDonate'];
        } else {
            $post_url = $this->api_url['checkDonate'];
        }

        $result = $this->link_server($post_url, $args, $HashKey, $HashIV);

        if (null === $result) {
            return false;
        }

        return $this->get_no_check_status($result);
    }

    protected function get_no_check_status($result)
    {
        if (1 == $result->RtnCode) {
            return 'Y' == $result->IsExist;
        }

        if (9000001 == $result->RtnCode) {
            return true;
        }

        return false;
    }
}
