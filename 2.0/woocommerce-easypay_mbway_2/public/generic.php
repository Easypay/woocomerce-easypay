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

include_once '../includes/class-wc-gateway-easypay-request.php';

$ep_data = json_decode(file_get_contents ('php://input'), true);
if (false === $ep_data) {
    (new WC_Logger())->add('easypay', '[' . basename(__FILE__) . '] Bad JSON payload received');
    exit;
}

$notifications_table = $wpdb->prefix . 'easypay_notifications_2';

$aux_ep_id = trim($ep_data['id']);
$aux_ep_id = substr($aux_ep_id, 0,36);

$query_str = "SELECT ep_key, ep_status, t_key, ep_method, ep_payment_id, ep_value FROM $notifications_table WHERE"
    . ' ep_payment_id = "%s"';

$select = sprintf($query_str, $aux_ep_id);

$query = $wpdb->get_results($select, ARRAY_A);

if (!$query || empty($query)) {
    (new WC_Logger())->add('easypay', '[' . basename(__FILE__) . '] Error selecting data from database');
    $temp['message'] = 'error selecting data from database';
    $temp['status'] = 'err1';
}

$id = $query[0]['ep_payment_id'];

// We need to detect the gateway we are working with
switch ($query[0]['ep_method']) {
    case 'cc':
        $wcep = new WC_Gateway_Easypay_CC_2();
        break;

    case 'mb':
        $wcep = new WC_Gateway_Easypay_MB_2();
        break;

    case 'mbw':
        $wcep = new WC_Gateway_Easypay_MBWay_2();
        break;
}

$api_auth = $wcep->easypay_api_auth();

$auth = [
    "url"        => $api_auth['url'],
    "account_id" => $api_auth['account_id'],
    "api_key"    => $api_auth['api_key'],
    "method"     => 'GET',
];

$request = new WC_Gateway_Easypay_Request($auth);
$response = $request->get_contents($id);
$temp = [];

