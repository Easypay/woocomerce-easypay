<?php

/**
 * Plugin Name: WooCommerce Easypay Gateway Santander Consumer
 * Description: Easypay Payment Gateway for WooCommerce - Don't leave for tomorrow what you can receive today
 * Version: 2.00
 * Author: Easypay
 * Author URI: https://easypay.pt
 * Requires at least: 3.6
 * Tested up to: 3.6.4
 *
 * Text Domain: wceasypay
 * Domain Path: /languages/
 *
 * @package Woocommerce-easypay-gateway-santander-consumer
 * @category Gateway
 * @author Easypay
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Install
require_once 'core/install.php';
register_activation_hook(__FILE__, 'wceasypay_activation_santander_consumer');

// Uninstall
require_once 'core/uninstall.php';
register_deactivation_hook(__FILE__, 'wceasypay_deactivation_santander_consumer');

//Plugin initialization
add_action('plugins_loaded', 'woocommerce_gateway_easypay_santander_consumer_init', 0);
add_action('woocommerce_api_easypay', 'easypay_callback_handler');

/**
 * WC Gateway Class - Easypay CC API 2.0
 */
function woocommerce_gateway_easypay_santander_consumer_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wceasypay_woocommerce_notice_santander_consumer');
        return;
    }

    /**
     * Localisation
     */
    load_plugin_textdomain('wceasypay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    /**
     * Add the Easypay Gateway to WooCommerce
     *
     * @param array $methods
     * @return  array
     */
    function woocommerce_add_gateway_easypay_santander_consumer($methods)
    {
        if (!class_exists('WC_Gateway_Easypay_Santander_Consumer')) {

            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-gateway-easypay-santander-consumer.php';
        }

        $methods[] = 'WC_Gateway_Easypay_Santander_Consumer';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_easypay_santander_consumer');

    /**
     * Filter for santander consumer Finance for payment total amount products in the cart including
     * the shipping costs and fees that is under 300€ and over 6000€
     * @param $available_gateways
     * @return mixed
     */
    function easypay_a3ee7c47_2aae_4509_bf31_f693e340acac_conditional_payment_gateways($available_gateways)
    {
        if (is_admin()) {
            return $available_gateways;
        }

        // STEP 1: Get order/cart total
        if (is_wc_endpoint_url('order-pay')) { // Pay for order page

            $order_id = wc_get_order_id_by_order_key($_GET['key']);
            $order = wc_get_order($order_id);
            $order_total = $order->get_total();

        } else { // Cart/Checkout page
            $order_total = WC()->cart->total;
        }

        // STEP 2: Disable payment gateway if order/cart total is more than 6000 and less than 300
        if ($order_total < 300 || $order_total > 6000) {
            unset($available_gateways['easypay_santander_consumer']); // unset Cash on Delivery
        }
        return $available_gateways;
    }


    //Filter for santander consumer Finance for payment total amount products int the cart including
    // the shipping costs and fees that is under 300€ and over 6000€
    add_filter('woocommerce_available_payment_gateways',
        'easypay_a3ee7c47_2aae_4509_bf31_f693e340acac_conditional_payment_gateways');

} //END of function woocommerce_gateway_easypay_santander_consumer_init

/**
 * WooCommerce Gateway Fallback Notice
 *
 * Request to user that Easypay Plugin needs the last vresion of WooCommerce
 */
function wceasypay_woocommerce_notice_santander_consumer()
{
    echo '<div class="error"><p>' . __('WooCommerce Easypay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!',
            'wceasypay') . '</p></div>';
}
