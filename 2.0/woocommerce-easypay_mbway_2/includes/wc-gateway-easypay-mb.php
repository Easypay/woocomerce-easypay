<?php

class WC_Gateway_Easypay_MB extends WC_Payment_Gateway
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
            'section=wc_payment_gateway_easypay_mb_2">';
        $this->a = '</a>';

        // 2.0 API EndPoint
        $this->live_url = 'https://api.prod.easypay.pt/2.0/single';
        $this->test_url = 'https://api.test.easypay.pt/2.0/single';
        // -----------------------------------------------------------------

        // Inherited Variables----------------------------------------------
        $this->id = 'easypay_mb_2';
        $this->icon = plugins_url('../images/logo.png', __FILE__);
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
        $this->expiration_time = (int)$this->get_option('expiration');
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
        $this->enabled = $this->gateway_enabled_2();
        //
        // validate admin options
        add_action("woocommerce_update_options_payment_gateways_{$this->id}"
            , [$this, 'process_admin_options']);

        // -----------------------------------------------------------------

        // Activate logs
        if ($this->logs) {
            $this->logger = new WC_Logger();
        }
        // -----------------------------------------------------------------

        // Load the settings (see admin_options in this class)
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );
        // -----------------------------------------------------------------

        // Action for receipt page (see function in this class)
        add_action(
            'woocommerce_receipt_' . $this->id,
            [$this, 'receipt_page']
        );
        // -----------------------------------------------------------------

        // Send Email
        add_action(
            'woocommerce_email_after_order_table',
            [$this, 'reference_in_mail_2'],
            20,
            5
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
    public function easypay_api_auth()
    {
        $api_auth['url'] = $this->test ? $this->test_url : $this->live_url;
        $api_auth['account_id'] = $this->account_id;
        $api_auth['api_key'] = $this->api_key;

        return $api_auth;
    }

    /**
     * Put the reference, entity and value in the email.
     *
     * @param   $order
     * @param   $sent_to_admin
     */
    public function reference_in_mail_2($order, $sent_to_admin, $plain_text, $email)
    {
        if ($order->get_payment_method() == 'easypay_mb_2') {
            global $wpdb;
            if (!$sent_to_admin) {
                // Log
                $this->log('A new mail for client');
                // Search entity, reference and value in database for this $order->get_id()
                $query_str = "SELECT *"
                    . " FROM {$wpdb->prefix}easypay_notifications_2"
                    . " WHERE t_key = %d";
                $row = $wpdb->get_row($wpdb->prepare($query_str, $order->get_id()));

                if ($row != null) {
                    // Do a log
                    $result = 'Data correctly search from database:' . PHP_EOL;
                    $result .= "Order ID: {$order->get_id()};" . PHP_EOL;
                    $result .= "Entity: {$row->ep_entity};" . PHP_EOL;
                    $result .= "Value: {$row->ep_value} ;" . PHP_EOL;
                    $result .= "Reference: {$row->ep_reference} ;" . PHP_EOL;
                    $this->log($result);
                    // Output the reference, entity and value in email

                    ob_start();
                    ?>
                    <div style="width: 220px; float: left; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color:#eee;">
                        <!-- img src="http://store.easyp.eu/img/MB_bw-01.png" -->

                        <div style="padding: 5px; padding-top: 10px; clear: both;">
                            <span style="font-weight: bold;float: left;"><?= __('Entity', 'wceasypay') ?>:</span>
                            <span style="color: #0088cc; float: right"><?= $row->ep_entity ?> (Easypay)</span>
                        </div>

                        <div style="padding: 5px;clear: both;">
                            <span style="font-weight: bold;float: left;"><?= __('Reference', 'wceasypay') ?>:</span>
                            <span style="color: #0088cc; float: right"><?= wordwrap($row->ep_reference, 3, ' ',
                                    true) ?></span>
                        </div>

                        <div style="padding: 5px; clear: both;">
                            <span style="font-weight: bold;float: left;"><?= __('Value', 'wceasypay') ?>:</span>
                            <span style="color: #0088cc; float: right"><?= $row->ep_value ?> &euro;</span>
                        </div>
                    </div>
                    <br>
                    <?php

                    $template = ob_get_clean();
                    echo $template;
                } else {

                    $result = 'Error while search data in database:' . PHP_EOL;
                    $result .= "Order ID: {$order->get_id()};" . PHP_EOL;
                    $this->log($result);
                    die("Error while search data in database...");
                }
            } else {
                // Log
                $this->log('A new mail for administrator');
            }
        }
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
    public function process_admin_options()
    {
        if (empty($_POST["woocommerce_{$this->id}_account_id"])) {
            WC_Admin_Settings::add_error('Error: Please fill required field: Easypay Account ID');
            return false;
        }
        if (empty($_POST["woocommerce_{$this->id}_api_key"])) {
            WC_Admin_Settings::add_error('Error: Please fill required field: Easypay API key');
            return false;
        }
        if (isset($_POST["woocommerce_{$this->id}_expiration_enable"])) {
            if (!is_numeric($_POST["woocommerce_{$this->id}_expiration"])
                || (int)$_POST["woocommerce_{$this->id}_expiration"] < 1
                || (int)$_POST["woocommerce_{$this->id}_expiration"] > 93) {
                WC_Admin_Settings::add_error('Error: Invalid value in field: Expiration in Days');
            }
        }
        parent::process_admin_options();

        return true;
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
        $this->form_fields = [
            'enabled'           => [
                'title'   => __('Enable/Disable', 'wceasypay'),
                'type'    => 'checkbox',
                'label'   => __('Enable Easypay Payment Gateway.', 'wceasypay'),
                'default' => 'no'
            ],
            'title'             => [
                'title'       => __('Title', 'wceasypay'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wceasypay'),
                'default'     => __('Easypay MB', 'wceasypay'),
                'desc_tip'    => true,
            ],
            'description'       => [
                'title'   => __('Customer Message', 'wceasypay'),
                'type'    => 'textarea',
                'default' => __('Don\'t leave for tomorrow what you can receive today', 'wceasypay')
            ],
            'account_id'        => [
                'title'       => __('Account ID', 'wceasypay'),
                'type'        => 'text',
                'description' => __('The Account ID You Generated at Easypay Backoffice', 'wceasypay'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'api_key'           => [
                'title'       => __('API Key', 'wceasypay'),
                'type'        => 'text',
                'description' => __('The API Key You Generated at Easypay Backoffice', 'wceasypay'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'expiration'        => [
                'title'       => __('Expiration in Days', 'wceasypay'),
                'type'        => 'decimal',
                'description' => __('Only 1 to 93 days accepted', 'wceasypay'),
                'default'     => '1',
                'desc_tip'    => true,
            ],
            'expiration_enable' => [
                'title'       => __('Enable Expiration for MB References', 'wceasypay'),
                'type'        => 'checkbox',
                'description' => __('Enable This Option to Activate Reference Expiration Time', 'wceasypay'),
                'default'     => 'no',
                'desc_tip'    => true,
            ],
            'testing'           => [
                'title'       => __('Gateway Testing', 'wceasypay'),
                'type'        => 'title',
                'description' => '',
            ],
            'test'              => [
                'title'       => __('Easypay sandbox', 'wceasypay'),
                'type'        => 'checkbox',
                'label'       => __('Enable Easypay sandbox', 'wceasypay'),
                'default'     => 'yes',
                'description' => __('Easypay sandbox can be used to test payments.', 'wceasypay'),
                'desc_tip'    => true,
            ],
            'logs'              => [
                'title'       => __('Debug', 'wceasypay'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'wceasypay'),
                'default'     => 'no',
                'description' => __('Log Easypay events such as API requests, the logs will be placed in <code>woocommerce/logs/easypay.txt</code>',
                    'wceasypay'),
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Admin Panel Options
     *
     * @return void
     */
    public function admin_options()
    {
        // Public_Url is for "Easypay Configurations" urls
        $public_url = get_site_url()
            . '/wp-content/plugins'
            . "/woocommerce-{$this->id}"
            . '/public/';

        ob_start();
        ?>
        <h3><?= __('Easypay standard', 'wceasypay') ?></h3>
        <p><?= __('Easypay standard works by sending the user to Easypay to enter their payment information.',
                '') ?></p>
        <table class="form-table">
            <?= $this->generate_settings_html() ?>
            <tr>
                <td>
                    <h3><?= __('Easypay Configurations', 'wceasypay') ?></h3>
                </td>
                <td>
                    <p>Configurations that you must perform on your Easypay account.
                        <br>
                        <strong><?= __('Go to "Webservices" > "URL Configuration"', 'wceasypay') ?></strong>
                    </p>
                </td>
            </tr>
            <tr>
                <td>
                    <h4><?= __('Notification URL', 'wceasypay') ?></h4>
                </td>
                <td>
                    <input type="text" size="100" readonly value="<?= $public_url ?>generic.php">
                </td>
            </tr>
        </table>
        <?php

        echo ob_get_clean();
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

        if ($this->expiration_enable == 'yes') {
            if ($this->expiration_time >= 1 || $this->expiration_time <= 93) {
                $max_date = Date('Y-m-d h:m', strtotime("+" . $this->expiration_time . " days"));
            }
        }

        // start to build the body with the ref data
        $body = [
            'key'      => (string)$order->get_id(),
            'method'   => $this->method,
            'value'    => floatval($order->get_total()),
            'currency' => $this->currency,
            'customer' => [
                'name'             => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email'            => $order->get_billing_email(),
                'key'              => (string)$order->get_id(),
                'phone_indicative' => "+351",
                'phone'            => $order->get_billing_phone(),
                // 'fiscal_number' =>"PT123456789",
            ],
        ]; // Commented the fiscal number since the special nif field is commented also

        if (isset($max_date)) {
            $body['expiration_time'] = $max_date;
        }

        $this->log('Payload for order #' . $order->get_id() . ': ' . print_r(json_encode($body), true));

        $url = $this->test ? $this->test_url : $this->live_url;

        $auth = [
            'url'        => $url,
            'account_id' => $this->account_id,
            'api_key'    => $this->api_key,
            'method'     => 'POST',
        ];

        if (!class_exists('WC_Easypay_Request')) {
            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . 'wc-easypay-request.php';
        }

        $request = new WC_Easypay_Request($auth);

        $data = $request->get_contents($body);

        if ($data['status'] != 'ok') {
            $this->log('Error while requesting payment for Order #' . $order->get_id() . ' [' . $data['message'][0] . ']');
            return $this->error_btn_order($order, $data['message'][0]);

        } else {
            $this->log('Reference created #' . $order->get_id() . ' @' . $data['method']['reference'] . ']');
            $note = __('Awaiting for reference payment.', 'wceasypay') . PHP_EOL;
            $note .= "Entity: {$data['method']['entity']}; " . PHP_EOL;
            $note .= "Value: {$order->get_total()}; " . PHP_EOL;
            $note .= "Reference: {$data['method']['reference']}; " . PHP_EOL;

            $order->add_order_note($note, 0);
        }

        $result = [
            '',
            "Order ID: {$order->get_id()};",
            "Entity: {$data['method']['entity']};",
            "Value: {$order->get_total()};",
            "Reference: {$data['method']['reference']};"
        ];
        if (!$wpdb->insert(
            $wpdb->prefix . 'easypay_notifications_2',
            [
                'ep_entity'     => $data['method']['entity'],
                'ep_value'      => $order->get_total(),
                'ep_reference'  => $data['method']['reference'],
                't_key'         => $order->get_id(),
                'ep_method'     => $this->method,
                'ep_payment_id' => $data['id'],
            ]
        )) {
            $result[0] = 'Error while inserting the new payment in database:';
        } else {
            $result[0] = 'New payment inserted in database:';
        }
        $this->log(implode(PHP_EOL, $result));

        // reduces stock
        $this->payment_on_hold($order, $reason = '');

        do_action('woocommerce_email_after_order_table', $order, false, false, false);

        $value = $order->get_total();

        return $request->get_mbbox_template($data['method']['entity']
            , $data['method']['reference']
            , $value);
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
        $html = "<p>{__('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wceasypay')}</p>";
        if ($this->logs) {
            $html .= "<p><strong>Message</strong>: $message</p>";
        }
        $html .= "<a class='button cancel' href='{ esc_url($order->get_cancel_order_url()) ?>'>{ __('Click to try again', 'wceasypay') }</a>";

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

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        ];
    }

    /**
     * Checking if this gateway is enabled and available in the user's country.
     *
     * @return bool
     */
    private function is_valid_for_use()
    {
        return in_array(get_woocommerce_currency(), ['EUR']);
    }

    // Errors

    /**
     * Displays an error message on the top of admin panel
     */
    public function mb_error_missing_api_key()
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
    public function mb_error_missing_account_id()
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
    public function mb_error_invalid_currency()
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
        if ($this->logs) {
            $this->logger->add('easypay', $message);
        }
    }
}