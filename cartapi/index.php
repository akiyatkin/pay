<?php

use akiyatkin\pay\Pay;
use infrajs\user\User;
use akiyatkin\meta\Meta;
use infrajs\cart\Cart;
use infrajs\load\Load;
use infrajs\ans\Ans;
use akiyatkin\fs\FS;

use akiyatkin\pay\sbrfpay\Sbrfpay;
use akiyatkin\pay\paykeeper\Paykeeper;


$meta = new Meta();


$meta->addArgument('token');
$meta->addArgument('order_nick');
$meta->addArgument('orderid'); //Банковский orderId
$meta->addArgument('place', function ($place) {
	if (in_array($place, ['admin','orders'])) {
		return $place;
	} else {
		return $this->fail('meta.requrired','place');
	}
});
$meta->addVariable('order*', function () {
	extract($this->gets(['order_nick','user*','place']));
	$order = Cart::getByNick($order_nick);
	if (!$order) return $this::fail('order404');
	if ($place == 'admin' && !$user['admin']
		|| $place == 'orders' && !Cart::isOwner($order['order_id'], $user['user_id'])
	) return $this->err('meta.forbidden');
	if ($order['pay'] != 'card') return $this->fail('needpay');
	if ($order['status'] != 'pay') return $this->fail('needstatuspay');
	if (!$order['total']) return $this->fail('needtotal');
	$ans['order'] = $order;
	return $order;
});
$meta->addVariable('user*', function () {
	extract($this->gets(['token']));
	$user = User::fromToken($token);
	if (!$user) return $this->fail('guest');
	$user = array_intersect_key($user, array_flip(['user_id','admin','email','timezone']));
	$this->ans['user'] = $user;
	return $user;
});

$meta->addAction('infofromorder', function () {
	extract($this->gets(['order*']));
	$info = $order['paydata'];
	$this->ans['info'] = array_intersect_key($info, array_flip(Pay::$infoprops));
	return $this->ret();
});

$meta->addAction('success', function () { 
	//Страница с сообщением об успешной оплате по редиректу с банка
	$info = $this->get('info*');
	$this->ans['info'] = array_intersect_key($info, array_flip(Pay::$infoprops));
	return $this->ret();
});
$meta->addAction('pay', function () {
	return $this->get('pay-'.Pay::$conf['bank']);
});

// $meta->addAction('layer.json', function () {
// 	echo '<pre>';
// 	exit;
// 	$src = '-pay/'.Pay::$conf['bank'].'/layer.json';
// 	$layer = Load::loadJSON($src);
// 	$this->ans = $layer;
// });






$meta->addVariable('info*', function () {
	$info = $this->get('info-'.Pay::$conf['bank']);
	return $info;
});
$meta->addFunction('error', function ($obj) {
	$ans = $this->ans;
	$ans['obj'] = $obj;
	$ans['result'] = 0;
	$ans['post'] = $_POST;
	$ans['request'] = $_SERVER['QUERY_STRING'];
	$ans['date'] = date('d.m.Y H:i');
	
	$json = json_encode($ans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	$save = 'data/auto/.pay-callback-error.json';
	file_put_contents($save, $json);

	return $this->fail($obj['code']);
});









$meta->addFunction('orderfrominfo', function () {
	extract($this->gets(['info*']));
	$order_nick = $info['order_nick'];
	$order = Cart::getByNick($order_nick);
	if (!$order) return $this->fail('order404');

	$r = Pay::savePayData($order['order_id'], $info);
	if (!$r) return $this->get('error', [
		'info' => $info,
		'code'=>'dbfail'
	]);
	return $order;
});

$meta->addFunction('info-sbrfpay', function () {
	extract($this->gets(['orderid']));	
	$info = Sbrfpay::getInfo($orderid);  //'total','orderId','formUrl','date'

	if (!$info) return $this->fail('erconnect');

	$order_nick = $info['orderNumber'];
	$order = Cart::getByNick($order_nick);
	if (!$order) return $this->fail('order404');
	$r = Pay::savePayData($order['order_id'], $info);
	
	if (!$r) return $this->get('error', [
		'info' => $info,
		'code'=>'dbfail'
	]);

	return $info;
});
$meta->addFunction('info-paykeeper', function () {
	return $this->fail();
});













$meta->addFunction('pay-sbrfpay', function () {
	extract($this->gets(['user*','order*']));

	if ($order['paydata']) {
		if (empty($order['paydata'])) return $this->fail('error', 1);
		if (empty($order['paydata']['formUrl'])) return $this->fail('error', 2);
		$info = $order['paydata'];
	} else {
		$info = Sbrfpay::getId($order['order_nick'], $order['total']);
		if (!empty($info['errorCode'])) return $this::get('error', [
			'info' => $info,
			'code' => $info['errorMessage']
		]);
		
		// $order['sbrfpay'] = [];
		// $order['sbrfpay']['orderId'] = $ans['orderId'];
		// $order['sbrfpay']['formUrl'] = $ans['formUrl'];
		$r = Pay::savePayData($order['order_id'], $info);
		if (!$r) return $this->get('error', [
			'info' => $info,
			'code'=>'dbfail'
		]);	
	}
	
	$this->ans['orderId'] = $info['orderId'];
	$this->ans['formUrl'] = $info['formUrl'];
	return $this->ret();
});
$meta->addFunction('pay-paykeeper', function () {
	extract($this->gets(['user*','order*']));
	$link = Paykeeper::getLink($order['order_nick'], $order['total'], $order['email'], $order['phone'], $order['name']);
	if (!$link) return $this->fail($ans, 'erconnect');
	$ans['formURL'] = $link;
	return $this->ret();
});


















//lang уже в переменной $this->lang
return $meta->init([
	'name'=>'pay',
	'base'=>'-pay/cartapi/'
]);
