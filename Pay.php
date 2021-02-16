<?php
namespace akiyatkin\pay;
use infrajs\lang\LangAns;
use infrajs\db\Db;
use infrajs\cart\Cart;
use infrajs\cache\CacheOnce;

class Pay {
	public static $infoprops = ['total','orderId','formUrl','date','order_nick','result','description','error'];
	public static $conf = [];
	public static $name = 'pay';
	use CacheOnce;
	use LangAns;
	public static function safePayData($paydata) {
		if (is_string($paydata)) $paydata = json_decode($paydata, true);
		return array_intersect_key($paydata, array_flip(Pay::$infoprops));
	}
	public static function savePayData($order_id, $paydata) {
		return Db::exec('UPDATE cart_orders
		 	SET paydata = :paydata, paid = 1, dateedit = now()
			WHERE order_id = :order_id
		', [
		 	':order_id' => $order_id,
		 	':paydata' => json_encode($paydata, JSON_UNESCAPED_UNICODE)
		]) !== false;
	}
	public static function mail($order_id) {
		Cart::$once = []; //Сбросили кэш
		$order = Cart::getById($order_id);
		$ouser = User::getByEmail($order['email']);
		$ouser['order'] = $order;
		$r1 = Cart::mailtoadmin($ouser, Cart::$conf['lang']['def'], 'AdmOrderToCheck');
		$r2 = Cart::mail($ouser, $ouser['lang'], 'orderToCheck');
		$r = $r1 && $r2;
		return $r;
	}
}