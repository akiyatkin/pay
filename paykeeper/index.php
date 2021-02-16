<?php
use infrajs\config\Config;
use infrajs\cart\Cart;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\db\Db;
use infrajs\nostore\Nostore;
use infrajs\cart\paykeeper\Paykeeper;
use infrajs\cart\Pay;
use infrajs\user\User;

$ans = [];
$res = Pay::check($ans, $order);
if ($res) return $res;
$order_nick = $order['order_nick'];

$link = Paykeeper::getLink($order_nick, $order['total'], $order['email'], $order['phone'], $order['name']);
if (!$link) return Ans::err($ans, 'Ошибка соединения. Код PK108');

$ans['formURL'] = $link;
// $r = Db::exec('UPDATE cart_orders
// 	SET paydata = :paydata, dateedit = now()
// 	WHERE order_id = :order_id
// ', [
// 	':order_id' => $order['order_id'],
// 	':paydata' => json_encode($paydata, JSON_UNESCAPED_UNICODE)
// ]) !== false;

$ans['order'] = $order;
return Ans::ret($ans);