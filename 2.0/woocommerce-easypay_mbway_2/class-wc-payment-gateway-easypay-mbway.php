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

    $query_string = "SELECT COUNT(t_key)"
        . " FROM $notifications_table"
        . " WHERE t_key = %u AND ep_status != 'pending'";

    $rset = $wpdb->get_results($wpdb->prepare($query_string, [$order_key]));
    if (empty($rset)) {
        $paid = false;
    } else {
        $paid = true;
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

    $order_key = filter_input(INPUT_POST
        , 'order_key'
        , FILTER_VALIDATE_INT);
    if (is_null($order_key) || false === $order_key) {
        echo json_encode(false);
        wp_die();
    }

    global $wpdb; // this is how you get access to the database
    $notifications_table = $wpdb->prefix . 'easypay_notifications_2';

    $query_string = "SELECT COUNT(t_key), ep_payment_id, t_key"
        . " FROM $notifications_table"
        . " WHERE t_key = %u AND ep_status = 'on-hold'";

    $rset = $wpdb->get_results($wpdb->prepare($query_string, [$order_key]));
    if (empty($rset)) {
        $is_cancelled = false;
    } else {

        $set['ep_status'] = 'declined';
        $where = [
            't_key' => $order_key
        ];
        $wpdb->update($notifications_table, $set, $where);

        $order = new WC_Order($order_key);
        $order->update_status('cancelled', 'Cancelled by customer');

        // cancel on easypay
        $auth = [
            'url' => '/void/' . $rset[0]['ep_payment_id'],
            'account_id' => $this->account_id,
            'api_key' => $this->api_key,
            'method' => 'POST',
        ];
        $payload = [
            'transaction_key' => $rset[0]['t_key'],
            'descriptive' => 'User cancelled',
        ];
        $request = new WC_Gateway_Easypay_Request($auth);
        $response = $request->get_contents($payload);
        if ($response['status'] != 'ok') {
            // log and silently discard
            // auth will be voided after X days
            (new WC_Logger())->add('easypay', '[' . basename(__FILE__) . '] Error voiding auth in ep: ' . $response['message'][0]);
        }
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

    class WC_Gateway_Easypay_MBWay_2 extends WC_Payment_Gateway
    {
        /**
         * Gateway's Constructor.
         *
         * One of the Woocommerce required functions
         */

        public function __construct()
        {
            // Class Variables -------------------------------------------------
            // Error
            $this->error = '<div class="error"><p>%s</p></div>';
            $this->ahref = '<a href="' . get_admin_url() . 'admin.php?' .
                'page=wc-settings&amp;' .
                'tab=checkout&amp;' .
                'section=wc_payment_gateway_easypay_mbway_2">';
            $this->a = '</a>';

            // 2.0 API EndPoint
            $this->live_url = 'https://api.prod.easypay.pt/2.0/single';
            $this->test_url = 'https://api.test.easypay.pt/2.0/single';
            // -----------------------------------------------------------------

            // Inherited Variables----------------------------------------------
            $this->id = 'easypay_mbway_2';
            $this->icon = plugins_url('images/logo.png', __FILE__);
            $this->has_fields = false;
            $this->method_title = __('Easypay MBWay', 'wceasypay');
            $this->method_description = __('Don\'t leave for tomorrow what you can receive today', 'wceasypay');
            // -----------------------------------------------------------------

            // Load the form fields (is a function in this class)
            $this->init_form_fields();

            // Woocommerce function
            $this->init_settings();

            // Define user set variables from form_fields function
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->currency = 'EUR';
            $this->expiration_time = $this->get_option('expiration');
            $this->expiration_enable = $this->get_option('expiration_enable');
            $this->autoCapture      = $this->get_option('capture');
            $this->method = "mbw";
            // Auth Stuff
            $this->account_id = $this->get_option('account_id');
            $this->api_key = $this->get_option('api_key');

            // Gateway Testing
            $this->test = $this->get_option('test') == 'yes';
            $this->logs = $this->get_option('logs') == 'yes';
            // -----------------------------------------------------------------

            // Validations
            $this->enabled = $this->gateway_enabled_2();
            $this->gateway_validation_2();
            // -----------------------------------------------------------------

            // Activate logs
            if ($this->logs) {
                $this->logger = new WC_Logger();
            }
            // -----------------------------------------------------------------

            // Load the settings (see admin_options in this class)
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array($this, 'process_admin_options')
            );
            // -----------------------------------------------------------------

            // Action for receipt page (see function in this class)
            add_action(
                'woocommerce_receipt_' . $this->id,
                array($this, 'receipt_page')
            );
            // -----------------------------------------------------------------

        } //END of constructor

        /*
        * Begin of custom order operations
        */
        protected function payment_on_hold($order, $reason = '')
        {
            $order->update_status('on-hold', $reason);
            wc_reduce_stock_levels($order->get_id());
            WC()->cart->empty_cart();
        }

        /*
         * Give Auth
         */
        public function easypay_api_auth() {
            $api_auth['url'] = $this->test ? $this->test_url : $this->live_url;
            $api_auth['account_id'] = $this->account_id;
            $api_auth['api_key'] = $this->api_key;

            return $api_auth;
        }

        /**
         * Check if the settings are correct
         *
         * @return bool
         */
        private function gateway_enabled_2()
        {
            return (($this->get_option('enabled') == 'yes') &&
                !empty($this->account_id) &&
                !empty($this->api_key) &&
                $this->is_valid_for_use()) ? 'yes' : 'no';
        }

        /**
         * Check if the settings are correct
         *
         * @return void
         */
        private function gateway_validation_2()
        {
            if (empty($this->account_id)) {
                add_action('admin_notices', array(&$this, 'error_missing_account_id'));
            }
            if (empty($this->api_key)) {
                add_action('admin_notices', array(&$this, 'error_missing_api_key'));
            }
            if (!$this->is_valid_for_use()) {
                add_action('admin_notices', array(&$this, 'error_invalid_currency'));
            }
            if ($this->expiration_time < 1 || $this->expiration_time > 93) {
                add_action('admin_notices', array(&$this, 'error_invalid_expiration'));
            }
        }

        /**
         * Start Gateway Settings Form Fields.
         *
         * One of the Woocommerce required functions that generates the var $this->settings
         *
         * @return void
         */
         public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'     => array(
                    'title'   => __('Enable/Disable', 'wceasypay'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable Easypay Payment Gateway.', 'wceasypay'),
                    'default' => 'no'
                ),
                'title'       => array(
                    'title'       => __('Title', 'wceasypay'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'wceasypay'),
                    'default'     => __('Easypay MBWay', 'wceasypay'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'   => __('Customer Message', 'wceasypay'),
                    'type'    => 'textarea',
                    'default' => __('Don\'t leave for tomorrow what you can receive today', 'wceasypay')
                ),
                'account_id'         => array(
                    'title'       => __('Account ID', 'wceasypay'),
                    'type'        => 'text',
                    'description' => __('The Account ID You Generated at Easypay Backoffice', 'wceasypay'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'api_key'        => array(
                    'title'       => __('API Key', 'wceasypay'),
                    'type'        => 'text',
                    'description' => __('The API Key You Generated at Easypay Backoffice', 'wceasypay'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'expiration' => array(
                     'title' => __('Expiration in Days', 'wceasypay'),
                     'type' => 'decimal',
                     'description' => __('Only 1 to 93 days accepted', 'wceasypay'),
                     'default' => '1',
                     'desc_tip' => true,
                ),
                'expiration_enable' => array(
                    'title' => __('Enable Expiration for MBWay References', 'wceasypay'),
                    'type' => 'checkbox',
                    'description' => __('Enable This Option to Activate Reference Expiration Time', 'wceasypay'),
                    'default' => 'no',
                    'desc_tip' => true,
                ),
                'capture' => array(
                    'title' => __('Auto Capture', 'wceasypay'),
                    'type' => 'checkbox',
                    'description' => __('Auto request the capture of the authorized transactions .', 'wceasypay'),
                    'default' => false,
                ),
                'testing'     => array(
                    'title'       => __('Gateway Testing', 'wceasypay'),
                    'type'        => 'title',
                    'description' => '',
                ),
                'test'        => array(
                    'title'       => __('Easypay sandbox', 'wceasypay'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable Easypay sandbox', 'wceasypay'),
                    'default'     => 'yes',
                    'description' => __('Easypay sandbox can be used to test payments.', 'wceasypay'),
                    'desc_tip'    => true,
                ),
                'logs'        => array(
                    'title'       => __('Debug', 'wceasypay'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable logging', 'wceasypay'),
                    'default'     => 'no',
                    'description' => __('Log Easypay events such as API requests, the logs will be placed in <code>woocommerce/logs/easypay.txt</code>', 'wceasypay'),
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Admin Panel Options
         *
         * @return void
         */
        public function admin_options()
        {
            //Public_Url is for "Easypay Configurations" urls
            $public_url = get_site_url() . '/wp-content/plugins/' . pathinfo(dirname(__FILE__), PATHINFO_BASENAME) . '/public/';

            echo '<h3>' . __('Easypay standard', 'wceasypay') . '</h3>';
            echo '<p>' . __('Easypay standard works by sending the user to Easypay to enter their payment information.', '') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '<tr>';
            echo '<td><h3>' . __('Easypay Configurations', 'wceasypay') . '</h3></td>';
            echo '<td><p>Configurations that you must perform on your Easypay account.<br/><strong>' . __('Go to "Webservices" > "URL Configuration"', 'wceasypay') . '</strong></p></td>';
            echo '</tr><tr>';
            echo '<td><h4>' . __('Notification URL', 'wceasypay') . '</h4></td>';
            echo '<td><input type="text" size="100" readonly value="' . $public_url . 'generic.php' . '"/></td>';
            echo '</tr>';
            echo '</table>';
        }

        /**
         * Output for the order received page.
         *
         * @return void
         */
        public function receipt_page($order)
        {
            echo $this->generate_form($order);
        }

        /**
         * Generates the form
         *
         * Request a new reference to API 01BG
         *
         * @param integer $order_id
         * @return  string
         */
        public function generate_form($order_id)
        {
            global $wpdb;

            //Preparing
            $order = new WC_Order($order_id);

            if($this->expiration_enable == 'yes') {
              if($this->expiration_time >= 1 || $this->expiration_time <= 93){
                  $max_date = Date('Y-m-d h:m', strtotime("+" . $this->expiration_time . " days"));
              }
            }

            // start to build the body with the ref data
            $body = [
                "type" => "authorisation",
                "key" => (string)$order->get_id(),
                "method" => $this->method,
                "value"	=> floatval($order->get_total()),
                "currency"	=> $this->currency,
                "customer" => [
                    "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    "email" => $order->get_billing_email(),
                    "key" => (string)$order->get_id(),
                    "phone_indicative" => "+351",
                    "phone" => $order->get_billing_phone(),
                    // "fiscal_number" =>"PT123456789",
                ],
            ]; // Commented the fiscal number since the special nif field is commented also

            if(isset($max_date)) {
                $body["expiration_time"] = $max_date;
            }

            $this->log('Payload for order #' . $order->get_id() . ': ' . print_r(json_encode($body), true));

            include_once dirname( __FILE__ ) . '/includes/class-wc-gateway-easypay-request.php';

            $url = $this->test ? $this->test_url : $this->live_url;

            $auth = [
                    "url" => $url,
                    "account_id" => $this->account_id,
                    "api_key" => $this->api_key,
                    "method" => 'POST',
            ];

            // Check if the billing phone is there if not die!
            if(empty($order->get_billing_phone())) {
                return $this->error_btn_order($order, "The phone field must be filled!");
            } else {
                $request = new WC_Gateway_Easypay_Request($auth);
                $data = $request->get_contents($body);
            }

            if ($data['status'] != 'ok') {
                $this->log('Error while requesting payment for Order #' . $order->get_id() . ' [' . $data['message'][0] . ']');
                return $this->error_btn_order($order, $data['message'][0]);

            } else {
                $this->log('Payment created #' . $order->get_id() . ' @' . $data['id'] . ']');
                $note = __('Awaiting for MBWay payment.', 'wceasypay') . PHP_EOL;
                $note .= 'Value: ' . $order->get_total() . '; ' . PHP_EOL;

                $order->add_order_note($note, 0);
            }

            $result = [
                '',
                "Order ID: {$order->get_id()}; ",
                "Payment ID: {$data['id']}; ",
                "Value: {$order->get_total()}; ",
                "Method: {$this->method}"
            ];
            if (!$wpdb->insert(
                $wpdb->prefix . 'easypay_notifications_2',
                [
                    'ep_value'      => $order->get_total(),
                    't_key'         => $order->get_id(),
                    'ep_method'     => $this->method,
                    'ep_payment_id' =>  $data['id']
                ]
            )) {
                $result[0] = 'Error while inserting the new payment in database:';
            } else {
                $result[0] = 'New payment inserted in database:';
            }
            $this->log(implode(PHP_EOL, $result));

            // reduces stock
            $this->payment_on_hold($order, $reason = '');

            // Now do the magic in the class with the query cycle
            return $request->mbway_template((string)$order->get_id());
        }

        /**
         * Order error button.
         *
         * @param object $order Order data
         * @param string $message Error message
         * @return  string
         */
        private function error_btn_order($order, $message = 'Internal Error')
        {
            // Display message if there is problem.
            $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wceasypay') . '</p>';
            if ($this->logs) {
                $html .= '<p><strong>Message</strong>: ' . $message . '</p>';
            }
            $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'wceasypay') . '</a>';

            return $html;
        }


        /**
         * Process the payment and return the result.
         *
         * One of the Woocommerce required functions
         *
         * @param integer $order_id
         * @return  array
         */
        public function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);

            $woocommerce->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        /**
         * Checking if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        private function is_valid_for_use()
        {
            return in_array(get_woocommerce_currency(), array('EUR'));
        }

        // Errors

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_api_key()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing API Key. %sClick here to configure.%s',
                'wceasypay'
            );

            $msgFinal = sprintf($msg, $this->ahref, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_account_id()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing Account ID. %sClick here to configure.%s',
                'wceasypay'
            );

            $msgFinal = sprintf($msg, $this->ahref, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_invalid_currency()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> The currency your cart is using is not valid, please set to Euro (EUR) if you want to use Easypay payments. %sClick here to configure.%s',
                'wceasypay'
            );

            $ahref2 = '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&amp;tab=general">';

            $msgFinal = sprintf($msg, $ahref2, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Log/Debug handler
         *
         * @param string $message
         */
        public function log($message)
        {
            if ($this->logs)
                $this->logger->add('easypay', $message);
        }
    } //END of class WC_Gateway_Easypay

    /**
     * Add the Easypay Gateway to WooCommerce
     *
     * @param array $methods
     * @return  array
     */
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
