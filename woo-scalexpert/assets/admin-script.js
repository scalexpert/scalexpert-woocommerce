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
        alert(environment + '  API credentials are invalid !');
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
                alert(environment + response)
            } else {
                alert(environment + " API connection error !")
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