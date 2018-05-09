<?php

/*
* Check order status given the Order ID as a parameter
* Example call: order_checker.php?order_id=<id>
*/

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
	exit;
}

require_once $wpLoadFilePath;

global $wpdb;

$wcep = new WC_Gateway_Easypay_MBWay();
