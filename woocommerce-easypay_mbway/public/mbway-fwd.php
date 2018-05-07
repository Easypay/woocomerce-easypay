<?php
$cenas = array(
        'get' => print_r($_GET, true),
        'post' => print_r($_POST, true),
        'raw body' => file_get_contents('php://input')
        );

file_put_contents('temp.log', print_r($cenas, true), FILE_APPEND);

file_put_contents('temp.log', PHP_EOL . ">>>>>>>" . PHP_EOL, FILE_APPEND);

$explodedFilePath = explode('wp-content', __FILE__);
$wpLoadFilePath   = reset($explodedFilePath) . '/wp-load.php';

if (!is_file($wpLoadFilePath)) {
	exit;
}

require_once $wpLoadFilePath;

global $wpdb;

$wcep = new WC_Gateway_Easypay_MBWay();

$json_payload = json_decode(file_get_contents('php://input'));

file_put_contents('temp.log', print_r($json_payload, true), FILE_APPEND);

/*

http://test.easypay.pt/_s/api_easypay_01BG.php?ep_cin=6793&ep_user=TESTE300810&ep_entity=10611&ep_ref_type=auto&ep_country=PT&ep_language=PT&t_value=1.02&t_key=3&o_name=John+Doe&o_description=&o_obs=&o_mobile=912+345+678&o_email=tec%40easypay.pt&s_code=d0846a2cbda2819540920acc1b61c603
http://test.easypay.pt/_s/api_easypay_05AG.php?e=10611&r=679300059&v=1.02&mbway=yes&mbway_title=TestesEPWP&mbway_type=authorization&mbway_phone_indicative=351&mbway_phone=911234567&mbway_currency=EUR&t_key=1&s_code=d0846a2cbda2819540920acc1b61c603

*/
