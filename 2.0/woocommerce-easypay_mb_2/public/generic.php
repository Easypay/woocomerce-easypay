<?php
/*
 * Receives a generic notification from 2.0 easypay API
 */

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
    exit;
}

require_once $wpLoadFilePath;

global $wpdb;

$wcep = new WC_Gateway_Easypay_MB_2();

include_once '../includes/class-wc-gateway-easypay-request.php';

$api_auth = $wcep->easypay_api_auth();

$auth = [
    "url" => $api_auth['url'],
    "account_id" => $api_auth['account_id'],
    "api_key" => $api_auth['api_key'],
    "method" => 'GET',
];

$request = new WC_Gateway_Easypay_Request($auth);

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];

$response = $request->get_contents($id);

print_r($response);