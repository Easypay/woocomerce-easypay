<?php
if (!defined('ABSPATH')) {
    exit;
}

// Soft deactivation
function wceasypay_deactivation_mb_2()
{
    $option_name = 'woocommerce_easypay_mb_2_settings';

    delete_option($option_name);

    delete_site_option($option_name);

}