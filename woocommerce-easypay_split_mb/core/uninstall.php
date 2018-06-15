<?php
if (!defined('ABSPATH')) {
	exit;
}

function wceasypay_deactivation_split_mb() {
    $option_name = 'woocommerce_easypay_split_mb_settings';
    delete_option( $option_name );
    delete_site_option( $option_name );

}
