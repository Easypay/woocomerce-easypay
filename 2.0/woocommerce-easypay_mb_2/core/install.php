<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wceasypay_db_version_2;
$wceasypay_db_version_2 = '2.0';

//Create EasyPay tables
function wceasypay_activation_mb_2()
{

    global $wpdb, $wceasypay_db_version_2;
    $charset_collate = $wpdb->get_charset_collate();
    $notification_table = $wpdb->prefix . 'easypay_notifications_2';

    $sql = "CREATE TABLE IF NOT EXISTS $notification_table ("
        . "`ep_key` int(11) NOT NULL auto_increment,"
        . "`ep_status` varchar(20) default 'pending',"
        . "`ep_entity` varchar(10) default NULL,"
        . "`ep_reference` varchar(9) default NULL,"
        . "`ep_value` double default NULL,"
        . "`ep_payment_type` varchar(10) default NULL,"
        . "`t_key` varchar(255) default NULL,"
        . "`notification_date` timestamp NULL default CURRENT_TIMESTAMP,"
        . "PRIMARY KEY (`ep_key`)"
        . ") {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $wpdb->query($sql) or die('Error Creating Table!');
    //
    // for clients that have had the easypay plugin installed
    //
    // `ep_method` column
    $sql_table_schema_needed = "SHOW COLUMNS"
        . " FROM $notification_table";
    $table_schema = $wpdb->get_results($sql_table_schema_needed);
    $new_table_columns = [];

    $add_col = true;
    foreach ($table_schema as $col) {
        if ($col->Field == 'ep_method') {
            $add_col = false;
        }
    }
    if ($add_col) {
        $new_table_columns[] = "ADD COLUMN `ep_method` varchar(12) default NULL AFTER `t_key`";
    }

    $add_col = true;
    foreach ($table_schema as $col) {
        if ($col->Field == 'ep_payment_id') {
            $add_col = false;
        }
    }
    if ($add_col) {
        $new_table_columns[] = "ADD COLUMN `ep_payment_id` varchar(36) default NULL AFTER `ep_value`";
    }

    $add_col = true;
    foreach ($table_schema as $col) {
        if ($col->Field == 'ep_last_operation_id') {
            $add_col = false;
        }
    }
    if ($add_col) {
        $new_table_columns[] = "ADD COLUMN `ep_last_operation_id` varchar(36) default NULL AFTER `ep_payment_id`";
    }

    $add_col = true;
    foreach ($table_schema as $col) {
        if ($col->Field == 'ep_last_operation_type') {
            $add_col = false;
        }
    }
    if ($add_col) {
        $new_table_columns[] = "ADD COLUMN `ep_last_operation_type` varchar(36) default NULL AFTER `ep_payment_id`";
    }

    if (!empty($new_table_columns)) {
        $wpdb->query("ALTER TABLE $notification_table " . implode(',', $new_table_columns));
    }

    add_option("wceasypay_db_version_2", $wceasypay_db_version_2);
}
