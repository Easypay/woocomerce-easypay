<?php

class WC_Gateway_Easypay_CC extends WC_Payment_Gateway
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
            'section=wc_payment_gateway_easypay_cc_2">';
        $this->a = '</a>';

        // 2.0 API EndPoint
        $this->live_url = 'https://api.prod.easypay.pt/2.0/single';
        $this->test_url = 'https://api.test.easypay.pt/2.0/single';
        // -----------------------------------------------------------------

        // Inherited Variables----------------------------------------------
        $this->id = 'easypay_cc_2';
        $this->icon = plugins_url('../images/logo.png', __FILE__);
        $this->has_fields = false;
        $this->method_title = __('Easypay CC', 'wceasypay');
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
        $this->autoCapture = $this->get_option('capture');
        $this->method = "cc";
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
                'default'     => __('Easypay CC', 'wceasypay'),
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
            'capture'           => [
                'title'       => __('Auto Capture', 'wceasypay'),
                'type'        => 'checkbox',
                'description' => __('Auto request the capture of the authorized transactions .', 'wceasypay'),
                'default'     => false,
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
                    <input value="<?= $public_url ?>generic.php" type="text" size="100" readonly>
                </td>
            </tr>
            <tr>
                <td>
                    <h4><?= __('Visa - Fwd URL', 'wceasypay') ?></h4>
                </td>
                <td>
                    <input value="<?= $public_url ?>visa-fwd-2.php" type="text" size="100" readonly>
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

        // start to build the body with the ref data
        $body = [
            'type'     => 'authorisation',
            'key'      => (string)$order->get_id(),
            'method'   => $this->method,
            'value'    => floatval($order->get_total()),
            'currency' => $this->currency,
            'customer' => [
                'name'             => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email'            => $order->get_billing_email(),
                'key'              => (string)$order->get_id(),
                'phone_indicative' => '+351',
                'phone'            => $order->get_billing_phone(),
                // 'fiscal_number' => 'PT123456789',
            ],
        ]; // Commented the fiscal number since the special nif field is commented also

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
            $this->log('Payment created #' . $order->get_id() . ' @' . $data['id'] . ']');
            $note = __('Awaiting for Credit Card payment.', 'wceasypay') . PHP_EOL;
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

        // Add a method to the class to credit card
        $visa_url = $data['method']['url'];
        return $request->get_visa_template($visa_url);
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
        $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.',
                'wceasypay') . '</p>';
        if ($this->logs) {
            $html .= '<p><strong>Message</strong>: ' . $message . '</p>';
        }
        $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again',
                'wceasypay') . '</a>';

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
    public function cc_error_missing_api_key()
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
    public function cc_error_missing_account_id()
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
    public function cc_error_invalid_currency()
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

    public function getVoidUrl()
    {
        $url = $this->test ? $this->test_url : $this->live_url;

        return str_replace('/single', '/void', $url);
    }

    public function getCaptureUrl()
    {
        $url = $this->test ? $this->test_url : $this->live_url;

        return str_replace('/single', '/capture', $url);
    }
}