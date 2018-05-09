<?php
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

file_put_contents('temp.log', print_r($json_payload, true), FILE_APPEND);