if ($query[0]['ep_status'] == 'processed') {
    $temp['message'] = 'document already processed';
    $temp['ep_status'] = 'ok0';

} else {

    $order = new WC_Order($query[0]['t_key']);

    // check for cc errors
    if ($ep_data['status'] == "failed"
        && $wcep->method == "cc"
        && $ep_data['type'] == "authorisation"
    ) {
        $set = [
            'ep_status'       => 'declined',
            'ep_entity'       => $response['method']['entity'],
            'ep_reference'    => $response['method']['reference'],
            'ep_value'        => $response['value'],
            'ep_payment_type' => $response['method']['type'],
            't_key'           => $response['key'],
        ];

        $wpdb->update($notifications_table, $set, [
            't_key' => $response['key']
        ]);

        print_r($set);

        wp_die();
    }

    // Check if the plugin is set for auto capture
    if ($query[0]['ep_method'] === 'cc') {

        if ($wcep->autoCapture == 'yes'
            && $wcep->method == 'cc'
            && $ep_data['type'] == 'authorisation'
        ) {
            // Capture
            $body = [
                'transaction_key' => (string)$query[0]['t_key'],
                'capture_date' => date('Y-m-d'),
                'descriptive' => (string)$query[0]['t_key'],
                'value' => $response['value'],
            ];

            if ($wcep->test) {
                $url = "https://api.test.easypay.pt/2.0/capture/$id";
            } else {
                $url = "https://api.prod.easypay.pt/2.0/capture/$id";
            };

            $auth = [
                'url' => $url,
                'account_id' => $wcep->account_id,
                'api_key' => $wcep->api_key,
                'method' => 'POST',
            ];

            $capture_request = new WC_Gateway_Easypay_Request($auth);

            $ep_data = $capture_request->get_contents($body);
            // check for errors

            $set = array(
                'ep_status' => 'captured',
                'ep_entity' => $response['method']['entity'],
                'ep_reference' => $response['method']['reference'],
                'ep_value' => $response['value'],
                'ep_payment_type' => $response['method']['type'],
                't_key' => $response['key'],
            );

            $wpdb->update($notifications_table, $set, [
                't_key' => $response['key']
            ]);
            $order->update_status('pending payment', 'Card authorized, waiting for capture');

        } else if ($wcep->autoCapture == 'yes'
            && $wcep->method == 'cc'
            && $ep_data['type'] == 'capture'
        ) {
            // go to processed mode
            $set = array(
                'ep_status' => 'processed',
                'ep_entity' => $response['method']['entity'],
                'ep_reference' => $response['method']['reference'],
                'ep_value' => $response['value'],
                'ep_payment_type' => $response['method']['type'],
                't_key' => $response['key'],
            );

            $wpdb->update($notifications_table, $set, [
                't_key' => $response['key']
            ]);
            $order->update_status('completed', 'Payment completed');

        } else if ($wcep->autoCapture == 'no'
            && $wcep->method == 'cc'
            && $ep_data['type'] == 'authorisation'
        ) {

            $set = array(
                'ep_status' => 'authorized',
                'ep_entity' => $response['method']['entity'],
                'ep_reference' => $response['method']['reference'],
                'ep_value' => $response['value'],
                'ep_payment_type' => $response['method']['type'],
                't_key' => $response['key'],
            );

            $wpdb->update($notifications_table, $set, [
                't_key' => $response['key']
            ]);
            $order->update_status('pending payment', 'Card authorized, waiting for capture');

        }
    } elseif ($query[0]['ep_method'] == 'mb') {

        if ($wcep->method == 'mb') {
            $set = array(
                'ep_status' => 'processed',
                'ep_entity' => $response['method']['entity'],
                'ep_reference' => $response['method']['reference'],
                'ep_value' => $response['value'],
                'ep_payment_type' => $response['method']['type'],
                't_key' => $response['key'],
            );

            $wpdb->update($notifications_table, $set, [
                'ep_reference' => $response['method']['reference']
            ]);
            $order->update_status('completed', 'Payment completed');

        }

    } else if ($query[0]['ep_method'] == 'mbw') {

        $set = [
            'ep_status' => 'processed',
        ];
        $where = [
            'ep_key' => $query[0]['ep_key'],
            'ep_payment_id' => $id,
        ];

        if ($wcep->method == 'mbw'
            && $ep_data['type'] == 'authorisation'
            && $wcep->autoCapture == 'yes'
        ) {
            $set['ep_status'] = 'waiting_capture';
            // Capture
            $body = [
                'transaction_key' => (string)$query[0]['t_key'],
                'capture_date' => date('Y-m-d'),
                'descriptive' => (string)$query[0]['t_key'],
                'value' => floatval($query[0]['ep_value']),
            ];

            if ($wcep->test) {
                $url = "https://api.test.easypay.pt/2.0/capture/$id";
            } else {
                $url = "https://api.prod.easypay.pt/2.0/capture/$id";
            };

            $auth = [
                'url' => $url,
                'account_id' => $wcep->account_id,
                'api_key' => $wcep->api_key,
                'method' => 'POST',
            ];
            $capture_request = new WC_Gateway_Easypay_Request($auth);

            $ep_data = $capture_request->get_contents($body);
            if ($ep_data['status'] != 'ok') {

                $set['ep_status'] = 'failed_capture';
            }

            $wpdb->update($notifications_table, $set, $where);
            $order->update_status('pending payment', 'Payment authorized, waiting for capture');

        } elseif ($wcep->method == 'mbw'
            && $ep_data['type'] == 'authorisation'
            && $wcep->autoCapture != 'yes'
        ) {

            if ($ep_data['status'] == 'success') {

                $set['ep_status'] = 'processed';
                $order->update_status('completed', 'Authorisation completed');
            } elseif ($ep_data['status'] == 'failed') {

                $set['ep_status'] = 'declined';
                $order->update_status('cancelled', $ep_data['messages'][0]);
            }

            $wpdb->update($notifications_table, $set, $where);

        } elseif ($wcep->method == 'mbw'
            && $ep_data['type'] == 'capture'
        ) {

            if ($ep_data['status'] == 'success') {

                $set['ep_status'] = 'captured';
                $order->update_status('completed', 'Payment completed');
            } elseif ($ep_data['status'] == 'failed') {

                $set['ep_status'] = 'refused';
                $order->update_status('cancelled', $ep_data['messages'][0]);
            }

            $wpdb->update($notifications_table, $set, $where);
        }
    }

    print_r($set);
    exit;

}

