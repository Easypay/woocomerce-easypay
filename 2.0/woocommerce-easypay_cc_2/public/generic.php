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

$wcep = new WC_Gateway_Easypay_CC_2();

include_once '../includes/class-wc-gateway-easypay-request.php';

$api_auth = $wcep->easypay_api_auth();

$auth = [
    "url"        => $api_auth['url'],
    "account_id" => $api_auth['account_id'],
    "api_key"    => $api_auth['api_key'],
    "method"     => 'GET',
];

$request = new WC_Gateway_Easypay_Request($auth);

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];

$response = $request->get_contents($id);

$temp = [];

if($response["type"] == "authorisation" || $response["type"] == "capture") {
    $select = sprintf("SELECT ep_key, ep_status, t_key FROM %seasypay_notifications_2 WHERE t_key = '%s'", $wpdb->prefix, $response['key']);
} else {
    $select = sprintf("SELECT ep_key, ep_status, t_key FROM %seasypay_notifications_2 WHERE ep_reference = '%s'", $wpdb->prefix, $response['method']['reference']);
}

$query = $wpdb->get_results($select, ARRAY_A);

if (!$query) {
    $wcep->log('[' . basename(__FILE__) . '] Error selecting data from database');
    $temp['message'] = 'error selecting data from database';
    $temp['status'] = 'err1';
}

if ($query[0]['ep_status'] == 'processed') {
    $temp['message'] = 'document already processed';
    $temp['ep_status'] = 'ok0';

} else {

    $order = new WC_Order($query[0]['t_key']);

    // Check if the plugin is set for auto capture
    if ($wcep->autoCapture == "yes" && $wcep->method == "cc" && $data['type'] == "authorisation") {
        // Capture
        $body = [
            "transaction_key" => (string)$query[0]['t_key'],
            "capture_date"    => date("Y-m-d"),
            "descriptive"     => (string)$query[0]['t_key'],
            "value"           => $response['value'],
        ];

        if ($wcep->test) {
            $url = "https://api.test.easypay.pt/2.0/capture/" . $id;
        } else {
            $url = "https://api.prod.easypay.pt/2.0/capture/" . $id;
        };

        $auth = [
            "url"        => $url,
            "account_id" => $wcep->account_id,
            "api_key"    => $wcep->api_key,
            "method"     => 'POST',
        ];

        $capture_request = new WC_Gateway_Easypay_Request($auth);

        $data = $capture_request->get_contents($body);
        // check for errors

        $set = array(
            'ep_status'       => 'captured',
            'ep_entity'       => $response['method']['entity'],
            'ep_reference'    => $response['method']['reference'],
            'ep_value'        => $response['value'],
            'ep_payment_type' => $response['method']['type'],
            't_key'           => $response['key'],
        );

        $wpdb->update($wpdb->prefix . 'easypay_notifications_2', $set, array('ep_reference' => $response['method']['reference']));
        $order->update_status('pending payment', 'Card authorized, waiting for capture');


    } else if ($wcep->autoCapture == "no" && $wcep->method == "cc" && $data['type'] == "authorisation") {
        $set = array(
            'ep_status'       => 'authorized',
            'ep_entity'       => $response['method']['entity'],
            'ep_reference'    => $response['method']['reference'],
            'ep_value'        => $response['value'],
            'ep_payment_type' => $response['method']['type'],
            't_key'           => $response['key'],
        );

        $wpdb->update($wpdb->prefix . 'easypay_notifications_2', $set, array('ep_reference' => $response['method']['reference']));
        $order->update_status('pending payment', 'Card authorized, waiting for capture');

    } else if ($wcep->autoCapture == "yes" && $wcep->method == "cc" && $data['type'] == "capture") {
        // go to processed mode
        $set = array(
            'ep_status'       => 'processed',
            'ep_entity'       => $response['method']['entity'],
            'ep_reference'    => $response['method']['reference'],
            'ep_value'        => $response['value'],
            'ep_payment_type' => $response['method']['type'],
            't_key'           => $response['key'],
        );

        $wpdb->update($wpdb->prefix . 'easypay_notifications_2', $set, array('ep_reference' => $response['method']['reference']));
        $order->update_status('completed', 'Payment completed');

    } else if ($wcep->method == "mb") {
        $set = array(
            'ep_status'       => 'processed',
            'ep_entity'       => $response['method']['entity'],
            'ep_reference'    => $response['method']['reference'],
            'ep_value'        => $response['value'],
            'ep_payment_type' => $response['method']['type'],
            't_key'           => $response['key'],
        );

        $wpdb->update($wpdb->prefix . 'easypay_notifications_2', $set, array('ep_reference' => $response['method']['reference']));
        $order->update_status('completed', 'Payment completed');

    }

    print_r($set);
}

