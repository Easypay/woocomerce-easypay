<?php

/**
 * Plugin Name: WooCommerce Easypay Gateway Multi Banco
 * Description: Easypay Payment Gateway for WooCommerce - Don't leave for tomorrow what you can receive today
 * Version: 1.00 - Beta
 * Author: Easypay
 * Author URI: https://easypay.pt
 * Requires at least: 3.5
 * Tested up to: 3.8.1
 *
 * Text Domain: wceasypay
 * Domain Path: /languages/
 *
 * @package Woocommerce-easypay-gateway-mb
 * @category Gateway
 * @author André Filipe
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Install
require_once 'core/install.php';
register_activation_hook(__FILE__, 'wceasypay_activation_mb');
// Uninstall
require_once 'core/uninstall.php';
register_deactivation_hook(__FILE__, 'wceasypay_deactivation_mb');

//Plugin initialization
add_action('plugins_loaded', 'woocommerce_gateway_easypay_mb_init', 0);
add_action('woocommerce_api_easypay', 'easypay_callback_handler');

/**
 * WC Gateway Class - Easypay MB
 */
function woocommerce_gateway_easypay_mb_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wceasypay_woocommerce_notice_mb');
        return;
    }

    /**
     * Localisation
     */
    load_plugin_textdomain('wceasypay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    class WC_Gateway_Easypay_MB extends WC_Payment_Gateway
    {
        /**
         * Gateway's Constructor.
         *
         * One of the Woocommerce required functions
         */

        public function __construct()
        {
            // Variable to access other woocommerce variables and functions
            global $woocommerce, $data;
            // Class Variables -------------------------------------------------
            // Error
            $this->error = '<div class="error"><p>%s</p></div>';
            $this->ahref = '<a href="' . get_admin_url() . 'admin.php?' .
                'page=wc-settings&amp;' .
                'tab=checkout&amp;' .
                'section=wc_payment_gateway_easypay_mb">';
            $this->a = '</a>';

            // 2.0 API EndPoint
            $this->live_url = 'https://api.prod.easypay.pt/2.0/single';
            $this->test_url = 'https://api.test.easypay.pt/2.0/single';
            // -----------------------------------------------------------------

            // Inherited Variables----------------------------------------------
            $this->id = 'easypay_mb';
            $this->icon = plugins_url('images/logo.png', __FILE__);
            $this->has_fields = false;
            $this->method_title = __('Easypay MB', 'wceasypay');
            $this->method_description = __('Don\'t leave for tomorrow what you can receive today', 'wceasypay');
            // -----------------------------------------------------------------

            // Load the form fields (is a function in this class)
            $this->init_form_fields();

            $this->init_settings(); // Woocommerce function

            // Define user set variables from form_fields function
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->currency = 'EUR';
            $this->expiration_time = $this->get_option('expiration');
            $this->expiration_enable = $this->get_option('expiration_enable');
            $this->method = "mb";
            // Auth Stuff
            $this->account_id = $this->get_option('account_id');
            $this->api_key = $this->get_option('api_key');

            // Gateway Testing
            $this->test = $this->get_option('test') == 'yes';
            $this->logs = $this->get_option('logs') == 'yes';
            // -----------------------------------------------------------------

            // Validations
            $this->enabled = $this->gateway_enabled();
            $this->gateway_validation();
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

            // Send Email
            add_action(
                'woocommerce_email_after_order_table',
                array($this, 'reference_in_mail'),
                10,
                3
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

        /**
         * Put the reference, entity and value in the email.
         *
         * @param   $order
         * @param   $sent_to_admin
         * @param   $plain_text
         * @return  void
         */
        public function reference_in_mail($order, $sent_to_admin)
        {
            if ($order->get_payment_method() == 'easypay_mb') {
                global $wpdb;
                if (!$sent_to_admin) {
                    // Log
                    $this->log('A new mail for client');
                    // Search entity, reference and value in database for this $order->get_id()
                    $row = $wpdb->get_row($wpdb->prepare(
                        "
                        SELECT *
                        FROM " . $wpdb->prefix . "easypay_notifications
                        WHERE t_key = %d;
                        ",
                        $order->get_id()
                    ));
                    if ($row != null) {
                        // Do a log
                        $result = 'Data correctly search from database:' . PHP_EOL;
                        $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                        $result .= 'Entity: ' . $row->ep_entity . ';' . PHP_EOL;
                        $result .= 'Value: ' . $row->ep_value . ';' . PHP_EOL;
                        $result .= 'Reference: ' . $row->ep_reference . ';' . PHP_EOL;
                        $this->log($result);
                        // Output the reference, entity and value in email
                        ?>
                        <br/>
                        <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;"
                               bordercolor="#eee">
                            <tr>
                                <td colspan="5">
                                    <!-- Alterar logo -->
                                    <img
                                            src="http://store.easyp.eu/img/easypay_logo_nobrands-01.png"
                                            style="max-width:120px;"
                                            title="Se quer pagar uma referência multibanco utilize a easypay"
                                            alt="Se quer pagar uma referência multibanco utilize a easypay"
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><strong>Entidade: </strong></td>
                                <td><?= $row->ep_entity ?></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><strong>Referência: </strong></td>
                                <td><?= $row->ep_reference ?></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><strong>Valor: </strong></td>
                                <td><?= $row->ep_value ?>&nbsp;&euro;</td>
                            </tr>
                        </table>
                        <?php
                    } else {
                        $result = 'Error while search data in database:' . PHP_EOL;
                        $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                        $this->log($result);
                        die("Error while search data in database...");
                    }
                } else {
                    // Log
                    $this->log('A new mail for administrator');
                }
            }
            return;
        }

        /**
         * Check if the settings are correct
         *
         * @return bool
         */
        private function gateway_enabled()
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
        private function gateway_validation()
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
            if ($this->expiration < 1 || $this->expiration > 93) {
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
                    'default'     => __('Easypay MB', 'wceasypay'),
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
                    'title' => __('Enable Expiration for MB References', 'wceasypay'),
                    'type' => 'checkbox',
                    'description' => __('Enable This Option to Activate Reference Expiration Time', 'wceasypay'),
                    'default' => 'no',
                    'desc_tip' => true,
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
            echo '<td><input type="text" size="100" readonly value="' . $public_url . 'notification.php' . '"/></td>';
            echo '</tr><tr>';
            echo '<td><h3>' . __('Easypay Configurations', 'wceasypay') . ' ' . __('On your server', 'wceasypay') . '</h3></td>';
            echo '<td>' . __('For Credit Card payment check you must create a cron job, we sugest you config your cron job to call this file once a day.', 'wceasypay') . '</td>';
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
            global $woocommerce, $orderReferenceHtml, $wpdb;

            //Preparing
            $order = new WC_Order($order_id);

            $args = array(
                'ep_cin'        => $this->cin,
                'ep_user'       => $this->user,
                'ep_ref_type'   => $this->ref_type,
                'ep_entity'     => $this->entity,
                't_key'         => $order->get_id(),
                't_value'       => $order->get_total(),
                'ep_country'    => $this->country,
                'ep_language'   => $this->language,
                'o_name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'o_description' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'o_obs'         => '',
                'o_mobile'      => $order->get_billing_phone(),
                'o_email'       => $order->get_billing_email()
            );

            if($this->expiration_enable == 'yes') {
              if($this->expiration_time >= 1 || $this->expiration_time <= 93){
                  $max_date = Date('Y-m-d h:m', strtotime("+" . $this->expiration_time . " days"));
              }
            }

            // start to build the body with the ref data
            $body = [
                "key" => "$order->get_id()",
                "method" => "{$this->method}",
                "value"	=> floatval($order->get_total()),
                "currency"	=> "$this->currency",
                "expiration_time" =>"{$max_date}",
                "customer" => [
                    "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    "email" => $order->get_billing_email(),
                    "key" => $order->get_id(),
                    "phone_indicative" => "+351",
                    "phone" => $order->get_billing_phone(),
                    // "fiscal_number" =>"PT123456789",
                ],
            ]; // Commented the fiscal number since the special nif field is commented also

            $this->log('Payload for order #' . $order->get_id() . ': ' . print_r(json_encode($body), true));
            /*
            $url = $this->get_request_url($this->apis['request_reference'], $args);

            $this->log('Request URL #' . $order->get_id() . ': ' . $url);
            */

            $contents = $this->get_contents($body);

            $obj = simplexml_load_string($contents);
            $data = json_decode(json_encode($obj), true);

            if (!$data) {
                $this->log('Error while requesting reference 1 #' . $order->get_id() . ' [' . $contents . ']');
                return $this->error_btn_order($order, 'Not enough data.');
            }
            if ($data['ep_status'] != 'ok0') {
                $this->log('Error while requesting reference 2 #' . $order->get_id() . ' [' . $data['ep_message'] . ']');
                return $this->error_btn_order($order, $data['ep_message']);
            } else {
                $this->log('Reference created #' . $order->get_id() . ' @' . $data['ep_reference'] . ']');
                $note = __('Awaiting for reference payment.', 'wceasypay') . PHP_EOL;
                $note .= 'Entity: ' . $data['ep_entity'] . '; ' . PHP_EOL;
                $note .= 'Value: ' . $data['ep_value'] . '; ' . PHP_EOL;
                $note .= 'Reference: ' . $data['ep_reference'] . '; ' . PHP_EOL;

                $order->add_order_note($note, 0);
            }

            if (!$wpdb->insert(
                $wpdb->prefix . 'easypay_notifications',
                array(
                    'ep_entity'    => $data['ep_entity'],
                    'ep_value'     => $data['ep_value'],
                    'ep_reference' => $data['ep_reference'],
                    't_key'        => $order->get_id(),
                )
            )) {
                $result = 'Error while inserting the new generated reference in database:' . PHP_EOL;
                $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                $result .= 'Entity: ' . $data['ep_entity'] . ';' . PHP_EOL;
                $result .= 'Value: ' . $data['ep_value'] . ';' . PHP_EOL;
                $result .= 'Reference: ' . $data['ep_reference'] . ';' . PHP_EOL;
                $this->log($result);
                #die("Error: I couldn't insert the reference in the database!");
            } else {
                $result = 'New data inserted in database:' . PHP_EOL;
                $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                $result .= 'Entity: ' . $data['ep_entity'] . ';' . PHP_EOL;
                $result .= 'Value: ' . $data['ep_value'] . ';' . PHP_EOL;
                $result .= 'Reference: ' . $data['ep_reference'] . ';' . PHP_EOL;
                $this->log($result);
            }

            // reduces stock
            $this->payment_on_hold($order, $reason = '');

            // Send Email
            add_action(
                'mail_the_guy',
                array($this, 'reference_in_mail'),
                10,
                2
            );
            do_action('mail_the_guy', $order, $data);

            return $this->get_reference_html($data);
        }

        /**
         * Returns a string from a link via cUrl
         *
         * @param array $payload
         * @return string
         */
        public function get_contents($payload)
        {
            $url = $this->test ? $this->test_url : $this->live_url;
            if (function_exists('curl_init')) {
                $headers = [
                    "AccountId: {$this->account_id}",
                    "ApiKey: {$this->api_key}",
                    'Content-Type: application/json',
                ];

                $curlOpts = [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => 1,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => $headers,
                ];

                $curl = curl_init();
                curl_setopt_array($curl, $curlOpts);
                $response_body = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response_body, true);

                return $response;

            } else {
                return file_get_contents($url);
            }
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
         * Returns the Checkout HTML code
         *
         * @param array $reference
         * @param string $html
         * @return  string
         */

        private function get_reference_html($reference, $html = '')
        {
            $html .= '<div style="float: left; text-align:center; border: 1px solid #ddd; border-radius: 5px; width: 240px; min-height: 70px; padding:10px;">';
            //$html .= '<img src="http://store.easyp.eu/img/easypay_logo_nobrands-01.png" style="height:40px; margin-bottom: 10px;" title="Se quer pagar uma referência multibanco utilize a easypay" alt="Se quer pagar uma referência multibanco utilize a easypay">';
            if ($this->use_multibanco) {
                $html .= $this->get_mbbox_template($reference['ep_entity'], $reference['ep_reference'], $reference['ep_value']);
            }

            return $html . '</div>';
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

        //Templates

        /**
         * Returns the Easypay MB Box
         * @param integer $reference
         * @param integer $entity
         * @param double $value
         * @return string
         */
        private function get_mbbox_template($entity, $reference, $value)
        {
            $template = '<div style="width: 220px; float: left; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color:#eee;">
                            <!-- img src="http://store.easyp.eu/img/MB_bw-01.png" -->

                            <div style="padding: 5px; padding-top: 10px; clear: both;">
                                <span style="font-weight: bold;float: left;">%s:</span>
                                <span style="color: #0088cc; float: right">%s (Easypay)</span>
                            </div>

                            <div style="padding: 5px;clear: both;">
                                <span style="font-weight: bold;float: left;">%s:</span>
                                <span style="color: #0088cc; float: right">%s</span>
                            </div>

                            <div style="padding: 5px; clear: both;">
                                <span style="font-weight: bold;float: left;">%s:</span>
                                <span style="color: #0088cc; float: right">%s &euro;</span>
                            </div>


                        </div>
                        <div style="padding: 5px; clear: both;">
                          <a class="button wc-backward" href="' . esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))) . '">' . __('Return to shop', 'wceasypay') . ' </a>
                        </div>';


            return sprintf($template, __('Entity', 'wceasypay'), $entity, __('Reference', 'wceasypay'), wordwrap($reference, 3, ' ', true), __('Value', 'wceasypay'), $value);
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
    function woocommerce_add_gateway_easypay_mb($methods)
    {
        $methods[] = 'WC_Gateway_Easypay_MB';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_easypay_mb');

    /**
     * Checkout Fields Override
     *
     * @param array $fields
     * @return  array
     */
    function custom_override_checkout_fields_mb($fields)
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

    #add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');

    /**
     * Order Billing Details NIF Override
     *
     * @param array $billing_data
     * @return  array
     */
    function custom_override_order_billing_details_nif($billing_data)
    {
        $billing_data['fiscal_number'] = array('label' => __('Fiscal Number', 'wceasypay'), 'show' => true);
        return $billing_data;
    }

    #add_filter('woocommerce_admin_billing_fields', 'custom_override_order_billing_details_nif');

    /**
     * Order Shipping Details NIF Override
     *
     * @param array $shipping_data
     * @return  array
     */
    function custom_override_order_shipping_details_nif($shipping_data)
    {
        $shipping_data['fiscal_number'] = array('label' => __('Fiscal Number', 'wceasypay'), 'show' => true);
        return $shipping_data;
    }

    #add_filter('woocommerce_admin_shipping_fields', 'custom_override_order_shipping_details_nif');


} //END of function woocommerce_gateway_easypay_init

/**
 * WooCommerce Gateway Fallback Notice
 *
 * Request to user that Easypay Plugin needs the last vresion of WooCommerce
 */
function wceasypay_woocommerce_notice_mb()
{
    echo '<div class="error"><p>' . __('WooCommerce Easypay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wceasypay') . '</p></div>';
}
