<?php
    
    if (!isset($_GET["e"]) || !isset($_GET["r"]) || !isset($_GET["v"]) || !isset($_GET["s"]) )
        die("Erro! não recebi todos os parâmetros!");
    
    $explodedFilePath = explode('wp-content', __FILE__);
    $wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

    require_once $wpLoadFilePath;
    
    global $woocommerce, $wpdb;
    
    $wcep = new WC_Gateway_Easypay_CC_2();
    $order = new WC_Order($_GET['t_key']);
    
    if ($_GET['s'] != 'ok') {
        $wcep->log('[' . basename(__FILE__) . '] #' . $_GET['t_key'] . ' Payment Error: ' . print_r($_GET, true));
        $order->update_status('failed', __('Error on payment request', 'wceasypay'));
    }

    header('Location: ' . $wcep->get_return_url($order));