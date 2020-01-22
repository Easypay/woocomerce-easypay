<?php
/*
 * Receives a generic notification from 2.0 easypay API
 */

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
    exit;
}

require_once $wpLoadFilePath;

global $wpdb;

if (!class_exists('WC_Easypay_Request')) {
    include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
        . 'wc-easypay-request.php';
}

$ep_notification = json_decode(file_get_contents('php://input'), true);
if (false === $ep_notification) {

    $msg = '[' . basename(__FILE__)
        . '] Bad JSON payload received';
    (new WC_Logger())->add('easypay', $msg);
    print_r([
        'message'   => 'Bad JSON payload received',
        'ep_status' => 'err1',
    ]);
    wp_die();
}

$notifications_table = $wpdb->prefix . 'easypay_notifications_2';
$aux_ep_id = trim($ep_notification['id']);
$aux_ep_id = substr($aux_ep_id, 0, 36);

$query_str = "SELECT ep_key, ep_status, t_key, ep_method, ep_payment_id"
    . " FROM $notifications_table WHERE";
switch ($ep_notification['type']) {
    case 'void':
        $query_str .= ' ep_last_operation_id = "%s"';
        break;
    case 'capture':
    default: /* authorisation */
        $query_str .= ' ep_payment_id = "%s"';
        break;
}
$notification = $wpdb->get_results($wpdb->prepare($query_str, [$aux_ep_id]));

if (empty($notification)) {

    $msg = '[' . basename(__FILE__)
        . '] Error selecting data from database';
    (new WC_Logger())->add('easypay', $msg);
    print_r([
        'message'   => 'Error selecting data from database',
        'ep_status' => 'err1',
    ]);
    wp_die();
}

$ep_key = $notification[0]->ep_key;
$ep_status = $notification[0]->ep_status;
$t_key = $notification[0]->t_key;
$ep_method = $notification[0]->ep_method;
$ep_payment_id = $notification[0]->ep_payment_id;
unset($notification);

if ($ep_status == 'processed') {

    print_r([
        'message'   => 'Document already processed',
        'ep_status' => 'ok0',
    ]);
    exit(0);
}


//
// GET the payment so we can validate this notification
//
// 1st get the gateway class for this payment method
// so we can access the account id and key
switch ($ep_method) {
    case 'cc':
        if (!class_exists('WC_Gateway_Easypay_CC')) {
            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-gateway-easypay-cc.php';
        }
        $wcep = new WC_Gateway_Easypay_CC();
        break;

    case 'mb':
        if (!class_exists('WC_Gateway_Easypay_MB')) {
            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-gateway-easypay-mb.php';
        }
        $wcep = new WC_Gateway_Easypay_MB();
        break;

    case 'mbw':
        if (!class_exists('WC_Gateway_Easypay_MBWay')) {
            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-gateway-easypay-mbway.php';
        }
        $wcep = new WC_Gateway_Easypay_MBWay();
        break;
}

$api_auth = $wcep->easypay_api_auth();

$auth = [
    'url'        => $api_auth['url'],
    'account_id' => $api_auth['account_id'],
    'api_key'    => $api_auth['api_key'],
    'method'     => 'GET',
];
$payment_details = (new WC_Easypay_Request($auth))
    ->get_contents($ep_payment_id);
