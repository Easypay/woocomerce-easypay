<?php

/**
 * Plugin Name: WooCommerce Easypay Gateway MBWay
 * Description: Easypay Payment Gateway for WooCommerce - Don't leave for tomorrow what you can receive today
 * Version: 2.00
 * Author: Easypay
 * Author URI: https://easypay.pt
 * Requires at least: 3.5
 * Tested up to: 3.8.1
 *
 * Text Domain: wceasypay
 * Domain Path: /languages/
 *
 * @package Woocommerce-easypay-gateway-mbway
 * @category Gateway
 * @author Easypay
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//
// standart autolader
function ep_autoloader($class_name)
{
    $plugin_dir = realpath(plugin_dir_path(__FILE__));



    $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
    $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
    require_once $classes_dir . $class_file;
}

spl_autoload_register('ep_autoloader');

// Install
require_once 'core/install.php';
register_activation_hook(__FILE__, 'wceasypay_activation_mbway_2');

// Uninstall
require_once 'core/uninstall.php';
register_deactivation_hook(__FILE__, 'wceasypay_deactivation_mbway_2');

//Plugin initialization
add_action('plugins_loaded', 'woocommerce_gateway_easypay_mbway_2_init', 0);
add_action('woocommerce_api_easypay', 'easypay_callback_handler');
add_action('wp_ajax_ep_mbway_check_payment', 'ep_mbway_check_payment');
add_action('wp_ajax_nopriv_ep_mbway_check_payment', 'ep_mbway_check_payment');

function ep_mbway_check_payment()
{
    $ajax_nonce = wp_create_nonce('wp-ep-mbway2-plugin');
    check_ajax_referer('wp-ep-mbway2-plugin', 'wp-ep-nonce');

    $order_key = filter_input(INPUT_GET
        , 'order_key'
        , FILTER_VALIDATE_INT);
    if (is_null($order_key) || false === $order_key) {
        echo json_encode(false);
        wp_die();
    }

    global $wpdb; // this is how you get access to the database
    $notifications_table = $wpdb->prefix . 'easypay_notifications_2';

    $query_string = "SELECT ep_status"
        . " FROM $notifications_table"
        . " WHERE t_key = %s AND ep_status != 'pending'";
    $rset = $wpdb->get_results($wpdb->prepare($query_string, [$order_key]));
    if (empty($rset)) {
        $paid = false;
    } else {
        switch ($rset[0]->ep_status) {
            case 'processed':
                $paid = true;
                break;
            case 'authorized':
            case 'declined':
            case 'failed_capture':
            case 'pending_void':
            case 'voided':
            case 'waiting_capture':
                $paid = false;
                break;
        }
    }

    echo json_encode($paid);
    // this is required to terminate immediately and return a proper response
    wp_die();
}

add_action('wp_ajax_ep_mbway_user_cancelled', 'ep_mbway_user_cancelled');
add_action('wp_ajax_nopriv_ep_mbway_user_cancelled', 'ep_mbway_user_cancelled');

function ep_mbway_user_cancelled()
{
    $ajax_nonce = wp_create_nonce('wp-ep-mbway2-plugin');
    check_ajax_referer('wp-ep-mbway2-plugin', 'wp-ep-nonce');

    $order_key = filter_input(INPUT_GET
        , 'order_key'
        , FILTER_VALIDATE_INT);
    if (is_null($order_key) || false === $order_key) {
        echo json_encode(false);
        wp_die();
    }

    global $wpdb; // this is how you get access to the database
    $notifications_table = $wpdb->prefix . 'easypay_notifications_2';

    $query_string = "SELECT ep_payment_id, t_key, ep_method"
        . " FROM $notifications_table"
        . " WHERE t_key = %s AND ep_status IN ('pending','authorized')";
    $notification = $wpdb->get_results($wpdb->prepare($query_string, [$order_key]));

    if (empty($notification)) {
        $is_cancelled = false;
    } else {
        $ep_payment_id = $notification[0]->ep_payment_id;
        $t_key = $notification[0]->t_key;
        $ep_method = $notification[0]->ep_method;
        unset($notification);
        //
        // go ahead and cancel the order
        // no use sending the goods or providing the service if the user
        // has already show the "will" to cancel
        $order = new WC_Order($order_key);
        $order->update_status('cancelled', 'Cancelled by customer');
        //
        // cancel on easypay
        // we need to detect the gateway we are working with
        switch ($ep_method) {
            case 'cc':
                $wcep = new WC_Gateway_Easypay_CC_2();
                break;

            case 'mb':
                $wcep = new WC_Gateway_Easypay_MB_2();
                break;

            case 'mbw':
                $wcep = new WC_Gateway_Easypay_MBWay_2();
                break;
        }

        if ($wcep->test) {
            $url = 'https://api.test.easypay.pt/2.0/void';
        } else {
            $url = 'https://api.prod.easypay.pt/2.0/void';
        };

        $api_auth = $wcep->easypay_api_auth();
        $auth = [
            'url' => "$url/$ep_payment_id",
            'account_id' => $api_auth['account_id'],
            'api_key' => $api_auth['api_key'],
            'method' => 'POST',
        ];
        $payload = [
            'transaction_key' => $t_key,
            'descriptive' => 'User cancelled',
        ];

        if(!class_exists('WC_Gateway_Easypay_Request')) {
            include_once dirname( __FILE__ ) . '/includes/class-wc-gateway-easypay-request.php';
        }

        $request = new WC_Gateway_Easypay_Request($auth);
        $void_response = $request->get_contents($payload);
        $set = [
            'ep_last_operation_type' => 'void',
            'ep_last_operation_id' => null,
        ];
        $where = [
            'ep_payment_id' => $ep_payment_id,
        ];
        if (empty($void_response)
            || $void_response['status'] != 'ok'
        ) {
            // log and silently discard
            // auth will be voided after X days
            $msg = '[' . basename(__FILE__) . "] Error voiding auth in ep: {$response['message'][0]}";


            (new WC_Logger())->add('easypay', '[' . basename(__FILE__)
                . '] Error voiding auth in ep: ' . $response['message'][0]);
        } else {
            $set['ep_last_operation_id'] = $void_response['id'];
        }
        //
        // keep the void id so we can find the payment
        // from the notification
        $wpdb->update($notifications_table, $set, $where);

        $is_cancelled = true;
    }

    echo json_encode($is_cancelled);
    // this is required to terminate immediately and return a proper response
    wp_die();
}

/**
 * WC Gateway Class - Easypay MBWAY API 2.0
 */
