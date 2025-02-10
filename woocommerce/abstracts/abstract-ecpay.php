<?php

abstract class RY_WEI_EcPay
{
    protected const Encrypt_Method = 'aes-128-cbc';

    protected function generate_trade_no($order_ID, $order_prefix = '')
    {
        $trade_no = $order_prefix . $order_ID . 'TS' . random_int(0, 9) . strrev((string) time());
        $trade_no = substr($trade_no, 0, 20);
        $trade_no = apply_filters('ry_ecpay_trade_no', $trade_no);
        return substr($trade_no, 0, 20);
    }

    protected function build_args($data, $MerchantID)
    {
        $args = [
            'MerchantID' => $MerchantID,
            'RqHeader' => [
                'Timestamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
                'RqID' => wc_rand_hash(),
                'Revision' => '3.0.0',
            ],
            'Data' => wp_json_encode($data),
        ];
        $args['RqHeader']['Timestamp'] = $args['RqHeader']['Timestamp']->getTimestamp();

        return $args;
    }

    protected function urlencode($string)
    {
        return str_replace(
            ['%2D', '%2d', '%5F', '%5f', '%2E', '%2e', '%2A', '%2a', '%21', '%28', '%29'],
            ['-', '-', '_', '_', '.', '.', '*', '*', '!', '(', ')'],
            urlencode($string),
        );
    }

    protected function link_server($post_url, $args, $HashKey, $HashIV)
    {
        wc_set_time_limit(40);

        $args['Data'] = $this->urlencode($args['Data']);
        $args['Data'] = openssl_encrypt($args['Data'], self::Encrypt_Method, $HashKey, 0, $HashIV);

        $response = wp_remote_post($post_url, [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($args),
            'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version')),
        ]);

        if (is_wp_error($response)) {
            RY_WEI_WC_Invoice::instance()->log('Link failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WEI_WC_Invoice::instance()->log('Link HTTP status error', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
            return;
        }

        $result = @json_decode($response['body']);

        if (!is_object($result)) {
            RY_WEI_WC_Invoice::instance()->log('Link response parse failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
            return;
        }

        if (!(isset($result->TransCode) && 1 == $result->TransCode)) {
            RY_WEI_WC_Invoice::instance()->log('Link result error', WC_Log_Levels::ERROR, ['code' => $result->TransCode, 'msg' => $result->TransMsg]);
            return;
        }

        $result->Data = openssl_decrypt($result->Data, self::Encrypt_Method, $HashKey, 0, $HashIV);
        $result->Data = urldecode($result->Data);
        $result->Data = @json_decode($result->Data);

        if (!is_object($result->Data)) {
            RY_WEI_WC_Invoice::instance()->log('Link data decrypt failed', WC_Log_Levels::ERROR, ['data' => $result->Data]);
            return;
        }

        return $result->Data;
    }

    protected function generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
    {
        $skip_args[] = 'CheckMacValue';
        foreach ($skip_args as $key) {
            unset($args[$key]);
        }

        ksort($args, SORT_STRING | SORT_FLAG_CASE);

        $args_string = [];
        $args_string[] = 'HashKey=' . $HashKey;
        foreach ($args as $key => $value) {
            $args_string[] = $key . '=' . $value;
        }
        $args_string[] = 'HashIV=' . $HashIV;

        $args_string = implode('&', $args_string);
        $args_string = $this->urlencode($args_string);
        $args_string = strtolower($args_string);
        $check_value = hash($hash_algo, $args_string);
        return strtoupper($check_value);
    }

    protected function add_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
    {
        $args['CheckMacValue'] = $this->generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args);
        return $args;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info['od_sob'])) {
            $order_ID = $ipn_info['od_sob'];
            $order_ID = (int) substr($order_ID, strlen($order_prefix), strrpos($order_ID, 'TS'));
            $order_ID = apply_filters('ry_ecpay_trade_no_to_order_id', $order_ID, $ipn_info['od_sob']);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }
        return false;
    }

    protected function die_success()
    {
        exit('1|OK');
    }

    protected function die_error()
    {
        exit('0|');
    }
}
