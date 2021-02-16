<?php
use infrajs\config\Config;
use infrajs\cart\Cart;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\view\View;
use infrajs\cart\sbrfpay\Sbrfpay;
use infrajs\cart\Pay;

$ans = [];
$res = Pay::check($ans, $order);
if ($res) return $res;
$order_nick = $order['order_nick'];


$status = Ans::get('status');
if (empty($_GET['orderId'])) {
	//Перешли в корень страницы /id/sbrfpay/
	
	if (isset($order['sbrfpay']['orderId'])) {
		//Нужно проверить статус, может уже всё оплачено
		$orderId = $order['sbrfpay']['orderId'];	
		$info = Sbrfpay::getInfo($orderId);
		if (!$info) return Ans::err($ans, 'Не удалось получить статус заказа. Код 404');
		if ($info['orderNumber'] != $id) return Ans::err($ans, 'Ошибка уникальности номера заказа. Код 204');

		
		if ($info['orderStatus'] == 2) { //Вся сумма авторизирована\
			//Во всех остальных случаях пробуем ещё раз оплатить
			$order['sbrfpay']['info'] = $info; //Сохраняем только если успхе
			$status = 'success';
			
			$order['status'] = 'check';
			Cart::saveOrder($order, $place);
			$ans['order'] = $order;
			$ans['msg'] = 'Заказ оплачен. Уникальный номер заказа в системе '.$orderId;
			return Cart::ret($ans, 'check');
		} else if ($info['orderStatus'] == 0) {
			//Заказ в ожидании
			//Номер заказа и ссылка при этом не меняются и $info сохранять не надо
		} else {
			//6 [actionCodeDescription] => Истек срок ожидания ввода данных.
			return Ans::err($ans, $info['actionCodeDescription'].' Код банка '.$info['errorCode'].'.');
		} 

		$ans['orderId'] = $order['sbrfpay']['orderId'];
		$ans['formUrl'] = $order['sbrfpay']['formUrl'];
		$action = 'check';
		$ans['order'] = $order;
		return Ans::ret($ans, $action);
	} else {
		$ogood = $order;
		
		$res = Sbrfpay::getId($order_nick, $ogood['total']);
	
		if (!empty($res['errorCode'])) return Ans::err($ans, $res['errorMessage']);
		
		$ans['orderId'] = $res['orderId'];
		$ans['formUrl'] = $res['formUrl'];	
		$order = Cart::loadOrder($id);
		$order['sbrfpay'] = [];
		$order['sbrfpay']['orderId'] = $ans['orderId'];
		$order['sbrfpay']['formUrl'] = $ans['formUrl'];
		Cart::saveOrder($order, $place);
		$ans['order'] = $order;	
		return $ans;
	}
} else {
	// $orderId = $_GET['orderId'];
	// if (empty($orderId)) return Ans::err($ans, 'Ссылка устарела. Код 001');
	
	// if (empty($status) || !in_array($status, ['error','success'])) return Ans::err($ans, 'Ссылка устарела. Код 010');
	
	
	// if (empty($order['sbrfpay']) || $order['sbrfpay']['orderId'] != $orderId) return Ans::err($ans, 'Ссылка устарела. Код 002');
	
	// if ($order['status'] == 'sbrfpay') {
	// 	$info = Sbrfpay::getInfo($orderId);
	// 	if ($info['orderStatus'] == 2) { //Вся сумма авторизирована
	// 		//Оплачено и статус меняется
	// 		$order['sbrfpay']['info'] = $info;
	// 		$order['status'] = 'check';
	// 		Cart::saveOrder($order, $place);
	// 		$ans['order'] = $order;
	// 		$ans['msg'] = 'Заказ оплачен';
	// 		return Cart::ret($ans, 'check');
	// 	} else {
	// 		//Cart::saveOrder($order, $place);
	// 		$ans['order'] = $order;
	// 		return Ans::err($ans, $info['actionCodeDescription'].' Код банка '.$info['errorCode'].'.');
	// 	}
	// }
	// $ans['order'] = $order;
	// return Ans::ret($ans);
	
}