$default_err = ['status' => '', 'message' => ''];
if (empty(array_diff_key($payment_details, $default_err))) {
    //
    // something's wrong with comms
    wp_die();
} else {
    //
    // this is only for single payments,
    // so we must have just one transaction
    $ep_value = floatval($payment_details['value']);
}
//
// prepare to capture...
if ($ep_method != 'mb') {

    $auth['url'] = $wcep->getCaptureUrl() . "/$ep_payment_id";
    $auth['method'] = 'POST';

    $capture_request = new WC_Easypay_Request($auth);
}
//
// get ready to update the order
$order = new WC_Order($t_key);
$where = [
    'ep_payment_id' => $ep_payment_id,
];
$set = array();
// check for cc errors
if ($ep_method == 'cc') {

    if ($ep_notification['type'] == 'authorisation'
        && $ep_notification['status'] == 'failed'
    ) {
        $set = [
            'ep_status' => 'declined',
        ];
        $wpdb->update($notifications_table, $set, $where);
        print_r($set);
        exit(0);
    }
    //
    // Check if the plugin is set for auto capture
    elseif ($wcep->autoCapture == 'yes'
        && $ep_notification['type'] == 'authorisation'
        && $ep_notification['status'] == 'success'
    ) {
        // Capture
        $body = [
            'transaction_key' => (string)$t_key,
            'capture_date'    => date('Y-m-d'),
            'descriptive'     => (string)$t_key,
            'value'           => $ep_value,
        ];
        //
        // make the capture request to easypay
        $capture_request_response = $capture_request->get_contents($body);
        $set = [
            'ep_status'              => 'waiting_capture',
            'ep_last_operation_type' => 'capture',
            'ep_last_operation_id'   => null,
        ];
        if (!empty($capture_request_response)) {

            $set['ep_last_operation_id'] = $capture_request_response['id'];
            if ($capture_request_response['status'] != 'ok') {

                $msg = '[' . basename(__FILE__)
                    . "] {$capture_request_response['message'][0]}";
                (new WC_Logger())->add('easypay', $msg);
            }
        }

        $p1 = 'pending payment';
        $p2 = 'Card authorized, waiting for capture';

    } elseif ($ep_notification['type'] == 'capture'
        && $ep_notification['status'] == 'success'
    ) {

        $set['ep_status'] = 'processed';
        $p1 = 'completed';
        $p2 = 'Payment completed';

    } else {
        if ($wcep->autoCapture == 'no'
            && $ep_notification['type'] == 'authorisation'
            && $ep_notification['status'] == 'success'
        ) {

            $set = [
                'ep_status' => 'authorized',
            ];
            $p1 = 'pending payment';
            $p2 = 'Card authorized, waiting for capture';
        }
    }

    $wpdb->update($notifications_table, $set, $where);
    $order->update_status($p1, $p2);

} elseif ($ep_method == 'mb'
    && $ep_notification['status'] == 'success') {

    $set = [
        'ep_status' => 'processed',
    ];
    $wpdb->update($notifications_table, $set, $where);
    $order->update_status('completed', 'Payment completed');

} elseif ($ep_method == 'mbw') {

    if ($ep_notification['type'] == 'authorisation'
        && $ep_notification['status'] == 'failed'
    ) {

        $set['ep_status'] = 'declined';
        $p1 = 'cancelled';
        $p2 = $ep_notification['messages'][0];
    } elseif ($ep_notification['type'] == 'authorisation'
        && $ep_notification['status'] == 'success'
        && $wcep->autoCapture == 'yes'
    ) {
        $set['ep_status'] = 'waiting_capture';
        // Capture
        $body = [
            'transaction_key' => (string)$t_key,
            'capture_date'    => date('Y-m-d'),
            'descriptive'     => (string)$t_key,
            'value'           => floatval($ep_value),
        ];
        //
        // make the capture request to easypay
        $capture_request_response = $capture_request->get_contents($body);
        if ($capture_request_response['status'] != 'ok') {

            $set['ep_status'] = 'failed_capture';
        } else {
            //
            // save the capture (operation) id so we can
            // find the payment later when the notification arrives
            $set['ep_last_operation_type'] = 'capture';
            $set['ep_last_operation_id'] = $capture_request_response['id'];
            //
            // update the order status
            $p1 = 'pending payment';
            $p2 = 'Payment authorized, waiting for capture';
        }
    } elseif ($ep_notification['type'] == 'authorisation'
        && $wcep->autoCapture == 'no'
    ) {

        if ($ep_notification['status'] == 'success') {

            $set['ep_status'] = 'authorized';
            $p1 = 'pending payment';
            $p2 = 'Authorisation completed';

        } elseif ($ep_notification['status'] == 'failed') {

            $set['ep_status'] = 'declined';
            $p1 = 'cancelled';
            $p2 = $ep_notification['messages'][0];
        }

    } elseif ($ep_notification['type'] == 'capture') {

        if ($ep_notification['status'] == 'success') {

            $set['ep_status'] = 'processed';
            $p1 = 'completed';
            $p2 = 'Payment completed';
        }

    } elseif ($ep_notification['type'] == 'void') {

        if ($ep_notification['status'] == 'success') {

            $set['ep_status'] = 'voided';
        } elseif ($ep_notification['status'] == 'failed') {

            $set['ep_status'] = 'pending_void';
        }

        $p1 = 'Cancelled';
        $p2 = 'Payment completed';
    }

    if (!empty($notifications_table) && !empty($set) && !empty($where)) {
        $wpdb->update($notifications_table, $set, $where);
    }

    if (isset($p1) && isset($p2)) {
        $order->update_status($p1, $p2);
    }
}

print_r($set);
exit;
