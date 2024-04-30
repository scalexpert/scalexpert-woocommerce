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

    // Init and load modal
    initModal();

    // For modal in checkout, load js after updated page checkout with ajax
    $('body').on('updated_checkout', function () {
        initModal();
    })
});

function initModal() {
    if ($('[data-modal="sep_openModal"]').length) {
        let $modal;
        $('[data-modal="sep_openModal"]').off().on('click', function () {
            if ($($(this)).length && $($(this).attr('data-idmodal'))) {
                $modal = $($(this).attr('data-idmodal'));
                if (typeof $modal !== 'undefined' && $modal) {
                    openModal($modal);
                }
            }
        });
    }
}

openModal = function openModal($modal) {
    if (typeof $modal !== 'undefined' && $modal.length) {
        $modal.show();
        eventCloseModal($modal);
    }
}

function eventCloseModal($modal) {
    if (typeof $modal !== 'undefined' && $modal.length) {
        $modal.off().on('click', function (event) {
            if (typeof event !== 'undefined' &&
                $(event.target).length &&
                $(event.target).attr('id') === $modal.attr('id')) {
                $modal.hide();
            }
        });
        if ($modal.find('.close').length) {
            $modal.find('.close').off().on('click', function () {
                $modal.hide();
            });
        }
    }
}
