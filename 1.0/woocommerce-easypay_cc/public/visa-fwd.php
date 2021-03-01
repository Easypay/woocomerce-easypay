<?php
    
    if (!isset($_GET["e"]) || !isset($_GET["r"]) || !isset($_GET["v"]) || !isset($_GET["s"]) )
        die("Erro! não recebi todos os parâmetros!");
    
    $explodedFilePath = explode('wp-content', __FILE__);
    $wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

    require_once $wpLoadFilePath;
    
    global $woocommerce, $wpdb;
    
    $wcep = new WC_Gateway_Easypay_CC();
    $order = new WC_Order($_GET['t_key']);
    
    if ($_GET['s'] == 'ok' && $wcep->autoCapture == 'yes') {
        $args = array(
                      'e' => $_GET["e"],
                      'r' => $_GET["r"],
                      'v' => $_GET["v"],
                      'k' => $_GET["k"]
                      );
        $url = $wcep->get_request_url($wcep->apis['request_payment'], $args);
        $wcep->log('[' . basename(__FILE__) . '] #' . $_GET['t_key'] . ' Requested URL: ' . $url);
        
        $contents = $wcep->get_contents($url);
        $wcep->log('[' . basename(__FILE__) . '] #' . $_GET['t_key'] . ' Requested Content: ' . $contents);
        
        $obj = simplexml_load_string($contents);
        $data = json_decode(json_encode($obj), true);
        
        if ($data['ep_status'] == 'ok0')
            $order->payment_complete();
        else {
            $wcep->log('[' . basename(__FILE__) . '] #' . $_GET['t_key'] . ' Payment Error: ' . $data['ep_status']);
            $order->update_status('failed');
        }
    } else if($_GET['s'] == 'ok') {
        $order->payment_complete();
    } else {
        $wcep->log('[' . basename(__FILE__) . '] #' . $_GET['t_key'] . ' Payment Error: ' . print_r($_GET, true));
        $order->update_status('failed', __('Error on payment request', 'wceasypay'));
    }
    //Adding 'k' to order
    $wpdb->insert( 
                  $wpdb->prefix . 'easypay_transaction_keys', 
                  array( 
                        't_key' => $_GET['t_key'], 
                        'k' => $_GET['k'] 
                        )
                  );
    
    header('Location: ' . $wcep->get_return_url($order));