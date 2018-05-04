<?php
$cenas = array(
        'get' => print_r($_GET, true),
        'post' => print_r($_POST, true),
        'raw body' => file_get_contents('php://input')
        );

file_put_contents('temp.log', print_r($cenas, true), FILE_APPEND);



/*

http://test.easypay.pt/_s/api_easypay_05AG.php?e=10611&r=679300400&v=1.20&mbway=yes&mbway_title=TestesEPWP&mbway_type=purchase&mbway_phone_indicative=351&mbway_phone=911234567&mbway_currency=EUR&t_key=1&s_code=d0846a2cbda2819540920acc1b61c603&ep_k1=A3823FBCB60A430AADEED31AF9AE5481


*/
