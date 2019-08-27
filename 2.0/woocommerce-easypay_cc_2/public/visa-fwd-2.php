<?php

if (!isset($_GET['e'])
    || !isset($_GET['r'])
    || !isset($_GET['v'])
    || !isset($_GET['s'])
    || !isset($_GET['t_key'])
) {
    die("Error! Missing parameters!");
}

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath = reset($explodedFilePath) . '/wp-load.php';

require_once $wpLoadFilePath;

global $woocommerce, $wpdb;

if (!class_exists('WC_Gateway_Easypay_CC')) {
    include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
        . 'wc-gateway-easypay-cc.php';
}

$wcep = new WC_Gateway_Easypay_CC();
$order = new WC_Order($_GET['t_key']);

if ($_GET['s'] != 'ok') {
    $wcep->log('[' . basename(__FILE__) . '] #' . $_GET['t_key'] . ' Payment Error: ' . print_r($_GET, true));
    $order->update_status('failed', __('Error on payment request', 'wceasypay'));
}

header('Location: ' . $wcep->get_return_url($order));