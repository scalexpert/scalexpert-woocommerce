import {initModal} from "./modal";

let $;

document.addEventListener("DOMContentLoaded", function () {
    $ = jQuery;

    $('body').on('update_variation_values', function (event) {
        const variation = getSelectedVariation(event);
        if (variation) updateViews(variation);
    });

})

const getSelectedVariation = (event) => {
    const productForm = event.target.closest('form');
    const variations = JSON.parse(productForm.getAttribute('data-product_variations'));

    const selectedAttributes = {};
    $(productForm).find('select[name^="attribute_"]').each(function () {
        const attributeName = $(this).data('attribute_name');
        selectedAttributes[attributeName] = $(this).val();
    });

    return variations.find(function (variation) {
        return Object.keys(selectedAttributes).every(function (attributeName) {
            return variation.attributes[attributeName] === selectedAttributes[attributeName];
        });
    });
};

function updateViews(variation) {
    if (!variation.display_price || !variation.variation_id) return;

    $.ajax({
        method: 'POST',
        url: '/wp-admin/admin-ajax.php',
        data: {
            action: 'sg_solutionView',
            price: variation.display_price,
            productId: variation.variation_id,
        }
    }).done(function (response) {
        if (response)
            updateHtmlContent(response);
    }).fail(function (xhr, status, error) {
        // console.error('Erreur AJAX:', error);
        // console.error('Réponse serveur:', xhr.responseText);
    });

}

function updateHtmlContent(newHtml) {
    const elements = document.querySelectorAll('.sep-Simulations-Product');
    if (elements.length > 0) {
        elements.forEach(element => {
            element.innerHTML = newHtml;
            // Reload modal after insert content
            initModal();
        });
    }
}
