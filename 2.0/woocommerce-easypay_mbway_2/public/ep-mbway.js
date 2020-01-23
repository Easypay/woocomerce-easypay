var try_counter = 100;
var timeout = 3000;
var data = {
    'action': 'ep_mbway_check_payment',
    'order_key': ajax_object.order_key,
    'wp-ep-nonce': ajax_object.nonce,
};
var timeoutID;

function check_for_payment(url, data) {
    if (try_counter <= 0) {
        updateOrderUI(ep_lng.auth_canceled_order_cancelled);
        return;
    }

    jQuery.getJSON(url, data, function (response) {
        if (false === response) {
            timeoutID = window.setTimeout(function () {
                check_for_payment(ajax_object.ajax_url, data);
            }, timeout);
        } else {
            switch (response) {
                case 'processed':
                    try_counter = 0;
                    updateOrderUI(ep_lng.auth_paid_order_shipped);
                    return;

                case 'declined':
                    try_counter = 0;
                    updateOrderUI(ep_lng.auth_declined_order_cancelled);
                    return;

                case 'voided':
                    try_counter = 0;
                    updateOrderUI(ep_lng.auth_voided_order_cancelled);
                    return;

                case 'authorized':
                case 'failed_capture':
                case 'pending_void':
                case 'waiting_capture':
                    timeoutID = window.setTimeout(function () {
                        check_for_payment(ajax_object.ajax_url, data);
                    }, timeout);
                    break;
            }
        }

    }).fail(function () {
        updateOrderUI(ep_lng.request_failed);
        try_counter = 0;
    }).always(function () {
        --try_counter;
    });
}

jQuery(document).ready(function () {

    timeoutID = window.setTimeout(function () {
        check_for_payment(ajax_object.ajax_url, data);
    }, timeout);
});

function updateOrderUI(text_msg) {

    var loader = jQuery('div#lds-grid');
    var msg_placeholder = jQuery(loader).next();

    jQuery(loader).fadeOut(function () {
        jQuery(this).empty().remove();
    });
    jQuery(msg_placeholder).text(text_msg).fadeIn();
}