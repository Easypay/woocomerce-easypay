<?php

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
	exit;
}

require_once $wpLoadFilePath;

global $wpdb;

$wcep = new WC_Gateway_Easypay_MBWay();


$json_payload = json_decode(file_get_contents('php://input'));

if (!$wpdb->insert(
    $wpdb->prefix . 'easypay_transaction_keys_mbway',
    array(
        'username'       => $json_payload->username,
        'cin'            => $json_payload->cin,
        'entity'         => $json_payload->entity,
        'reference'      => $json_payload->reference,
        'key'            => $json_payload->key,
        'type'           => $json_payload->type,
        'status'         => $json_payload->status,
        'last_message'   => $json_payload->status_message,
        'token'          => $json_payload->token
    )
)) {
  // Error

}