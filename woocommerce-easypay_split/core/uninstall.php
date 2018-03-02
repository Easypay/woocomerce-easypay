<?php
if (!defined('ABSPATH')) {
	exit;
}

function wceasypay_deactivation_split() {
    $option_name = 'woocommerce_easypay_split_settings';
    delete_option( $option_name );
    delete_site_option( $option_name );

}