function woocommerce_gateway_easypay_mbway_2_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wceasypay_woocommerce_notice_mbway_2');
        return;
    }

    /**
     * Localisation
     */
    load_plugin_textdomain('wceasypay', false, dirname(plugin_basename(__FILE__)) . '/languages');

//    class WC_Gateway_Easypay_MBWay_2 extends WC_Payment_Gateway
//    {
//    }

    /**
     * Add the Easypay Gateway to WooCommerce
     *
     * @param array $methods
     * @return  array
     */
    include_once dirname(__FILE__)
        . '/includes/class-wc-payment-gateway-easypay-mbway.php';

    function woocommerce_add_gateway_easypay_mbway_2($methods)
    {
        $methods[] = 'WC_Gateway_Easypay_MBWay_2';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_easypay_mbway_2');

    /**
     * Checkout Fields Override
     *
     * @param array $fields
     * @return  array
     */
    function custom_override_checkout_fields_mbway_2($fields)
    {
        $fields['billing']['billing_state']['required'] = false;
        $fields['shipping']['shipping_state']['required'] = false;

        $nif_field = array(
            'label'       => __('Fiscal Number', 'wceasypay'),
            'placeholder' => _x('Fiscal Number', 'placeholder', 'wceasypay'),
            'required'    => false,
            'class'       => array('form-row-wide'),
            'clear'       => true
        );

        $fields['billing']['billing_fiscal_number'] = $nif_field;
        $fields['shipping']['shipping_fiscal_number'] = $nif_field;

        return $fields;
    }

    #add_filter('woocommerce_checkout_fields', ' custom_override_checkout_fields_mbway_2');

    /**
     * Order Billing Details NIF Override
     *
     * @param array $billing_data
     * @return  array
     */
    function custom_override_order_billing_details_nif_mbway_2($billing_data)
    {
        $billing_data['fiscal_number'] = array('label' => __('Fiscal Number', 'wceasypay'), 'show' => true);
        return $billing_data;
    }

    #add_filter('woocommerce_admin_billing_fields', 'custom_override_order_billing_details_nif_mbway_2');

    /**
     * Order Shipping Details NIF Override
     *
     * @param array $shipping_data
     * @return  array
     */
    function custom_override_order_shipping_details_nif_mbway_2($shipping_data)
    {
        $shipping_data['fiscal_number'] = array('label' => __('Fiscal Number', 'wceasypay'), 'show' => true);
        return $shipping_data;
    }

    #add_filter('woocommerce_admin_shipping_fields', 'custom_override_order_shipping_details_nif_mbway_2');


} //END of function woocommerce_gateway_easypay_mb_2_init

/**
 * WooCommerce Gateway Fallback Notice
 *
 * Request to user that Easypay Plugin needs the last vresion of WooCommerce
 */
function wceasypay_woocommerce_notice_mbway_2()
{
    echo '<div class="error"><p>' . __('WooCommerce Easypay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wceasypay') . '</p></div>';
}
