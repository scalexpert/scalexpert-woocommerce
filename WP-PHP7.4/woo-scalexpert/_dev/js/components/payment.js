/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

let $;

document.addEventListener("DOMContentLoaded", function () {
    $ = jQuery;
    initPayments();

    // For load js after updated page checkout with ajax
    $('body').on('updated_checkout', function () {
        initPayments();
    });
});

function initPayments() {
    if ($('.payment_methods input[type="radio"]').length) {
        changeRadio($('.payment_methods input[type="radio"]:checked'));
        addEventOnRadio();
    }
    checkboxCGV();
    addEventPaymentButton();
}

function addEventOnRadio() {
    $('.payment_methods input[type="radio"]').on('change', function (event) {
        if (typeof event !== 'undefined' && event && $(event.target).length) {
            changeRadio($(event.target));
        }
    });
}

function changeRadio($radio) {
    if (typeof $radio !== 'undefined' &&
        $radio &&
        $radio.length &&
        $('#place_order').length) {
        if ($radio.attr('value') === 'scalexpert') {
            $('#place_order').hide();
        } else {
            $('#place_order').show();
        }
    }
}

function checkboxCGV() {
    let $checkboxCGV = $('form.woocommerce-checkout input[type="checkbox"]#terms, form#order_review input[type="checkbox"]#terms');

    if($checkboxCGV.length) {
        $checkboxCGV.on('change', function (elm) {
            changeCheckboxCGV(elm.target);
        })
    }
}

function changeCheckboxCGV(checkboxCGV) {
    let $checkboxCGV = $(checkboxCGV);
    let $paymentButtons = $('.payment_method_scalexpert .sep_financialSolution .sep_financialSolution-buttonPay');

    if($paymentButtons.length) {
        $paymentButtons.attr('disabled', '').removeAttr('disabled').removeClass('disabled');
        if(!$checkboxCGV.prop('checked')) {
            $paymentButtons.attr('disabled', 'disabled').addClass('disabled');
        }
    }
}

function addEventPaymentButton() {
    let $paymentButtons = $('.payment_method_scalexpert .sep_financialSolution .sep_financialSolution-buttonPay');
    let $inputSolutionCode = $('.payment_method_scalexpert input[name="solutionCode"]');

    if($paymentButtons.length && $inputSolutionCode.length) {
        $paymentButtons.off().on('click', function() {
            $inputSolutionCode.val($(this).attr('data-solutioncode')); // add solution code in input for next step
            $('#place_order').submit(); // Submit form commande if valid phone number
        })
    }
}
