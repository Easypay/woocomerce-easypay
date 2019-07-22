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
        if (true === response) {
            try_counter = 0;
            updateOrderUI(ep_lng.auth_paid_order_shipped);
            return;
        } else {
            timeoutID = window.setTimeout(function () {
                check_for_payment(ajax_object.ajax_url, data);
            }, timeout);
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

    jQuery('#wc-ep-cancel-order').on('click', function () {

        data.action = 'ep_mbway_user_cancelled';
        window.clearTimeout(timeoutID);
        try_counter = -1;

        jQuery.getJSON(ajax_object.ajax_url, data, function (response) {
            if (true === response) {
                updateOrderUI(ep_lng.user_cancelled_order);
            } else {
                updateOrderUI(ep_lng.cannot_cancel_order);
            }
        }).fail(function () {
            updateOrderUI(ep_lng.cannot_cancel_order);
        });
    });
});

function updateOrderUI(text_msg) {

    var loader = jQuery('div#lds-grid');
    var msg_placeholder = jQuery(loader).next();

    jQuery(loader).fadeOut(function () {
        jQuery(this).empty().remove();
    });
    jQuery('a#wc-ep-cancel-order').fadeOut(function () {
        jQuery(this).parent().remove();
    });
    jQuery(msg_placeholder).text(text_msg).fadeIn();
}