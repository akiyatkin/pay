<?php

use infrajs\ans\Ans;
use infrajs\cart\paykeeper\Paykeeper;
use infrajs\nostore\Nostore;
use infrajs\config\Config;
use infrajs\cart\Cart;
use infrajs\user\User;
use infrajs\load\Load;
use infrajs\db\Db;

Nostore::on();

$ans = array();

$info = $_REQUEST;
$conf = Config::get('cart');
$conf = $conf['paykeeper'];
$secret = $conf['secret'];

$ans['info'] = $info;
foreach(['id','sum','clientid','orderid','key'] as $k) {
	if (empty($info[$k])) return Paykeeper::err($ans, 'Недостаточно данных. Код c'.__LINE__);
}
$paymentid = $info['id'];
$sum = $info['sum'];
$clientid = $info['clientid']; //fio + (email)
$orderid = $info['orderid'];
$key = $info['key'];

$mykey = md5($paymentid . number_format($sum, 2, ".", "") . $clientid . $orderid . $secret);
//echo $mykey;
if ($key != $mykey) return Paykeeper::err($ans, 'Данные повреждены. Код c'.__LINE__);
if (!$orderid) return Paykeeper::err($ans, 'Нет информации о заказе. Код c'.__LINE__);


$order = Cart::getByNick($orderid);
if (!$order) return Paykeeper::err($ans, 'Заказ не найден. Код c'.__LINE__);

if(empty($order['total'])) return Paykeeper::err($ans, 'Ошибка в стоимости заказа. Код c'.__LINE__);
$amount = $order['total'];

//echo $amount;
if (number_format($sum, 2,'.','') != number_format($amount, 2,'.','')) return Paykeeper::err($ans, 'Ошибка с суммой заказа. Код c'.__LINE__);

//if ($order['status'] != "pay") return Paykeeper::err($ans, 'Ошибка инициации оплаты. Код c'.__LINE__);
$paydata = $info;

$r = Db::exec('UPDATE cart_orders
 	SET paydata = :paydata, paid = 1, dateedit = now()
	WHERE order_id = :order_id
', [
 	':order_id' => $order['order_id'],
 	':paydata' => json_encode($paydata, JSON_UNESCAPED_UNICODE)
]) !== false;
if (!$r) return Paykeeper::err($ans, 'Неудалось сохранить ответ. Код c'.__LINE__);


$r = Cart::setStatus($order['order_id'], 'check');
if (!$r) return Paykeeper::err($ans, 'Неудалось изменить статус заказа. Код c'.__LINE__);
$ouser = User::getByEmail($order['email']);
$worder = Cart::getWaitOrder($ouser);
if ($worder) Cart::setActive($worder['order_id'], $ouser['user_id']);

Cart::$once = [];
$order = Cart::getByNick($orderid);
$r = Paykeeper::mail($order);
if (!$r) return Paykeeper::err($ans, 'Неудалось отправить оповещение. Код c'.__LINE__);
$ans['result'] = 1;
$ans['msg'] = "OK " . md5($paymentid . $secret);
		

if ($ans['result']) echo $ans['msg'];
else return Ans::ans($ans);
