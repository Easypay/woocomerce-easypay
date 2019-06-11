<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

global $wceasypay_db_version_2;
$wceasypay_db_version_2 = '2.0';

//Create EasyPay tables
function wceasypay_activation_mb_2() {

	global $wpdb, $wceasypay_db_version_2;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."easypay_notifications_2 (
							`ep_key` int(11) NOT NULL auto_increment,
							`ep_status` varchar(20) default 'pending',
							`ep_entity` varchar(10) default NULL,
							`ep_reference` varchar(9) default NULL,
							`ep_value` double default NULL,
							`ep_payment_type` varchar(10) default NULL,
							`t_key` varchar(255) default NULL,
							`notification_date` timestamp NULL default CURRENT_TIMESTAMP,
							PRIMARY KEY (`ep_key`)
					)  {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $wpdb->query($sql) or die("Error Creating Table!");

    add_option("wceasypay_db_version_2", $wceasypay_db_version_2);
}
