<?php
if (!defined('ABSPATH')) {
	exit;
}

function wceasypay_deactivation_cc() {
    $option_name = 'woocommerce_easypay_cc_settings';
    delete_option( $option_name );

    delete_site_option( $option_name );  

    #global $wpdb;
    #$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}easypay_notifications" ) or die("Could not delete table!");
    #$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}easypay_transaction_keys" ) or die("Could not delete table!");
}