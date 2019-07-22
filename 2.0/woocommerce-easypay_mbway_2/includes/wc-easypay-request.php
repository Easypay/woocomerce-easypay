<?php

/**
 * Class WC_Easypay_Request file.
 *
 * @package WooCommerce\Gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to Easypay.
 */
class WC_Easypay_Request
{
    /**
     *
     * @param array $auth
     * @return void
     */
    public function __construct($auth)
    {
        $this->url = $auth['url'];
        $this->account_id = $auth['account_id'];
        $this->api_key = $auth['api_key'];
        $this->method = $auth['method'];
    }

    /**
     * Returns the api results
     *
     * @param array $payload
     * @return string
     */
    public function get_contents($payload)
    {
        if (!function_exists('curl_init')) {
            /*
             * @todo    throw custom exception or something else
             */
            wp_die();
        }

        $url = $this->url;

        switch ($this->method) {
            case 'POST':
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
                break;

            case 'GET':
                $url .= "/$payload";
                $headers = [
                    "AccountId: {$this->account_id}",
                    "ApiKey: {$this->api_key}",
                    'Content-Type: application/json',
                ];

                $curlOpts = [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_HTTPHEADER => $headers,
                ];
                break;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curlOpts);
        $response_body = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response_body, true);

        return $response;
    }

    /**
     * Returns the Checkout HTML code
     *
     * @param array $reference
     * @param string $value
     * @return  string
     */
    public function get_reference_html($reference, $value)
    {
        $html = '<div style="float: left; text-align:center; border: 1px solid #ddd; border-radius: 5px; width: 240px; min-height: 70px; padding:10px;">';
        //$html .= '<img src="http://store.easyp.eu/img/easypay_logo_nobrands-01.png" style="height:40px; margin-bottom: 10px;" title="Se quer pagar uma referência multibanco utilize a easypay" alt="Se quer pagar uma referência multibanco utilize a easypay">';
        $html .= $this->get_mbbox_template($reference['method']['entity'], $reference['method']['reference'], $value);
        return $html . '</div>';
    }

    /**
     * Returns the Easypay MB Box
     * @param integer $reference
     * @param integer $entity
     * @param double $value
     * @return string
     */
    public function get_mbbox_template($entity, $reference, $value)
    {
        $href = esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop')));

        $lng_entity = __('Entity', 'wceasypay');
        $lng_ref = __('Reference', 'wceasypay');
        $lng_value = __('Value', 'wceasypay');
        $ep_ref = wordwrap($reference, 3, ' ', true);

        ob_start();
        ?>
        <div
            style="width: 220px; float: left; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color:#eee;">
            <!-- img src="http://store.easyp.eu/img/MB_bw-01.png" -->

            <div style="padding: 5px; padding-top: 10px; clear: both;">
                <span style="font-weight: bold;float: left;"><?= $lng_entity ?>:</span>
                <span style="color: #0088cc; float: right"><?= $entity ?> (Easypay)</span>
            </div>

            <div style="padding: 5px;clear: both;">
                <span style="font-weight: bold;float: left;"><?= $lng_ref ?>:</span>
                <span style="color: #0088cc; float: right"><?= $ep_ref ?></span>
            </div>

            <div style="padding: 5px; clear: both;">
                <span style="font-weight: bold;float: left;"><?= $lng_value ?>:</span>
                <span style="color: #0088cc; float: right"><?= $value ?> &euro;</span>
            </div>

        </div>
        <div style="padding: 5px; clear: both;">
            <a class="button wc-backward" href="<?= $href ?>"><?= __('Return to shop', 'wceasypay') ?></a>
        </div>';
        <?php
        $template = ob_get_clean();

        return sprintf($template, __('Entity', 'wceasypay'), $entity, __('Reference', 'wceasypay'), wordwrap($reference, 3, ' ', true), __('Value', 'wceasypay'), $value);
    }

    public function get_mbway_template($order_key)
    {
        $js_mbway = plugin_dir_url(__FILE__)
            . '..' . DIRECTORY_SEPARATOR
            . 'public' . DIRECTORY_SEPARATOR
            . 'ep-mbway.js';
        $deps = ['jquery'];
        $script_handle = 'mbway_check_for_payment_notification';
        wp_enqueue_script($script_handle
            , $js_mbway
            , $deps
            , 0
            , true);
        $ajax_nonce = wp_create_nonce('wp-ep-mbway2-plugin');
        wp_localize_script($script_handle, 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'order_key' => $order_key,
            'nonce' => $ajax_nonce,
        ]);

        $html = '
            <div style="padding: 5px; padding-top: 10px; clear: both; id="mbway_idle">
            </div>
            
            <style type="text/css">
              .lds-grid {
              display: inline-block;
              position: relative;
              width: 80px;
              height: 80px;
            }
            .lds-grid div {
              position: absolute;
              width: 16px;
              height: 16px;
              border-radius: 50%;
              background: #034f84;
              animation: lds-grid 1.2s linear infinite;
            }
            .lds-grid div:nth-child(1) {
              top: 8px;
              left: 8px;
              animation-delay: 0s;
            }
            .lds-grid div:nth-child(2) {
              top: 8px;
              left: 32px;
              animation-delay: -0.4s;
            }
            .lds-grid div:nth-child(3) {
              top: 8px;
              left: 56px;
              animation-delay: -0.8s;
            }
            .lds-grid div:nth-child(4) {
              top: 32px;
              left: 8px;
              animation-delay: -0.4s;
            }
            .lds-grid div:nth-child(5) {
              top: 32px;
              left: 32px;
              animation-delay: -0.8s;
            }
            .lds-grid div:nth-child(6) {
              top: 32px;
              left: 56px;
              animation-delay: -1.2s;
            }
            .lds-grid div:nth-child(7) {
              top: 56px;
              left: 8px;
              animation-delay: -0.8s;
            }
            .lds-grid div:nth-child(8) {
              top: 56px;
              left: 32px;
              animation-delay: -1.2s;
            }
            .lds-grid div:nth-child(9) {
              top: 56px;
              left: 56px;
              animation-delay: -1.6s;
            }
            @keyframes lds-grid {
              0%, 100% {
                opacity: 1;
              }
              50% {
                opacity: 0.5;
              }
            }
            
            </style>
            <div class="lds-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
            <div style="padding: 5px; clear: both;">
                <h4>... We are waiting 20s for your MBWay App Approval</h4>
            </div>
            <div style="padding: 5px; clear: both;">
                <a class="button wc-backward" href="' . esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))) . '">' . __('Return to shop', 'wceasypay') . ' </a>
            </div>
            <div style="padding: 5px; clear: both;">
                <a id="wc-ep-cancel-order" class="button wc-backward" href="#">' . __('Cancel Purchase', 'wceasypay') . ' </a>
            </div>

        '; // we are going to put this in a cycle and work with the timer down and the animation with easypay color scheme

        return $html;
    }

    public function get_visa_template($visa_url)
    {
        if (wp_redirect($visa_url)) {
            exit;
        } else {
            $msg = __(
                '<strong>Easypay Gateway Not Avaiable</strong>',
                'wceasypay'
            );
            $msgFinal = sprintf($msg);
            echo sprintf($this->error, $msgFinal);
        }
    }

}

