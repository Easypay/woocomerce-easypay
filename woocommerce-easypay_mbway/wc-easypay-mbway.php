<?php

/**
 * Plugin Name: WooCommerce Easypay Gateway MBWay
 * Description: Easypay Payment Gateway for WooCommerce - Don't leave for tomorrow what you can receive today
 * Version: 1.00 - Beta
 * Author: Easypay
 * Author URI: https://easypay.pt
 *
 * Text Domain: wceasypay
 * Domain Path: /languages/
 *
 * @package Woocommerce-easypay-gateway-mbway
 * @category Gateway
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//Installation
require_once 'core/install.php';
register_activation_hook(__FILE__, 'wceasypay_activation_mbway');
# Deactivate
require_once 'core/uninstall.php';
register_deactivation_hook(__FILE__, 'wceasypay_deactivation_mbway');

//Plugin initialization
add_action('plugins_loaded', 'woocommerce_gateway_easypay_mbway_init', 0);
add_action('woocommerce_api_easypay', 'easypay_callback_handler');

/**
 * WC Gateway Class - Easypay MB
 */
function woocommerce_gateway_easypay_mbway_init() {

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wceasypay_woocommerce_notice_mbway');
        return;
    }

    /**
     * Localisation
     */
    load_plugin_textdomain('wceasypay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    class WC_Gateway_Easypay_MBWay extends WC_Payment_Gateway {

        /**
         * Gateway's Constructor.
         *
         * One of the Woocommerce required functions
         */

        public function __construct()
        {
            // Variable to acess other woocommerce variables and functions
            global $woocommerce, $data;
            # Auto capture var
            global $autoCapture;
            // Class Variables -------------------------------------------------
            // Error
            $this->error    = '<div class="error"><p>%s</p></div>';
            $this->ahref    = '<a href="' . get_admin_url() . 'admin.php?'.
                              'page=wc-settings&amp;'.
                              'tab=checkout&amp;'.
                              'section=wc_gateway_easypay_mbway">';
            $this->a        = '</a>';
            // Apis
            $this->apis     = array(
                'request_reference' => 'api_easypay_01BG.php',
                'request_payment_info' => 'api_easypay_03AG.php',
                'request_payment' => 'api_easypay_05AG.php',
                'payment_listings' => 'api_easypay_040BG1.php'
            );
            // Easypay URL
            $this->live_url = 'https://www.easypay.pt/_s/';
            $this->test_url = 'http://test.easypay.pt/_s/';
            // -----------------------------------------------------------------

            // Inherited Variables----------------------------------------------
            $this->id                   = 'easypay_mbway';
            $this->icon                 = plugins_url('images/logo.png', __FILE__);
            $this->has_fields           = false;
            $this->method_title         = __('Easypay MBWay', 'wceasypay');
            $this->method_description   = __('Don\'t leave for tomorrow what you can receive today', 'wceasypay');
            // -----------------------------------------------------------------

            // Load the form fields (is a function in this class)
            $this->init_form_fields();

            $this->init_settings(); // Woocommerce function

            // Define user set variables from form_fields function -------------
            // Main setings
            $this->enabled          = $this->get_option('enabled');
            $this->title            = $this->get_option('title');
            $this->description      = $this->get_option('description');
            $this->cin              = $this->get_option('cin');
            $this->user             = $this->get_option('user');
            $this->entity           = $this->get_option('entity');
            $this->code             = $this->get_option('code');
            $this->country          = $this->get_option('country');
            $this->language         = $this->get_option('language');
            $this->expiration       = $this->get_option('expiration');
            $this->mbway_title      = $this->get_option('mbway_title');
            $this->ref_type         = 'auto';
            // Payment Types
            $this->use_mbway        = true;
            $this->use_multibanco   = false;
            $this->use_credit_card  = false;
            $this->use_boleto       = false;
            // Gateway Testing
            $this->test             = $this->get_option('test') == 'yes';
            $this->logs             = $this->get_option('logs') == 'yes';
            // -----------------------------------------------------------------

            // Validations
            $this->enabled = $this->gateway_enabled();
            $this->gateway_validation();

            // Activate logs
            if ($this->logs) {
                $this->logger = new WC_Logger();
            }

            // Load the settings (see admin_options in this class)
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array($this, 'process_admin_options')
            );
            // Action for receipt page (see function in this class)
            /*
            add_action(
                'woocommerce_receipt_' . $this->id,
                array($this, 'receipt_page')
            );
            */
            // Action for receipt page (see function in this class)
            add_action(
                'woocommerce_receipt_' . $this->id,
                array($this, 'receipt_page')
            );
            // Send Email
            add_action(
                'woocommerce_email_after_order_table',
                array($this, 'reference_in_mail'),
                10,
                3
            );

        } //END of constructor

        /*
        * Begin of custom order operations
        */

        protected function payment_on_hold( $order, $reason = '' ) {
            $order->update_status( 'on-hold', $reason );
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
            if($order->get_payment_method() == 'easypay_mbway') {
                global $wpdb;
                if (!$sent_to_admin) {
                    // Log
                    $this->log('A new mail for client');
                    // Search entity, reference and value in database for this $order->get_id()
                    $row = $wpdb->get_row( $wpdb->prepare(
                        "
                        SELECT *
                        FROM ".$wpdb -> prefix."easypay_notifications
                        WHERE t_key = %d;
                        ",
                        $order->get_id()
                    ));
                    if ($row != null) {
                        // Do a log
                        $result  = 'Data correctly search from database:' . PHP_EOL;
                        $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                        $result .= 'Entity: ' . $row->ep_entity . ';' . PHP_EOL;
                        $result .= 'Value: ' . $row->ep_value . ';' . PHP_EOL;
                        $result .= 'Reference: ' . $row->ep_reference . ';' . PHP_EOL;
                        $this->log($result);
                        // Output the reference, entity and value in email
                        ?>
                        <br/>
                        <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" bordercolor="#eee">
                            <tr>
                                <td colspan="5">
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
         * Check if the settings are correts
         *
         * @return bool
         */
        private function gateway_enabled()
        {
            return (($this->get_option('enabled') == 'yes') &&
                !empty($this->cin) &&
                !empty($this->user) &&
                !empty($this->entity) &&
                !empty($this->country) &&
                !empty($this->language) &&
                ($this->use_multibanco || $this->use_credit_card || $this->use_boleto || $this->use_mbway) &&
                $this->is_valid_for_use()) ? 'yes' : 'no';
        }

        /**
         * Check if the settings are correct
         *
         * @return void
         */
        private function gateway_validation()
        {
            if (empty($this->cin)) {
                add_action('admin_notices', array(&$this, 'error_missing_cin'));
            }
            if (empty($this->user)) {
                add_action('admin_notices', array(&$this, 'error_missing_user'));
            }
            if (empty($this->entity)) {
                add_action('admin_notices', array(&$this, 'error_missing_entity'));
            }
            if (empty($this->country)) {
                add_action('admin_notices', array(&$this, 'error_missing_country'));
            }
            if (empty($this->language)) {
                add_action('admin_notices', array(&$this, 'error_missing_lang'));
            }
            if (!$this->is_valid_for_use()) {
                add_action('admin_notices', array(&$this, 'error_invalid_currency'));
            }
            // Validate expiration
            if ($this->expiration < 1 || $this->expiration > 93) {
                add_action('admin_notices', array(&$this, 'error_invalid_expiration'));
            }
            // Validate mbway_title
            if (empty($this->mbway_title)) {
                add_action('admin_notices', array(&$this, 'error_invalid_mbway_title'));
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
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wceasypay'),
                    'type' => 'checkbox',
                    'label' => __('Enable Easypay Payment Gateway.', 'wceasypay'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'wceasypay'),
                    'default' => __('Easypay MBWay', 'wceasypay'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'wceasypay'),
                    'type' => 'textarea',
                    'default' => __('Don\'t leave for tomorrow what you can receive today', 'wceasypay')
                ),
                'cin' => array(
                    'title' => __('CIN', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('The Client Identification Number of your easypay account.', 'wceasypay'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'user' => array(
                    'title' => __('User', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('The USER of your easypay account.', 'wceasypay'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'entity' => array(
                    'title' => __('Entity', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('The ENTITY of your easypay account. <br/>Please refer to our commercial department for more information.', 'wceasypay'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'code' => array(
                    'title' => __('Code', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('Security Token, use this only if you have validation by code instead of validation by IP.', 'wceasypay'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'country' => array(
                    'title' => __('Country', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('Must be always PT.', 'wceasypay'),
                    'default' => 'PT',
                    'desc_tip' => true,
                ),
                'language' => array(
                    'title' => __('Language', 'wceasypay'),
                    'type' => 'text',
                    'description' => __('The language that the user should see on credit card gateway.', 'wceasypay'),
                    'default' => 'PT',
                    'desc_tip' => true,
                ),
                'expiration' => array(
                     'title' => __('Expiration Date (11683 Entity) in Days', 'wceasypay'),
                     'type' => 'decimal',
                     'description' => __('Only 1 to 93 days accepted', 'wceasypay'),
                     'default' => '1',
                     'desc_tip' => true,
                ),
                'mbway_title' => array(
                     'title' => __('Mbway Title for Mbway Transactions', 'wceasypay'),
                     'type' => 'text',
                     'description' => __('A Title Is Required', 'wceasypay'),
                     'default' => 'MBWAY SHOP',
                     'desc_tip' => true,
                ),
                'testing' => array(
                    'title' => __('Gateway Testing', 'wceasypay'),
                    'type' => 'title',
                    'description' => '',
                ),
                'test' => array(
                    'title' => __('Easypay sandbox', 'wceasypay'),
                    'type' => 'checkbox',
                    'label' => __('Enable Easypay sandbox', 'wceasypay'),
                    'default' => 'yes',
                    'description' => __('Easypay sandbox can be used to test payments.', 'wceasypay'),
                    'desc_tip' => true,
                ),
                'logs' => array(
                    'title' => __('Debug', 'wceasypay'),
                    'type' => 'checkbox',
                    'label' => __('Enable logging', 'wceasypay'),
                    'default' => 'no',
                    'description' => __('Log Easypay events such as API requests, the logs will be placed in <code>woocommerce/logs/easypay.txt</code>', 'wceasypay'),
                    'desc_tip' => true,
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
            echo '<td><h3>'.__('Easypay Configurations', 'wceasypay').'</h3></td>';
            echo '<td><p>Configurations that you must perform on your Easypay account.<br/><strong>'.__('Go to "Webservices" > "URL Configuration"', 'wceasypay').'</strong></p></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td><h4>'.__('MBWay FWD URL', 'wceasypay').'</h4></td>';
            echo '<td><input type="text" size="100" readonly value="'.$public_url . 'mbway-fwd.php'.'"/></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td><h4>'.__('Notification URL', 'wceasypay').'</h4></td>';
            echo '<td><input type="text" size="100" readonly value="'.$public_url . 'notification.php'.'"/></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td><h3>'.__('Easypay Configurations', 'wceasypay').' '.__('On your server', 'wceasypay').'</h3></td>';
            echo '<td>'.__('For Credit Card payment check you must create a cron job, we sugest you config your cron job to call this file once a day.', 'wceasypay').'</td>';
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
         * @param   integer $order_id
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
                'o_email'       => $order->get_billing_email(),
                'ep_partner'    => ''
            );

            if($this->entity == "11683") {
              if($this->expiration >= 1 || $this->expiration <= 93){
                  $args['ep_partner'] = (string)$this->user;
                  $max_date=Date('Y-m-d', strtotime("+" . $this->expiration . " days"));
                  $args['o_max_date'] = $max_date;
              }
            }

            $this->log('Arguments for order #' . $order->get_id() . ': ' . print_r($args, true));

            $url = $this->get_request_url($this->apis['request_reference'], $args);

            $this->log('Request URL #' . $order->get_id() . ': ' . $url);
            $contents = $this->get_contents($url);

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

            // Create a new row in database:
            //      Table wp_easypay_notifications
            // The row without ep_doc, ep_status with 'pending' and t_key with
            // a number (order_id) was generated by the next code.
            // This row is used for show this data in email.
            // This below function returns false or the number of affected rows.
            if (!$wpdb->insert(
                $wpdb->prefix . 'easypay_notifications',
                array(
                    'ep_entity'     => $data['ep_entity'],
                    'ep_value'      => $data['ep_value'],
                    'ep_reference'  => $data['ep_reference'],
                    't_key'         => $order->get_id(),
                )
            )) {
                $result = 'Error while inserting the new generated reference in database:' . PHP_EOL;
                $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                $result .= 'Entity: ' . $data['ep_entity'] . ';' . PHP_EOL;
                $result .= 'Value: ' . $data['ep_value'] . ';' . PHP_EOL;
                $result .= 'Reference: ' . $data['ep_reference'] . ';' . PHP_EOL;
                $this->log($result);
            } else {
                $result = 'New data inserted in database:' . PHP_EOL;
                $result .= 'Order ID: ' . $order->get_id() . ';' . PHP_EOL;
                $result .= 'Entity: ' . $data['ep_entity'] . ';' . PHP_EOL;
                $result .= 'Value: ' . $data['ep_value'] . ';' . PHP_EOL;
                $result .= 'Reference: ' . $data['ep_reference'] . ';' . PHP_EOL;
                $this->log($result);
            }
            // Validate phone form field before this step in the onverride section
            if(!empty($order->get_billing_phone())) {
              $raw_phone = trim($order->get_billing_phone(), '+');
              $country_code = substr_replace($raw_phone, '', 2, -1);
              $phone_number = substr_replace($raw_phone, '', 0, 3);
            } else {
              // Error?
            }

            $args_mbway = array(
              'e' => $data['ep_entity'],
              'r' => $data['ep_reference'],
              'v' => $data['ep_value'],
              'mbway' => 'yes',
              'mbway_title' => $this->mbway_title,
              'mbway_type' => 'purchase',
              'mbway_phone_indicative' => $country_code,
              'mbway_phone' => $phone_number,
              'mbway_currency' => 'EUR',
              't_key' => $order->get_id()
            );

            // Call the 05AG API to create an mbway authorization
            // http://test.easypay.pt/_s/api_easypay_05AG.php?e=10611&r=679300400&v=1.20&mbway=yes&mbway_title=TestesEPWP&mbway_type=authorization&mbway_phone_indicative=351&mbway_phone=911234567&mbway_currency=EUR&t_key=1&s_code=d0846a2cbda2819540920acc1b61c603

            $this->log('Arguments for MBWay Auth order #' . $order->get_id() . ': ' . print_r($args_mbway, true));

            $mbway_url = $this->get_request_url($this->apis['request_payment'], $args_mbway);

            $this->log('Request MBWay URL #' . $order->get_id() . ': ' . $mbway_url);
            $mbway_contents = $this->get_contents($mbway_url);

            $mbway_obj = simplexml_load_string($mbway_contents);
            $mbway_data = json_decode(json_encode($mbway_obj), true);

            if (!$mbway_data) {
                $this->log('Error while requesting MBWay Auth. 1 #' . $order->get_id() . ' [' . $mbway_contents . ']');
                return $this->error_btn_order($order, 'Not enough data.');
            }
            if ($mbway_data['ep_status'] != 'accepted') {
                $this->log('Error while requesting MBWay Auth. 2 #' . $order->get_id() . ' [' . $mbway_data['ep_message'] . ']');
                return $this->error_btn_order($order, $mbway_data['ep_message']);
            } else {
                $this->log('MBWay Auth. created #' . $order->get_id() . ' @' . $mbway_data['r'] . ']');
                $note = __('Awaiting for reference payment.', 'wceasypay') . PHP_EOL;
                $note .= 'Entity: ' . $mbway_data['e'] . '; ' . PHP_EOL;
                $note .= 'Value: ' .  $mbway_data['v'] . '; ' . PHP_EOL;
                $note .= 'Reference: ' . $mbway_data['r'] . '; ' . PHP_EOL;
                $order->add_order_note($note, 0);
            }

            // It's necessary these changes for send a email with an order in processing
            #$order->update_status('on-hold'); // pending->on-hold
            #$order->update_status('pending'); // on-hold->pending
            $this->payment_on_hold( $order, $reason = '' ); // reduces stock


            // Send Email
            // Podiamos passar o email para o mbway-fwd, já que só nesse momento vai fazer sentido, talvez...
            add_action(
                'mail_the_guy',
                array($this, 'reference_in_mail'),
                10,
                2
            );
            do_action('mail_the_guy', $order, $data );

            // Podiamos colocar outra página tipo: Vai receber um email assim que aprovar a transacção?! Ou optar por uma cena ninja qualquer TIPO um ciclo JS no browser
            // Para ver se o pagamento já tinha sido recebido no mbway-fwd!!! E Caso o cliente escolhesse clicar num botão para cancelar e regressar,
            // Caso passasse muito tempo
            // Cancelava a encomenda e já não reduzia o stock
            /*
              Por um file à escuta sem ser o fwd que recebe a not. da easypay. mas que vai ver o estado do id da encomenda que eu lhe der como paramentro e vai responder o estado da encomenda ao AJAX em cada ciclo
            */


            return $this->get_reference_html($data);
        }
        /**
         * Builds a resquest url
         *
         * @param   string $api API Name
         * @param   array $args List of Arguments
         * @return  string
         */
        public function get_request_url($api, $args)
        {
            return ($this->test ? $this->test_url : $this->live_url) .
                $api . '?' .
                http_build_query($args) .
                ( $this->code != '' ? '&s_code=' . $this->code : '');
        }

        /**
         * Returns a string from a link via cUrl
         *
         * @param string $url
         * @return string
         */
        public function get_contents($url)
        {
            if (function_exists('curl_init')) {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
                $result = curl_exec($curl);
                curl_close($curl);

                return $result;

            } else {
                return file_get_contents($url);
            }
        }

        /**
         * Order error button.
         *
         * @param   object  $order      Order data
         * @param   string  $message    Error message
         * @return  string
         */
        private function error_btn_order($order, $message = 'Internal Error')
        {
            // Display message if there is problem.
            $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wceasypay') . '</p>';
            if ($this->logs) {
                $html .= '<p><strong>Message</strong>: ' . $message . '</p>';
            }
            $html .='<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'wceasypay') . '</a>';

            return $html;
        }

        /**
         * Returns the Checkout HTML code
         *
         * @param   array $reference
         * @param   string $html
         * @return  string
         */

        private function get_reference_html($reference, $html = '')
        {
            $html .= '<div style="float: left; text-align:center; border: 1px solid #ddd; border-radius: 5px; width: 240px; min-height: 70px; padding:10px;">';
            //$html .= '<img src="http://store.easyp.eu/img/easypay_logo_nobrands-01.png" style="height:40px; margin-bottom: 10px;" title="Se quer pagar uma referência multibanco utilize a easypay" alt="Se quer pagar uma referência multibanco utilize a easypay">';
            if ($this->use_mbway) {
                $html .= $this->get_mbbox_template($reference['ep_entity'], $reference['ep_reference'], $reference['ep_value']);
            }

            return $html . '</div>';
        }

        /**
         * Process the payment and return the result.
         *
         * One of the Woocommerce required functions
         *
         * @param   integer $order_id
         * @return  array
         */
        public function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);

            $woocommerce->cart->empty_cart();

            return array(
                'result' => 'success',
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
         * @param integer   $reference
         * @param integer   $entity
         * @param double    $value
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
                          <a class="button wc-backward" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">' . __( 'Return to shop', 'wceasypay' ) . ' </a>
                        </div>';


            return sprintf($template, __('Entity', 'wceasypay'), $entity, __('Reference', 'wceasypay'), wordwrap($reference, 3, ' ', true), __('Value', 'wceasypay'), $value);
        }

        /**
         * Returns the Easypay Visa Box
         * @param string $visa_url
         * @return string
         */
        private function get_visa_template($visa_url)
        {
            $template = '<div style="margin-top: 10px; width: 220px; float: left; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color:#eee;">
                            <img src="http://store.easyp.eu/img/visa_mc_bw-01.png">

                            <div style="padding: 5px; padding-top: 10px; clear: both;">
                                <a href="%s" style="color: #0088cc; text-decoration: none;">%s!</a>
                            </div>

                        </div>';

            return sprintf($template, esc_url($visa_url), __('Pay Now', 'wceasypay'));
        }

        /**
         * Returns the Easypay Boleto Box
         * @param string $boleto_url
         * @return string
         */
        private function get_boleto_template($boleto_url)
        {
            $template = '<div style="margin-top: 10px; width: 220px; float: left; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color:#eee;">
                            <img src="http://store.easyp.eu/img/bb_bw-01.png">

                            <div style="padding: 5px; padding-top: 10px; clear: both;">
                                <a href="%s" style="color: #0088cc; text-decoration: none;">%s!</a>
                            </div>

                        </div>';

            return sprintf($template, esc_url($boleto_url), __('Pay Now', 'wceasypay'));
        }

        // Errors
        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_cin()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing CIN. %sClick here to configure.%s',
                'wceasypay'
            );

            $msgFinal = sprintf($msg, $this->ahref, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_user()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing USER. %sClick here to configure.%s',
                'wceasypay'
            );

            $msgFinal = sprintf($msg, $this->ahref, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_entity()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing ENTITY. %sClick here to configure.%s',
                'wceasypay'
            );

            $msgFinal = sprintf($msg, $this->ahref, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_country()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing COUNTRY. %sClick here to configure.%s',
                'wceasypay'
            );

            $msgFinal = sprintf($msg, $this->ahref, $this->a);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_missing_lang()
        {
            $msg = __(
                '<strong>Easypay Gateway Disabled</strong> Missing LANGUAGE. %sClick here to configure.%s',
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
         * Displays an error message on the top of admin panel
         */
        public function error_invalid_expiration()
        {
            $msg = __(
                      '<strong>Warning:</strong> Please choose an expiration number of days between 1 and 93',
                      'wceasypay'
                      );

            $msgFinal = sprintf($msg);

            echo sprintf($this->error, $msgFinal);
        }

        /**
         * Displays an error message on the top of admin panel
         */
        public function error_invalid_mbway_title()
        {
            $msg = __(
                      '<strong>Warning:</strong> Please insert a valid mbway title',
                      'wceasypay'
                      );

            $msgFinal = sprintf($msg);

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
     * @param   array $methods
     * @return  array
     */
    function woocommerce_add_gateway_easypay_mbway($methods)
    {
        $methods[] = 'WC_Gateway_Easypay_MBWay';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_easypay_mbway');

    /**
     * Checkout Fields Override
     *
     * @param   array $fields
     * @return  array
     */
     // Ensure required billing phone

     add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

     function custom_override_checkout_fields( $fields ) {

          $fields['billing']['billing_phone'] = array(
            'label'     => __('Phone', 'woocommerce'),
            'placeholder'   => _x('+351000111000', 'placeholder', 'woocommerce'),
            'required'  => true,
            'class'     => array('billing-phone, form-row-wide'),
            'clear'     => true
          );

          return $fields;
     }
     // Validate phone field with our own rules
     add_action('woocommerce_checkout_process', 'wh_phoneValidateCheckoutFields');

    function wh_phoneValidateCheckoutFields() {
        $billing_phone = filter_input(INPUT_POST, 'billing_phone');
        // Lets compare with a regex expression
        if (strlen(trim(preg_replace('/^[+]\d{12}$/', '', $billing_phone))) > 0) {
            wc_add_notice(__('Invalid <strong>Phone Number</strong>, please check your input.'), 'error');
        }
    }

     /**
     * Display phone field value on the order edit page
     */

    add_action( 'woocommerce_admin_order_data_after_shipping_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

    function my_custom_checkout_field_display_admin_order_meta($order){
        echo '<p><strong>'.__('MBWay Transaction Phone Number').':</strong> ' . get_post_meta( $order->get_id(), '_billing_phone', true ) . '</p>';
    }

} //END of function woocommerce_gateway_easypay_init

/**
 * WooCommerce Gateway Fallback Notice
 *
 * Request to user that Easypay Plugin needs the last vresion of WooCommerce
 */
function wceasypay_woocommerce_notice_mbway() {
    echo '<div class="error"><p>' . __('WooCommerce Easypay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wceasypay') . '</p></div>';
}
