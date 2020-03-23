<?php
defined('RY_WEI_VERSION') or exit('No direct script access allowed');

class WC_Meta_Box_Invoice_Data
{
    protected static $fields;

    protected static function init_fields()
    {
        self::$fields = [
            'type' => [
                'label'   => __('Invoice type', 'ry-woocommerce-ecpay-invoice'),
                'show'    => false,
                'class'   => 'select short',
                'type'    => 'select',
                'options' => [
                    'personal' => _x('personal', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                    'company' => _x('company', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                       'donate' => _x('donate', 'invoice type', 'ry-woocommerce-ecpay-invoice')
                ]
            ],
            'carruer_type' => [
                'label'   => __('Carruer type', 'ry-woocommerce-ecpay-invoice'),
                'show'    => false,
                'class'   => 'select short',
                'type'    => 'select',
                'options' => [
                    'none' => _x('none', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'MOICA' => _x('MOICA', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-woocommerce-ecpay-invoice')
                ]
            ],
            'carruer_no' => [
                'label'   => __('Carruer number', 'ry-woocommerce-ecpay-invoice'),
                'show'    => false,
                'type'    => 'text'
            ],
            'no' => [
                'label'   => __('Tax ID number', 'ry-woocommerce-ecpay-invoice'),
                'show'    => false,
                'type'    => 'text'
            ],
            'donate_no' => [
                'label'   => __('Donate number', 'ry-woocommerce-ecpay-invoice'),
                'show'    => false,
                'type'    => 'text'
            ]
        ];

        if ('no' == RY_WEI::get_option('support_carruer_type_none', 'no')) {
            unset(self::$fields['carruer_type']['options']['none']);
        }
    }

    public static function output($order)
    {
        self::init_fields();

        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_type = $order->get_meta('_invoice_type');
        $carruer_type = $order->get_meta('_invoice_carruer_type'); ?>

<h3 style="clear:both"><?=__('Invoice info', 'ry-woocommerce-ecpay-invoice') ?>
</h3>
<?php if (!empty($invoice_type)) { ?>
<div class="ivoice <?=$invoice_number ? '' : 'address' ?>">
    <div class="ivoice_data_column">
        <p>
            <?php if ($invoice_number) { ?>
            <strong><?=__('Invoice number', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?=$invoice_number ?><br>
            <strong><?=__('Invoice random number', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?=$order->get_meta('_invoice_random_number') ?><br>
            <?php } ?>
            <strong><?=__('Invoice type', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?php echo _x($invoice_type, 'invoice type', 'ry-woocommerce-ecpay-invoice'); ?><br>
            <?php if ($invoice_type == 'personal') { ?>
            <strong><?=__('Carruer type', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?php echo _x($carruer_type, 'carruer type', 'ry-woocommerce-ecpay-invoice'); ?><br>
            <?php if (in_array($carruer_type, ['MOICA', 'phone_barcode'])) { ?>
            <strong><?=__('Carruer number', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?php echo $order->get_meta('_invoice_carruer_no'); ?><br>
            <?php } ?>
            <?php } ?>
            <?php if ($invoice_type == 'company') { ?>
            <strong><?=__('Tax ID number', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?php echo $order->get_meta('_invoice_no'); ?><br>
            <?php } ?>
            <?php if ($invoice_type == 'donate') { ?>
            <strong><?=__('Donate number', 'ry-woocommerce-ecpay-invoice') ?>:</strong>
            <?php echo $order->get_meta('_invoice_donate_no'); ?><br>
            <?php } ?>
        </p>
    </div>
    <div class="ivoice_action_column">
        <?php if ($invoice_number) { ?>
        <button id="invalid_ecpay_invoice" type="button" class="button" data-orderid="<?=$order->get_id() ?>">
            <?=__('Invalid invoice', 'ry-woocommerce-ecpay-invoice') ?></button>
        <?php } elseif ($order->is_paid()) { ?>
        <button id="get_ecpay_invoice" type="button" class="button" data-orderid="<?=$order->get_id() ?>">
            <?=__('Get invoice', 'ry-woocommerce-ecpay-invoice') ?></button>
        <?php } ?>
    </div>
</div>
<?php } ?>
<div class="edit_address">
    <?php
            if (!$invoice_number) {
                foreach (self::$fields as $key => $field) {
                    $field['id'] = '_invoice_' . $key;
                    $field['value'] = $order->get_meta($field['id']);

                    switch ($field['type']) {
                        case 'select':
                            woocommerce_wp_select($field);
                            break;
                        default:
                            woocommerce_wp_text_input($field);
                            break;
                    }
                }
            } ?>
</div>
<?php
    }
}
