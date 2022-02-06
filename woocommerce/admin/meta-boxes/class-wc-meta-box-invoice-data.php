<?php
class WC_Meta_Box_Invoice_Data
{
    protected static $fields;

    protected static function init_fields($order)
    {
        self::$fields = [
            'type' => [
                'label' => __('Invoice type', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'class' => 'select short',
                'type' => 'select',
                'options' => [
                    'personal' => _x('personal', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                    'company' => _x('company', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                       'donate' => _x('donate', 'invoice type', 'ry-woocommerce-ecpay-invoice')
                ]
            ],
            'carruer_type' => [
                'label' => __('Carruer type', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'class' => 'select short',
                'type' => 'select',
                'options' => [
                    'none' => _x('none', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'MOICA' => _x('MOICA', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-woocommerce-ecpay-invoice')
                ]
            ],
            'carruer_no' => [
                'label' => __('Carruer number', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'type' => 'text'
            ],
            'no' => [
                'label' => __('Tax ID number', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'type' => 'text'
            ],
            'donate_no' => [
                'label' => __('Donate number', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'type' => 'text'
            ],
        ];
        if ('no' == RY_WEI::get_option('support_carruer_type_none', 'no')) {
            unset(self::$fields['carruer_type']['options']['none']);
        }

        if ($order->is_paid()) {
            self::$fields['number'] = [
                'label' => __('Invoice number', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'type' => 'text'
            ];
            self::$fields['random_number'] = [
                'label' => __('Invoice random number', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'type' => 'text',
                'pattern' => '[0-9]{4}'
            ];
            self::$fields['date'] =  [
                'label' => __('Invoice date', 'ry-woocommerce-ecpay-invoice'),
                'show' => false,
                'type' => 'date'
            ];
        }
    }

    public static function output($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_type = $order->get_meta('_invoice_type');
        $carruer_type = $order->get_meta('_invoice_carruer_type'); ?>

<h3 style="clear:both">
    <?=__('Invoice info', 'ry-woocommerce-ecpay-invoice') ?>
</h3>
<?php if (!empty($invoice_type)) { ?>
<div class="ivoice <?=$invoice_number ? '' : 'address' ?>">
    <div class="ivoice_data_column">
        <p>
            <?php if ($invoice_number == 'zero') { ?>
            <strong><?=__('Invoice number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=__('Zero no invoice', 'ry-woocommerce-ecpay-invoice') ?><br>
            <?php } elseif ($invoice_number == 'delay') { ?>
            <strong><?=__('Invoice number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=__('Delay get invoice', 'ry-woocommerce-ecpay-invoice') ?><br>
            <?php } elseif ($invoice_number) { ?>
            <strong><?=__('Invoice number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=$invoice_number ?><br>
            <strong><?=__('Invoice random number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=$order->get_meta('_invoice_random_number') ?><br>
            <strong><?=__('Invoice date', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=$order->get_meta('_invoice_date') ?><br>
            <?php } ?>

            <strong><?=__('Invoice type', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=_x($invoice_type, 'invoice type', 'ry-woocommerce-ecpay-invoice'); ?><br>

            <?php if ($invoice_type == 'personal') { ?>
            <strong><?=__('Carruer type', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=_x($carruer_type, 'carruer type', 'ry-woocommerce-ecpay-invoice'); ?><br>

            <?php if (in_array($carruer_type, ['MOICA', 'phone_barcode'])) { ?>
            <strong><?=__('Carruer number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=$order->get_meta('_invoice_carruer_no'); ?><br>
            <?php } ?>
            <?php } ?>

            <?php if ($invoice_type == 'company') { ?>
            <strong><?=__('Tax ID number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=$order->get_meta('_invoice_no'); ?><br>
            <?php } ?>

            <?php if ($invoice_type == 'donate') { ?>
            <strong><?=__('Donate number', 'ry-woocommerce-ecpay-invoice') ?>:</strong> <?=$order->get_meta('_invoice_donate_no'); ?><br>
            <?php } ?>
        </p>
    </div>
    <div class="ivoice_action_column">
        <?php
        if ($invoice_number) {
            if ($invoice_number == 'delay') {
                echo '<button id="cancel_delay_ecpay_invoice" type="button" class="button" data-orderid="' . $order->get_id() . '">'
                    . __('Cancel invoice', 'ry-woocommerce-ecpay-invoice')
                    . '</button>';
            } else {
                echo '<button id="invalid_ecpay_invoice" type="button" class="button" data-orderid="' . $order->get_id() . '">'
                    . __('Invalid invoice', 'ry-woocommerce-ecpay-invoice')
                    . '</button>';
            }
        } elseif ($order->is_paid()) {
            echo '<button id="get_ecpay_invoice" type="button" class="button" data-orderid="' . $order->get_id() . '">'
                    . __('Get invoice', 'ry-woocommerce-ecpay-invoice')
                    . '</button>';
        }
        ?>
    </div>
</div>
<?php } ?>

<div class="edit_address">
    <?php
    if (!$invoice_number) {
        self::init_fields($order);

        foreach (self::$fields as $key => $field) {
            $field['id'] = '_invoice_' . $key;
            $field['value'] = $order->get_meta($field['id']);

            switch ($field['type']) {
                case 'select':
                    woocommerce_wp_select($field);
                    break;
                case 'date':
                    ?>
    <p class="form-field form-field-wide <?=$field['id'] ?>_field">
        <label for="<?=esc_attr($field['id']) ?>"><?=esc_attr($field['label']) ?></label>
        <input type="text" class="date-picker" id="<?=$field['id'] ?>" name="<?=$field['id'] ?>" maxlength="10" value="" pattern="<?php echo esc_attr(apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])')); ?>" />@
        &lrm;
        <input type="number" class="hour" placeholder="<?php esc_attr_e('h', 'ry-woocommerce-ecpay-invoice'); ?>" name="<?=esc_attr($field['id']) ?>_hour" min="0" max="23" step="1" value="" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
        <input type="number" class="minute" placeholder="<?php esc_attr_e('m', 'ry-woocommerce-ecpay-invoice'); ?>" name="<?=esc_attr($field['id']) ?>_minute" min="0" max="59" step="1" value="" pattern="[0-5]{1}[0-9]{1}" />:
        <input type="number" class="second" placeholder="<?php esc_attr_e('s', 'ry-woocommerce-ecpay-invoice'); ?>" name="<?=esc_attr($field['id']) ?>_second" min="0" max="59" step="1" value="" pattern="[0-5]{1}[0-9]{1}" />
    </p>
    <?php
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
