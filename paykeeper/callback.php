<?php

use infrajs\ans\Ans;
use akiyatkin\pay\paykeeper\Paykeeper;
use infrajs\cart\Cart;
use infrajs\user\User;
use infrajs\load\Load;
use akiyatkin\pay\Pay;
use infrajs\db\Db;

header('Cache-Control: no-store');

$ans = array();

$info = $_REQUEST;
$conf = Pay::$conf['paykeeper'];
$secret = $conf['secret'];

$ans['info'] = &$info;
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

$info['total'] = $order['total'];
$info['orderId'] = $info['id'];
$info['date'] = time();
$info['order_nick'] = $orderid;
$info['result'] = 1;
// ['total','orderId','date','order_nick','result','description','error','formUrl'];


$r = Pay::savePayData($order['order_id'], $info); //Сохраняем и ошибку
if (!$r) return Paykeeper::err($ans, 'Неудалось сохранить ответ. Код c'.__LINE__);


if ($order['status'] != 'pay') return Paykeeper::err($ans, 'Нельзя выполнить это действие с заказом в текущем статусе. Код c'.__LINE__);

$r = Cart::setStatus($order['order_id'], 'check');
if (!$r) return Paykeeper::err($ans, 'Неудалось изменить статус заказа. Код c'.__LINE__);
$r = Pay::setPaid($order['order_id']);
if (!$r) return Paykeeper::err($ans, 'Ошибка базы данных. Код c'.__LINE__);
$r = Pay::mail($order['order_id']);
if (!$r) return Paykeeper::err($ans, 'Неудалось отправить оповещение. Код c'.__LINE__);


echo "OK " . md5($paymentid . $secret);

