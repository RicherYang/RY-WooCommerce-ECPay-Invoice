import $ from 'jquery';

import './ry-invoice.scss';

$(function () {
    if ($('#RY_WEI_get_mode').length) {
        $('#RY_WEI_get_mode').on('change', function () {
            if ($(this).val() == 'manual') {
                $('#RY_WEI_get_delay_days').closest('tr').hide();
                $('#RY_WEI_skip_foreign_order').closest('tr').hide();
            } else {
                $('#RY_WEI_get_delay_days').closest('tr').show();
                $('#RY_WEI_skip_foreign_order').closest('tr').show();
            }
        }).trigger('change');
    }

    if ($('#RY_WEI_amount_abnormal_mode').length) {
        $('#RY_WEI_amount_abnormal_mode').on('change', function () {
            if ($(this).val() == 'product') {
                $('#RY_WEI_amount_abnormal_product').closest('tr').show();
            } else {
                $('#RY_WEI_amount_abnormal_product').closest('tr').hide();
            }
        }).trigger('change');
    }

    if ($('#_invoice_type').length) {
        $(document.body).on('change', '#_invoice_type', function () {
            switch ($(this).val()) {
                case 'personal':
                    $('._invoice_carruer_type_field').show();
                    $('._invoice_no_field').hide();
                    $('._invoice_donate_no_field').hide();
                    $('#_invoice_carruer_type').trigger('change');
                    break;
                case 'company':
                    $('._invoice_carruer_type_field').hide();
                    $('._invoice_carruer_no_field').hide();
                    $('._invoice_no_field').show();
                    $('._invoice_donate_no_field').hide();
                    break;
                case 'donate':
                    $('._invoice_carruer_type_field').hide();
                    $('._invoice_carruer_no_field').hide();
                    $('._invoice_no_field').hide();
                    $('._invoice_donate_no_field').show();
                    break;
            }
        });
        $(document.body).on('change', '#_invoice_carruer_type', function () {
            switch ($(this).val()) {
                case 'none':
                    $('._invoice_carruer_no_field').hide();
                    break;
                case 'ecpay_host':
                    $('._invoice_carruer_no_field').hide();
                    break;
                case 'MOICA':
                    $('._invoice_carruer_no_field').show();
                    break;
                case 'phone_barcode':
                    $('._invoice_carruer_no_field').show();
                    break;
            }
        });
        $('#_invoice_type').trigger('change');
    }

    $('#get_ecpay_invoice').on('click', function () {
        $.blockUI({
            message: RyWeiAdminInvoiceParams.i18n.get
        });
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'RY_WEI_get',
                id: $(this).data('orderid'),
                _ajax_nonce: RyWeiAdminInvoiceParams._nonce.get
            }
        }).always(function () {
            location.reload();
        });
    });

    $('#invalid_ecpay_invoice').on('click', function () {
        $.blockUI({
            message: RyWeiAdminInvoiceParams.i18n.invalid
        });
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'RY_WEI_invalid',
                id: $(this).data('orderid'),
                _ajax_nonce: RyWeiAdminInvoiceParams._nonce.invalid
            }
        }).always(function () {
            location.reload();
        });
    });

    $('#cancel_delay_ecpay_invoice').on('click', function () {
        $.blockUI({
            message: RyWeiAdminInvoiceParams.i18n.cancel
        });
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'RY_WEI_cancel_delay',
                id: $(this).data('orderid'),
                _ajax_nonce: RyWeiAdminInvoiceParams._nonce.cancel
            }
        }).always(function () {
            location.reload();
        });
    });
});
