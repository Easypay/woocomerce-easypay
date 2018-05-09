<?php
// Debug
$cenas = array(
        'get' => print_r($_GET, true),
        'post' => print_r($_POST, true),
        'raw body' => file_get_contents('php://input')
        );

file_put_contents('temp.log', print_r($cenas, true), FILE_APPEND);

file_put_contents('temp.log', PHP_EOL . ">>>>>>>" . PHP_EOL, FILE_APPEND);

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
	exit;
}

require_once $wpLoadFilePath;

global $wpdb;

$wcep = new WC_Gateway_Easypay_MBWay();

$json_payload = json_decode(file_get_contents('php://input'));

// #1 - Update

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
  file_put_contents('temp.log', $json_payload->key , FILE_APPEND);

}

// Output
file_put_contents('temp.log', print_r($json_payload, true), FILE_APPEND);
