/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

let $;

document.addEventListener("DOMContentLoaded", function () {
    $ = jQuery;
    $('body').on('updated_checkout', function () {
        if ($('input[name="payment_method"]:checked').attr('value') === 'scalexpert') {
            if ($('input[name="solutionCode"]').length === 0) {
                $('#place_order').attr('disabled', '');
            }
        }
        $('input[name="payment_method"]').on('change', function (event) {
            if (typeof event !== 'undefined' && event && $(event.target).length) {
                if ($(event.target).attr('value') === 'scalexpert') {
                    if ($('input[name="solutionCode"]').length === 0) {
                        $('#place_order').attr('disabled', '');
                    } else {
                        $('#place_order').removeAttr('disabled');
                    }
                } else {
                    $('#place_order').removeAttr('disabled');
                }
            }
        });
    });
});