<?php

namespace Arturishe21\LiqPay;

use Arturishe21\LiqPay\Support\LiqPay as AbstractLiqPay;

class LiqPay
{
    protected AbstractLiqPay $client;

    public function __construct()
    {
    	$this->client = new AbstractLiqPay(config('liqpay.public_key'), config('liqpay.private_key'));
    }

    public function pay(
    	$amount 		= 1.00,
    	$currency 		= 'UAH',
    	$description 	= 'foo',
    	$order_id 		= "bar",
        $result_url     = "",
        $server_url     = ""
    ) {
    	$form = $this->client->cnbForm(array(
		'action'         => 'pay',
		'amount'         => $amount,
		'currency'       => $currency,
		'description'    => $description,
		'order_id'       => $order_id,
		'version'        => '3',
        'result_url'     => $result_url,
        'server_url'     => $server_url
		));

        $script = '<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script><script type="text/javascript">jQuery(document).ready(function($) {$("form").submit();});</script> ';

        return $form . $script;
    }

    public function status(string $orderId = "bar"): mixed
    {
        $data = $this->client->api("request", array(
            'action'        => 'status',
            'version'       => '3',
            'order_id'      => $orderId
        ));

        return json_encode($data, TRUE);
    }

}