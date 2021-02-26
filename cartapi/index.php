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

header('Cache-Control: no-store');

$meta = new Meta();


$meta->addArgument('token');
$meta->addArgument('order_nick', function ($val) {
	$this->ans['order_nick'] = $val;
	return $val;
});

$meta->addArgument('status', function ($status) {
	if (in_array($status, ['success','error'])) {		
		return $status;
	} else {
		return $this->fail('meta.required','status');
	}
});
$meta->addArgument('orderid'); //Банковский orderId
$meta->addArgument('place', function ($place) {
	if (in_array($place, ['admin','orders'])) {		
		return $place;
	} else {
		return $this->fail('meta.required','place');
	}
});
$meta->addVariable('order', function () {
	extract($this->gets(['order_nick','user','place']));
	$order = Cart::getByNick($order_nick);
	
	if (!$order) return $this::fail('order404');
	
	if ($place == 'admin' && !$user['admin']
		|| $place == 'orders' && !Cart::isOwner($order['order_id'], $user['user_id'])
	) return $this->err('meta.forbidden');

	if ($order['pay'] != 'card') return $this->fail('needpay');

	if (!in_array($order['status'], ['pay','check'])) return $this->fail('needstatuspay');
	if (!$order['total']) return $this->fail('needtotal');

	return $order;
});
$meta->addVariable('user', function () {
	extract($this->gets(['token']));
	$user = User::fromToken($token);
	if (!$user) return $this->fail('guest');
	$user = array_intersect_key($user, array_flip(['user_id','admin','email','timezone']));
	$this->ans['user'] = $user;
	return $user;
});

$meta->addAction('infofromorder', function () {
	extract($this->gets(['order']));
	$this->ans['info'] = $order['paydata'];
	return $this->ret();
});

$meta->addAction('getinfo', function () { 
	//Страница с сообщением об успешной оплате по редиректу с банка
	extract($this->gets(['order','user', 'status']), EXTR_REFS);
	$this->ans['user'] = $user;
	if (!empty($order['paydata']['result'])) {
		//Успешную оплату больше не перепроверяем
		$this->ans['info'] = $order['paydata'];
		return $this->ret();
	}

	extract($this->gets(['info'])); //При вызове info информация сохраняется в paydata	

	$this->ans['info'] = Pay::safePayData($info);

	if ($status == 'error') {
		return $this->get('error', ['info' => $info, 'code'=>'bankerror', 'payload'=> $info['error']]);
	}

	if ($info['result']) {
		if ($order['status'] == 'pay') {
			$r = Cart::setStatus($order['order_id'], 'check');
			if (!$r) return $this->get('error', ['info' => $info, 'code'=>'dbfail', 'payload' => 'status' ]);
			$r = Pay::setPaid($order['order_id']);
			if (!$r) return $this->get('error', ['info' => $info, 'code'=>'dbfail', 'payload' => 'paid' ]);
			$r = Pay::mail($order['order_id']);
			if (!$r) return $this->fail('nomail');
		}
	}
	return $this->ret();
});
$meta->addAction('pay', function () {
	return $this->get('pay#'.Pay::$conf['bank']);
});


$meta->addVariable('info', function () {
	extract($this->gets(['info#'.Pay::$conf['bank']]), EXTR_REFS);
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
	$payload = isset($obj['payload']) ? $obj['payload'] : true;
	return $this->fail($obj['code'], $payload);
});









$meta->addFunction('pay#sbrfpay', function () {
	extract($this->gets(['user','order']));
	if (!empty($order['paydata']['error'])) { 
		//После появления ошибки Сбер не позволяет сделать повторную попытку
		$res = $order['paydata'];
		//unset($res['formUrl']);
	} else if (!empty($order['paydata']['formUrl']) ) {// && empty($order['paydata']['error']) //Если была ошибка нельзя сгенерировать новую ссылку на тот же заказ		
		$res = $order['paydata'];
	} else {
		$res = Sbrfpay::getId($order['order_nick'], $order['total']);
		if (!$res) return $this::get('error', [ 'code' => 'erconnect', 'payload' => 'empty']);
		$r = Pay::savePayData($order['order_id'], $res); //Сохраняем и ошибку
		foreach (['formUrl', 'orderId'] as $prop) {
			if (empty($res[$prop])) return $this::get('error', [ 'res' => $res, 'code' => 'erconnect', 'payload' => $prop]);
		}
		if (!empty($res['errorCode'])) {
			$payload = empty($res['errorMessage']) ? 'errorCode '.$res['errorCode'] : 'errorMessage '.$res['errorMessage'];
			return $this::get('error', ['res' => $res, 'code' => 'erconnect', 'payload' => $payload]);
		}
		if (!$r) return $this->get('error', ['res' => $res, 'code'=>'dbfail', 'payload'=> 'savePayData']);	
	}
	$this->ans['info'] = Pay::safePayData($res);
	return $this->ret();
});
$meta->addFunction('info#sbrfpay', function () {
	extract($this->gets(['orderid','order']));	
	$info = Sbrfpay::getInfo($orderid); //Pay::$infoprops
	if (!empty($order['paydata']['formUrl'])) {
		//formUrl не повторяется, нужно переносить его с момента первого вызова
		$info['formUrl'] = $order['paydata']['formUrl'];
	}
	if (!$info) return $this->fail('erconnect', 'info');
	if ($info['order_nick'] != $order['order_nick']) return $this->fail('order404');

	$r = Pay::savePayData($order['order_id'], $info);
	if (!$r) return $this->fail('error','info');
	return $info;
});





$meta->addFunction('info#paykeeper', function () {
	//paykeeper работает через оповещения на callback
	//-pay/paykeeper/callback.php
	return $this->fail();
});
$meta->addFunction('pay#paykeeper', function () {
	extract($this->gets(['user','order']));
	$info = Paykeeper::getId($order['order_nick'], $order['total'], $order['email'], $order['phone'], $order['name']);
	if (!$info) return $this::get('error', [ 'code' => 'erconnect', 'payload' => 'pay']);
	$this->ans['info'] = Pay::safePayData($info);
	return $this->ret();
});


















//lang уже в переменной $this->lang
return $meta->init([
	'name'=>'pay',
	'base'=>'-pay/cartapi/'
]);
