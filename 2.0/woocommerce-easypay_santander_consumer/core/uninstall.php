<?php
if (!defined('ABSPATH')) {
    exit;
}

// Soft deactivation
function wceasypay_deactivation_santander_consumer()
{
    $option_name = 'woocommerce_easypay_santander_consumer_settings';

    delete_option($option_name);

    delete_site_option($option_name);

}