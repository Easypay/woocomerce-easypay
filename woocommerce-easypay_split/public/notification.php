<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$easypay_ips = array('127.0.0.1', '195.22.18.130', '195.22.18.133', '46.101.9.8', '46.101.92.191');

/*
if (!in_array($_SERVER['REMOTE_ADDR'], $easypay_ips)) {
	die("No valid IP" . $_SERVER['REMOTE_ADDR']);
}
*/

if (!isset($_GET['ep_doc']) || !isset($_GET['ep_cin']) || !isset($_GET['ep_user'])) {
	die("No valid Params!" . $_GET['ep_doc'] . " _ " . $_GET['ep_cin'] ." _ ". $_GET['ep_user']);
}

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
	exit;
}

require_once $wpLoadFilePath;

global $wpdb;

$wcep = new WC_Gateway_Easypay_MB();

$data_to_insert = array(
    'ep_doc' => $_GET['ep_doc'],
    'ep_cin' => $_GET['ep_cin'],
    'ep_user' => $_GET['ep_user']
);

$url = $wcep->get_request_url( $wcep -> apis['request_payment_info'], $data_to_insert );
$wcep->log('[' . basename(__FILE__) . '] Requested URL: ' . $url);

$contents = $wcep->get_contents($url);
$wcep->log('[' . basename(__FILE__) . '] Requested Content: ' . $contents);

$obj = simplexml_load_string($contents);
$data = json_decode(json_encode($obj), true);

$temp['select'] = sprintf( "SELECT ep_key, ep_status FROM %seasypay_notifications WHERE ep_reference = '%s'", $wpdb -> prefix, $data['ep_reference'] );
$temp['mesage'] = 'document generated';
$temp['status'] = 'ok0';

$result = $wpdb->get_results( $temp['select'], ARRAY_A );

if (!$result) {
	$wcep -> log('[' . basename(__FILE__) . '] Error selecting data from database');
	$temp['mesage'] = 'error selecting data from database';
	$temp['status'] = 'err1';
}

//Once it has an entry on database, we check the status to see if needs further actions
if ( $result['ep_status'] == 'processed' ) {
	$temp['mesage'] = 'document already processed';
	$temp['ep_status'] = 'ok0';
} else {
	$set = array(
    'ep_doc' => $_GET['ep_doc'],
    'ep_cin' => $_GET['ep_cin'],
    'ep_user' =>$_GET['ep_user'],
    'ep_status' => 'processed',
    'ep_entity' => $data['ep_entity'],
    'ep_reference' => $data['ep_reference'],
    'ep_value' => $data['ep_value'],
    'ep_date' => $data['ep_date'],
    'ep_payment_type' => $data['ep_payment_type'],
    'ep_value_fixed' => $data['ep_value_fixed'],
    'ep_value_var' => $data['ep_value_var'],
    'ep_value_tax' => $data['ep_value_tax'],
    'ep_value_transf' => $data['ep_value_transf'],
    'ep_date_transf' => $data['ep_date_transf'],
    't_key' => $data['t_key']
	);

	$wcep -> log('[' . basename(__FILE__) . '] Notification Data: ' . print_r($data_to_insert, true) . print_r($set, true));
	$wpdb->update($wpdb->prefix . 'easypay_notifications', $set, array('ep_reference' => $data['ep_reference']));

	$order = new WC_Order($data['t_key']);
	$order->update_status('completed', 'Payment completed');
}

header('Content-type: text/xml; charset="ISO-8859-1"');

?>
<?= '<?xml version="1.0" encoding="ISO-8859-1"?>' ?>
<getautoMB_key>
  <ep_status><?= $temp['status'] ?></ep_status>
  <ep_message><?= $temp['mesage'] ?></ep_message>
  <ep_cin><?= $_GET['ep_cin'] ?></ep_cin>
  <ep_user><?= $_GET['ep_user'] ?></ep_user>
  <ep_doc><?= $_GET['ep_doc'] ?></ep_doc>
  <ep_key><?= $result['ep_key'] ?></ep_key>
</getautoMB_key>
