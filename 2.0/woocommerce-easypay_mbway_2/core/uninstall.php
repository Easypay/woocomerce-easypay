<?php
if (!defined('ABSPATH')) {
	exit;
}

// Soft deactivation
function wceasypay_deactivation_mbway_2() {
    $option_name = 'woocommerce_easypay_mbway_2_settings';

    delete_option( $option_name );

    delete_site_option( $option_name );

}