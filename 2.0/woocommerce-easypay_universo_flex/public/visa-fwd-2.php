<?php
if (!isset($_GET['method'])) {
    if (!isset($_GET['e'])
        || !isset($_GET['r'])
        || !isset($_GET['v'])
        || !isset($_GET['s'])
        || !isset($_GET['t_key'])
    ) {
        die("Error! Missing parameters!");
    }
} else {
    if (!isset($_GET['fwd'])
        || !isset($_GET['id'])
        || !isset($_GET['status'])
        || !isset($_GET['type'])
        || !isset($_GET['transaction_key'])
    ) {
        die("Error! Missing parameters!");
    }
}

$t_key = isset($_GET['t_key']) ? $_GET['t_key'] : $_GET['transaction_key'];

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath = reset($explodedFilePath) . '/wp-load.php';

require_once $wpLoadFilePath;

global $woocommerce, $wpdb;

if (!isset($_GET['method'])) {
    if (!class_exists('WC_Gateway_Easypay_CC')) {
        include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
            . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'wc-gateway-easypay-cc.php';
    }
    $wcep = new WC_Gateway_Easypay_CC();
} else {
    if (!class_exists('WC_Gateway_Easypay_Universo_Flex')) {
        include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
            . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'wc-gateway-easypay-universo-flex.php';
    }

    $wcep = new WC_Gateway_Easypay_Universo_Flex();
}

$order = new WC_Order($t_key);

if ($_GET['s'] != 'ok') {
    $wcep->log('[' . basename(__FILE__) . '] #' . $t_key . ' Payment Error: ' . print_r($_GET, true));
    $order->update_status('failed', __('Error on payment request', 'wceasypay'));
}

header('Location: ' . $wcep->get_return_url($order));