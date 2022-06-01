<?php

if (!isset($_GET['t_key']) || !isset($_GET['id'])) {
    echo "Error: Not enough params";
    exit();
}

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
    exit();
}

require_once $wpLoadFilePath;

global $wpdb;

if (!class_exists('WC_Gateway_Easypay_Universo_Flex')) {
    include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
        . 'wc-gateway-easypay-universo-flex.php';
}
$wcep = new WC_Gateway_Easypay_Universo_Flex();

$api_auth = $wcep->easypay_api_auth();

if ($api_auth['account_id'] != $_GET['id']) {
    echo "Error: Data mismatch";
    exit();
}

$xml = '<?xml version="1.0" encoding="ISO-8859-1" ?>' . PHP_EOL;
//Output XML
$xml.= '<get_detail>' . PHP_EOL;

global $result;

try {
    $order = new WC_Order($_GET['t_key']);

    //Fetch Payment Detail
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'easypay_notifications_2
            WHERE  t_key = ' . $_GET['t_key'];

    $result = $wpdb->get_row($sql);

    //Header
    $xml.= '<ep_status>'      . 'ok'                  .'</ep_status>' . PHP_EOL;
    $xml.= '<ep_message>'     . 'success'             .'</ep_message>' . PHP_EOL;
    $xml.= '<ep_value>'       . $result->ep_value     .'</ep_value>' . PHP_EOL;
    $xml.= '<t_key>'          . $result->t_key        .'</t_key>' . PHP_EOL;
    //Order Information
    $xml.= '<order_info>' . PHP_EOL;

    $xml.= '<total_taxes>'            . ((double) $order->get_total() - (double) $order->get_total_tax()) . '</total_taxes>' . PHP_EOL;
    $xml.= '<total_including_taxes>'  . $order->get_total() . '</total_including_taxes>' . PHP_EOL;
    // Turned off by default on the plugin
    //$xml.= '<bill_fiscal_number>'     . (isset($order->order_custom_fields['_billing_fiscal_number'][0]) ? $order->order_custom_fields['_billing_fiscal_number'][0] : '')                . '</bill_fiscal_number>' . PHP_EOL;
    //$xml.= '<shipp_fiscal_number>'    . (isset($order->order_custom_fields['_shipping_fiscal_number'][0]) ? $order->order_custom_fields['_shipping_fiscal_number'][0] : '')                . '</shipp_fiscal_number>' . PHP_EOL;
    $xml.= '<bill_name>'              . $order->get_billing_first_name()  . ' ' . $order->get_billing_first_name() . '</bill_name>' . PHP_EOL;
    $xml.= '<shipp_name>'             . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . '</shipp_name>' . PHP_EOL;
    $xml.= '<bill_address_1>'         . $order->get_billing_address_1()     . '</bill_address_1>' . PHP_EOL;
    $xml.= '<shipp_adress_1>'         . $order->get_shipping_address_1()    . '</shipp_adress_1>' . PHP_EOL;
    $xml.= '<bill_address_2>'         . $order->get_billing_address_2()     . '</bill_address_2>' . PHP_EOL;
    $xml.= '<shipp_adress_2>'         . $order->get_shipping_address_2()    . '</shipp_adress_2>' . PHP_EOL;
    $xml.= '<bill_city>'              . $order->get_billing_city()          . '</bill_city>' . PHP_EOL;
    $xml.= '<shipp_city>'             . $order->get_shipping_city()         . '</shipp_city>' . PHP_EOL;
    $xml.= '<bill_zip_code>'          . $order->get_billing_postcode()      . '</bill_zip_code>' . PHP_EOL;
    $xml.= '<shipp_zip_code>'         . $order->get_shipping_postcode()     . '</shipp_zip_code>' . PHP_EOL;
    $xml.= '<bill_country>'           . $order->get_billing_country()       . '</bill_country>' . PHP_EOL;
    $xml.= '<shipp_country>'          . $order->get_shipping_country()      . '</shipp_country>' . PHP_EOL;

    $xml.= '</order_info>' . PHP_EOL;

    //Order Items
    $xml.= '<order_detail>' . PHP_EOL;
    foreach ( $order->get_items() as $item ) {
        $xml.= '<item>' . PHP_EOL;
        $xml.= '<item_description>'   . $item['name']       . '</item_description>' . PHP_EOL;
        $xml.= '<item_quantity>'      . $item['qty']        . '</item_quantity>' . PHP_EOL;
        $xml.= '<item_total>'         . $item['line_total'] . '</item_total>' . PHP_EOL;
        $xml.= '</item>' . PHP_EOL;
    }
    $xml.= '</order_detail>' . PHP_EOL;

} catch (Exception $ex) {
    $xml.= '<ep_status>'      . 'err'                   .'</ep_status>' . PHP_EOL;
    $xml.= '<ep_message>'     . $ex->getMessage()       .'</ep_message>' . PHP_EOL;
    $xml.= '<ep_entity>'      . $result->ep_entity      .'</ep_entity>' . PHP_EOL;
    $xml.= '<ep_reference>'   . $result->ep_reference   .'</ep_reference>' . PHP_EOL;
    $xml.= '<ep_value>'       . $result->ep_value       .'</ep_value>' . PHP_EOL;
}

$xml.= '</get_detail>' . PHP_EOL;
echo $xml;