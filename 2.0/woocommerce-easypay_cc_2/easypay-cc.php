<?php

/**
 * Plugin Name: WooCommerce Easypay Gateway Cartao de Credito
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
 * @package Woocommerce-easypay-gateway-cc-2
 * @category Gateway
 * @author Easypay
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Install
require_once 'core/install.php';
register_activation_hook(__FILE__, 'wceasypay_activation_cc_2');

// Uninstall
require_once 'core/uninstall.php';
register_deactivation_hook(__FILE__, 'wceasypay_deactivation_cc_2');

//Plugin initialization
add_action('plugins_loaded', 'woocommerce_gateway_easypay_cc_2_init', 0);
add_action('woocommerce_api_easypay', 'easypay_callback_handler');

/**
 * WC Gateway Class - Easypay CC API 2.0
 */
function woocommerce_gateway_easypay_cc_2_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wceasypay_woocommerce_notice_cc_2');
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
    function woocommerce_add_gateway_easypay_cc_2($methods)
    {
        if (!class_exists('WC_Gateway_Easypay_CC')) {

            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-gateway-easypay-cc.php';
        }

        $methods[] = 'WC_Gateway_Easypay_CC';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_easypay_cc_2');

} //END of function woocommerce_gateway_easypay_cc_2_init

/**
 * WooCommerce Gateway Fallback Notice
 *
 * Request to user that Easypay Plugin needs the last vresion of WooCommerce
 */
function wceasypay_woocommerce_notice_cc_2()
{
    echo '<div class="error"><p>' . __('WooCommerce Easypay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wceasypay') . '</p></div>';
}
