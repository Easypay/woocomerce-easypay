var try_counter = 7;
var timeout = 3000;
var data = {
    'action': 'ep_mbway_check_payment',
    'order_key': ajax_object.order_key,
    'wp-ep-nonce': ajax_object.nonce,
};

function check_for_payment(url, data) {
    if (try_counter <= 0) {
        alert('No more tries left!');
        return;
    }

    jQuery.getJSON(url, data, function (response) {
        if (true === response) {
            alert('Paid for! Doing something...');
            try_counter = 0;
        } else {
            window.setTimeout(function () {
                check_for_payment(ajax_object.ajax_url, data);
            }, timeout);
        }
    }).fail(function () {
        alert('Request failed! Doing something...');
        try_counter = 0;
    }).always(function () {
        --try_counter;
    });
}

jQuery(document).ready(function () {

    window.setTimeout(function () {
        check_for_payment(ajax_object.ajax_url, data);
    }, timeout);
});