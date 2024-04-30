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


    let btnNeworder = '#sg_newOrder #newBasket';

    // check if btn in DOM
    if ($(btnNeworder).length) {
        createOverlay();
        addEventOnButtonReOrder(btnNeworder);
    }
});

function addEventOnButtonReOrder(btnNeworder) {
    if ($(btnNeworder).length) {
        // When click btn
        $(btnNeworder).off().on('click', function (e) {
            e.preventDefault();
            callAjax(btnNeworder);
            return;
        });
    }
}

function createOverlay() {
    let html = '';
    html += '<div id="sg_overlay"><div class="sg_loader"></div></div>';
    $('body').append(html);
}

function openOverlay(){
    $('#sg_overlay').attr('data-open', 'true');
}

function closeOverlay(){
    $('#sg_overlay').attr('data-open', 'false');
}

function callAjax(btnNeworder) {
    if ($(btnNeworder).length && typeof sg_recreateCart_object !== 'undefined' && typeof sg_recreateCart_object.ajaxurl !== 'undefined') {
        let idOrder = parseInt($(btnNeworder).attr('data-orderId'));
        if (!isNaN(idOrder)) {

            $.ajax({
                type: 'POST',
                headers: {"cache-control": "no-cache"},
                url: sg_recreateCart_object.ajaxurl,
                async: true,
                cache: false,
                dataType: 'html',
                data: {
                    ajax: true,
                    action: 'sg_recreateCart',
                    orderID: idOrder
                },
                beforeSend: openOverlay()
            }).success(function (response) {
                if (typeof response !== 'undefined' && response) {
                    successAjax();
                } else {
                    errorAjax(btnNeworder);
                }
            }).error(function () {
                errorAjax(btnNeworder);
            });
        }
    }
}

function errorAjax(btnNeworder) {
    let txtError = 'New order creation failed !';

    if($(btnNeworder).attr('data-errorText')) {
        txtError = $(btnNeworder).attr('data-errorText');
    }

    closeOverlay();
    alert(txtError);
}

function successAjax() {
    $('#sg_newOrder').submit();
}
