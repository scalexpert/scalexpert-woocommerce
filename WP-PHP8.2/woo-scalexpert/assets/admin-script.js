/**
 * Toggle Visibility of Passwordfields in Forms
 * @param idField
 *
 */
function tooglePass(idField) {
    typeField = jQuery('#' + idField).attr("type");
    if (typeField == "password") {
        jQuery('#' + idField).attr("type", "text");
    } else {
        jQuery('#' + idField).attr("type", "password");
    }

}

/**
 *
 *
 * @returns {boolean}
 */
function checkKey() {
    let environment = jQuery('#environment').find(":selected").val();

    let apiKeyField = '#api_key';
    let secretField = '#secret';

    if (environment === 'Test') {
        apiKeyField += "_test";
        secretField += "_test";
    }

    let apiKey = jQuery(apiKeyField).val();
    let apiSecret = jQuery(secretField).val();

    if (!apiKey || !apiSecret) {
        alert('Veuillez compléter les informations manquantes concernant votre clé d\'API de ' + environment.toLowerCase());
        return false;
    }

    jQuery.post(
        ajaxurl,
        {
            'method': 'POST',
            'action': 'sg_checkKey',
            'environment': environment,
            'apiSecret': apiSecret,
            'apiKey': apiKey,
        },
        function (response) {
            response = JSON.parse(response);
            if (response) {
                alert('Votre clé d\'API de ' + environment.toLowerCase() + ' est valide');
            } else {
                alert('Votre clé d\'API de ' + environment.toLowerCase() + ' est invalide');
            }
        }
    );
}

/**
 *
 *
 */
function changeLabel(idfield, labelActif, labelNotActif) {
    let debugValue = document.getElementById(idfield).checked;
    let newLabel = labelNotActif;

    if (debugValue) {
        newLabel = labelActif;
    }

    document.getElementById(idfield + '_label').innerHTML = newLabel;
}


/**
 *
 *
 */
function toggleActivate(idfield, labelActif, labelNotActif) {
    let sgScalexpertActivate = jQuery('#' + idfield).val();
    let activate = (sgScalexpertActivate == 1) ? 0 : 1;
    let label = (activate == 1) ? labelActif : labelNotActif;
    jQuery('#' + idfield).val(activate);
    jQuery('#label_' + idfield).html(label);
}


jQuery(document).ready(function () {
    jQuery("#cancelSGButton").click(function () {
        jQuery("#cancelSGButton").attr('disabled', 'disabled');

        createOverlay();

        let cancelAmount = jQuery("#SGcanceledAmount").val();
        let finID = jQuery("#scalexpert_finID").val();
        let orderID = jQuery("#orderID").val();

        jQuery.post(
            ajaxurl,
            {
                'method': 'POST',
                'action': 'sg_cancelFinancing',
                'cancelAmount': cancelAmount,
                'finID': finID,
                'orderID': orderID
            },
            function (response) {
                if (response) {
                    if (JSON.parse(response) == null) {
                        alert("Demande d'annulation impossible, veuillez consulter les logs d'erreurs pour plus de détails.");
                    } else {
                        alert(JSON.parse(response));
                        window.location.replace("/wp-admin/post.php?post=" + orderID + "&action=edit");
                    }
                } else {
                    alert("L'API Scalexpert n'a pu être contacté, veuillez essayer plus tard !");
                }
            }
        );
    });

    jQuery("#scalexpert_deliveryConfirmButton").click(function () {
        jQuery("#scalexpert_deliveryConfirmButton").attr('disabled', 'disabled');

        createOverlay();

        let trackingNumber = jQuery("#scalexpert_tracking_number").val();
        let operator = jQuery("#scalexpert_operator_selected").find(':selected').val();

        if (!trackingNumber || !operator) {
            alert('Veuillez compléter les informations manquantes avant de confirmer la livraison');
            return false;
        }

        jQuery.post(
            ajaxurl,
            {
                'method': 'POST',
                'action': 'scalexpert_confirmDelivery',
                dataType: "json",
                'creditSubscriptionId': jQuery("#scalexpert_creditSubscriptionId").val(),
                'trackingNumber': trackingNumber,
                'operator': operator,
                'orderId': jQuery("#orderId").val()
            },
            function (response) {
                response = JSON.parse(response);

                if (response.status === 204) {
                    alert("Confirmation de livraison effectuée");
                } else {
                    alert("Impossible de confirmer la livraison");
                }

                window.location.reload();
            }
        );
    });
});

function createOverlay() {
    let html = '';
    html += '<div id="sg_overlay" data-open="true"><div class="sg_loader"></div></div>';

    jQuery('body').append(html);
}





