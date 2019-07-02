<?php


/**
 * Class WC_Gateway_Easypay_Request file.
 *
 * @package WooCommerce\Gateways
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to PayPal.
 */
class WC_Gateway_Easypay_Request
{
    /**
     *
     * @param array $auth
     * @return void
     */
    public function __construct( $auth ) {
        $this->url = $auth["url"];
        $this->account_id = $auth["account_id"];
        $this->api_key = $auth["api_key"];
        $this->method = $auth["method"];
    }

    /**
     * Returns the api results
     *
     * @param array $payload
     * @return string
     */
    public function get_contents($payload)
    {
        $url = $this->url;
        $curlOpts = null;

        switch ($this->method){
            case 'POST':
                $headers = [
                    "AccountId: {$this->account_id}",
                    "ApiKey: {$this->api_key}",
                    'Content-Type: application/json',
                ];

                $curlOpts = [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => 1,
                    CURLOPT_TIMEOUT        => 60,
                    CURLOPT_POSTFIELDS     => json_encode($payload),
                    CURLOPT_HTTPHEADER     => $headers,
                ];

                break;
            case 'GET':
                $url = $url . "/" . $payload;

                $headers = [
                    "AccountId: {$this->account_id}",
                    "ApiKey: {$this->api_key}",
                    'Content-Type: application/json',
                ];

                $curlOpts = [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 60,
                    CURLOPT_HTTPHEADER     => $headers,
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

    public function get_visa_template($visa_url)
    {
        if(wp_redirect( $visa_url ) ) {
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

