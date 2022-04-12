<?php
if (!defined('ABSPATH')) {
    exit;
}

// Soft deactivation
function wceasypay_deactivation_universo_flex()
{
    $option_name = 'woocommerce_easypay_universo_flex_settings';

    delete_option($option_name);

    delete_site_option($option_name);

}