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
        $this->test = $auth["test"];
        $this->url = $auth["url"];
        $this->account_id = $auth["account_id"];
        $this->api_key = $auth["api_key"];
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
            die; // add something later
        }
    }

}

