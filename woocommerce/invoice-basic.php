<?php

final class RY_WEI_WC_Invoice_Basic
{
    protected static $_instance = null;

    public static function instance(): RY_WEI_WC_Invoice_Basic
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('woocommerce_checkout_fields', [$this, 'add_invoice_info'], 9999);

        add_action('woocommerce_after_checkout_billing_form', [$this, 'show_invoice_form']);
        add_action('woocommerce_after_checkout_validation', [$this, 'invoice_checkout_validation'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'save_order_invoice'], 10, 2);

        add_action('woocommerce_order_details_after_customer_details', [$this, 'show_invoice_info']);
    }

    public function add_invoice_info($fields)
    {
        $fields['invoice'] = [
            'invoice_type' => [
                'type' => 'select',
                'label' => __('Invoice type', 'ry-woocommerce-ecpay-invoice'),
                'options' => [
                    'personal' => _x('personal', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                    'company' => _x('company', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                    'donate' => _x('donate', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                ],
                'default' => 'personal',
                'required' => true,
                'priority' => 10,
            ],
            'invoice_carruer_type' => [
                'type' => 'select',
                'label' => __('Carruer type', 'ry-woocommerce-ecpay-invoice'),
                'options' => [
                    'none' => _x('none', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-woocommerce-ecpay-invoice') . __(' (send paper when win)', 'ry-woocommerce-ecpay-invoice'),
                    'MOICA' => _x('MOICA', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                ],
                'default' => 'ecpay_host',
                'required' => true,
                'priority' => 10,
            ],
            'invoice_carruer_no' => [
                'label' => __('Carruer number', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 20,
            ],
            'invoice_no' => [
                'label' => __('Tax ID number', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 30,
            ],
            'invoice_donate_no' => [
                'label' => __('Donate number', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 40,
            ],
        ];

        if ('no' === RY_WEI::get_option('support_carruer_type_none', 'no')) {
            unset($fields['invoice']['invoice_carruer_type']['options']['none']);
        }

        if ('yes' === RY_WEI::get_option('move_billing_company', 'no')) {
            unset($fields['billing']['billing_company']);
            $fields['invoice']['invoice_company_name'] = [
                'label' => __('Company name', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 30,
            ];
        }

        // default donate no - 財團法人台灣兒童暨家庭扶助基金會 ( CCF )
        $donate_no = apply_filters('ry_wei_default_donate_no', ['7261651', '5900', '8585', '7885', '035', '378585', '2085', '024', '326139', '5875', '5520', '68660', '2100', '323804', '078585', '5584', '70885', '8300', '5678585', '2812085', '6323200', '6361712', '6361716', '8700', '7123', '1785', '3100', '6782', '461234', '818585', '33085', '176176'], '');
        if (is_array($donate_no)) {
            $donate_no = $donate_no[intval(time() / 86400) % count($donate_no)];
        }
        $fields['invoice']['invoice_donate_no']['default'] = $donate_no;

        if (did_action('woocommerce_checkout_process')) {
            $invoice_type = strtolower(sanitize_locale_name($_POST['invoice_type'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $invoice_carruer_type = strtolower(sanitize_locale_name($_POST['invoice_carruer_type'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Missing

            switch ($invoice_type) {
                case 'personal':
                    switch ($invoice_carruer_type) {
                        case 'none':
                            $fields['invoice']['invoice_carruer_no']['required'] = false;
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'ecpay_host':
                            $fields['invoice']['invoice_carruer_no']['required'] = false;
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'MOICA':
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'phone_barcode':
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                    }
                    break;
                case 'company':
                    $fields['invoice']['invoice_carruer_no']['required'] = false;
                    $fields['invoice']['invoice_donate_no']['required'] = false;
                    break;
                case 'donate':
                    $fields['invoice']['invoice_carruer_no']['required'] = false;
                    $fields['invoice']['invoice_no']['required'] = false;
                    $fields['invoice']['invoice_company_name']['required'] = false;
                    break;
            }
        }
        return $fields;
    }

    public function show_invoice_form($checkout)
    {
        $asset_info = include RY_WEI_PLUGIN_DIR . 'assets/ry-checkout.asset.php';
        wp_enqueue_script('ry-wei-checkout', RY_WEI_PLUGIN_URL . 'assets/ry-checkout.js', $asset_info['dependencies'], $asset_info['version'], true);

        wc_get_template('checkout/form-invoice.php', [
            'checkout' => $checkout,
        ], '', RY_WEI_PLUGIN_DIR . 'templates/');
    }

    public function invoice_checkout_validation($data, $errors)
    {
        if ('personal' == $data['invoice_type']) {
            // 自然人憑證
            if ('MOICA' == $data['invoice_carruer_type']) {
                if (!empty($data['invoice_carruer_no'])) {
                    if (!preg_match('/^[A-Z]{2}\d{14}$/', $data['invoice_carruer_no'])) {
                        $errors->add('validation', __('Invalid carruer number', 'ry-woocommerce-ecpay-invoice'));
                    }
                }
            }

            // 手機載具
            if ('phone_barcode' == $data['invoice_carruer_type']) {
                if (!preg_match('/^\/{1}[0-9A-Z+-.]{7}$/', $data['invoice_carruer_no'])) {
                    $errors->add('validation', __('Invalid carruer number', 'ry-woocommerce-ecpay-invoice'));
                } elseif ('yes' === RY_WEI::get_option('check_number_with_api', 'yes')) {
                    if (class_exists('RY_WEI_WC_Invoice_Api') && false === RY_WEI_WC_Invoice_Api::instance()->check_mobile_code($data['invoice_carruer_no'])) {
                        $errors->add('validation', __('Invalid carruer number', 'ry-woocommerce-ecpay-invoice'));
                    }
                }
            }

            // 統一編號
        } elseif ('company' == $data['invoice_type']) {
            if (!preg_match('/^[0-9]{8}$/', $data['invoice_no'])) {
                $errors->add('validation', __('Invalid tax ID number', 'ry-woocommerce-ecpay-invoice'));
            }

            // 愛心碼
        } elseif ('donate' == $data['invoice_type']) {
            if (!preg_match('/^[0-9]{3,7}$/', $data['invoice_donate_no'])) {
                $errors->add('validation', __('Invalid donate number', 'ry-woocommerce-ecpay-invoice'));
            } elseif ('yes' === RY_WEI::get_option('check_number_with_api', 'yes')) {
                if (class_exists('RY_WEI_WC_Invoice_Api') && false === RY_WEI_WC_Invoice_Api::instance()->check_donate_no($data['invoice_donate_no'])) {
                    $errors->add('validation', __('Invalid donate number', 'ry-woocommerce-ecpay-invoice'));
                }
            }
        }
    }

    public function save_order_invoice($order, $data)
    {
        $order->update_meta_data('_invoice_type', $data['invoice_type'] ?? 'personal');
        $order->update_meta_data('_invoice_carruer_type', $data['invoice_carruer_type'] ?? 'ecpay_host');
        $order->update_meta_data('_invoice_carruer_no', $data['invoice_carruer_no'] ?? '');
        $order->update_meta_data('_invoice_no', $data['invoice_no'] ?? '');
        $order->update_meta_data('_invoice_donate_no', $data['invoice_donate_no'] ?? '');
        if ('yes' === RY_WEI::get_option('move_billing_company', 'no')) {
            $order->set_billing_company($data['invoice_company_name'] ?? '');
        }
    }

    public function show_invoice_info($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_type = $order->get_meta('_invoice_type');
        $carruer_type = $order->get_meta('_invoice_carruer_type');

        if (!$invoice_type) {
            return;
        }

        $invoice_info = [];
        if ($invoice_number) {
            if ('zero' == $invoice_number) {
                $invoice_info[] = [
                    'key' => 'zero-info',
                    'name' => __('Zero total fee without invoice', 'ry-woocommerce-ecpay-invoice'),
                    'value' => '',
                ];
            } elseif ('negative' == $invoice_number) {
                $invoice_info[] = [
                    'key' => 'negative-info',
                    'name' => __('Negative total fee can\'t invoice', 'ry-woocommerce-ecpay-invoice'),
                    'value' => '',
                ];
            } elseif ('delay' != $invoice_number) {
                $invoice_info[] = [
                    'key' => 'invoice-number',
                    'name' => __('Invoice number', 'ry-woocommerce-ecpay-invoice'),
                    'value' => $invoice_number,
                ];
                $invoice_info[] = [
                    'key' => 'invoice-random-number',
                    'name' => __('Invoice random number', 'ry-woocommerce-ecpay-invoice'),
                    'value' => $order->get_meta('_invoice_random_number'),
                ];
            }
        }

        $invoice_info[] = [
            'key' => 'invoice-type',
            'name' => __('Invoice type', 'ry-woocommerce-ecpay-invoice'),
            'value' => rywei_invoice_type_to_name($invoice_type),
        ];

        if ('personal' == $invoice_type) {
            $key = count($invoice_info) - 1;
            $invoice_info[$key]['value'] .= ' (' . rywei_carruer_type_to_name($carruer_type) . ')';
            if (in_array($carruer_type, ['MOICA', 'phone_barcode'])) {
                $invoice_info[] = [
                    'key' => 'carruer-number',
                    'name' => __('Carruer number', 'ry-woocommerce-ecpay-invoice'),
                    'value' => $order->get_meta('_invoice_carruer_no'),
                ];
            }
        } elseif ('company' == $invoice_type) {
            $invoice_info[] = [
                'key' => 'tax-id-number',
                'name' => __('Tax ID number', 'ry-woocommerce-ecpay-invoice'),
                'value' => $order->get_meta('_invoice_no'),
            ];
        } elseif ('donate' == $invoice_type) {
            $invoice_info[] = [
                'key' => 'donate-number',
                'name' => __('Donate number', 'ry-woocommerce-ecpay-invoice'),
                'value' => $order->get_meta('_invoice_donate_no'),
            ];
        }

        $args = [
            'order' => $order,
            'invoice_info' => apply_filters('ry_wei_order_invoice_info_list', $invoice_info, $order),
        ];
        wc_get_template('order/order-invoice-info.php', $args, '', RY_WEI_PLUGIN_DIR . 'templates/');
    }
}
