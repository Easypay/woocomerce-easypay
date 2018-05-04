<?php


$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
	exit;
}

require_once $wpLoadFilePath;

global $woocommerce, $wpdb;

$wcep = new WC_Gateway_Easypay();

$date_today = date('Y-m-d');
$date_yesterday = date('Y-m-d', strtotime($date_today . ' - 1 days'));


$args = array(
    'ep_cin' => $wcep->cin,
    'ep_user' => $wcep->user,
    'ep_entity' => $wcep->entity,
    'o_list_type' => 'timestamp',
    'o_ini' => $date_yesterday,
    'o_last' => $date_today,
    'ep_list' => 'creditcard'
);
var_dump($args);

$url = $wcep->get_request_url($wcep->apis['payment_listings'], $args);
$wcep->log('[' . basename(__FILE__) . '] #CronJob Requested URL: ' . $url);

$contents = $wcep->get_contents($url);
$wcep->log('[' . basename(__FILE__) . '] #CronJob Requested Content: ' . $contents);

$obj = simplexml_load_string($contents);
$data = json_decode(json_encode($obj), true);

if ($data['ep_status'] == 'ok0') {
    $tmp = array();
    if (isset($data['cc_payments']['cc_detail']["ep_cin"]))
        $tmp = array($data['cc_payments']['cc_detail']);
    else
        $tmp = $data['cc_payments']['cc_detail'];
    
    foreach ($tmp as $payment) {

        $t_key = $wpdb->get_var('SELECT t_key FROM ' . $wpdb->prefix . 'easypay_transaction_keys WHERE k = \'' . $payment['ep_cc_key'] . '\'');

        $order = new WC_Order($t_key);

        if ($payment['ep_status'] != 'pago') {
            $order->update_status('failed', 'Payment failed.');
        } else {
            $order->update_status('completed', 'Payment completed.');
        }
    }
} else {
    $wcep->log('[' . basename(__FILE__) . '] #CronJob Payment Error: ' . $data['ep_status']);
    $woocommerce->add_error(__('Payment error: ', 'wceasypay') . __('Error on payment listings', 'wceasypay'));
}
