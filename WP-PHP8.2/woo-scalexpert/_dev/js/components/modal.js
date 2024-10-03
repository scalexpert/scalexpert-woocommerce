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
    });
    // For modal in cart, load js after updated page checkout with ajax
    $('body').on('updated_wc_div', function () {
        initModal();
    });
});

function initModal() {
    if ($('[data-modal="sep_openModal"]').length) {
        let $modal;
        let solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';

        addEventChangeSimulation(solutionSelector);

        $('[data-modal="sep_openModal"]').off().on('click', function (e) {
            e.preventDefault();
            if ($($(this)).length) {
                if($($(this).attr('data-idmodal')).length) {
                    $modal = $($(this).attr('data-idmodal'));
                }
                else if($($(this).attr('href')).length) {
                    $modal = $($(this).attr('href'));
                }

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


function addEventChangeSimulation(solutionSelector) {
    if($(solutionSelector).length) {
        $(solutionSelector).each(function (i, elm) {
            if (typeof elm !== 'undefined' && $(elm).length) {
                let idSolution = $(elm).attr('data-id');
                if(typeof idSolution !== 'undefined' && idSolution) {
                    let idGroupSolution = $(elm).attr('data-groupid');
                    $(elm).off().on('click', function (e) {
                        e.preventDefault();
                        let idGroupSolutionSelect = '.sep-Simulations-groupSolution[data-id="' + idGroupSolution + '"]';
                        $(idGroupSolutionSelect + ' .sep-Simulations-solution').hide();
                        $(idGroupSolutionSelect + ' .sep-Simulations-solution[data-id="' + idSolution + '"]').show();
                        return;
                    });
                }

            }
        });
    }
}
