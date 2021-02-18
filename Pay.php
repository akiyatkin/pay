<?php
namespace akiyatkin\pay;
use infrajs\lang\LangAns;
use infrajs\db\Db;
use infrajs\cart\Cart;
use infrajs\cache\CacheOnce;
use infrajs\user\User;

class Pay {
	public static $infoprops = ['total','orderId','date','order_nick','result','description','error','formUrl'];
	public static $conf = [];
	public static $name = 'pay';
	use CacheOnce;
	use LangAns;
	public static function safePayData($paydata) {
		$info = array_intersect_key($paydata, array_flip(Pay::$infoprops));
		if (empty($info['error'])) $info['error'] = '';
		return $info;
	}
	public static function setPaid($order_id) {
		return Db::exec('UPDATE cart_orders
		 	SET paid = 1
			WHERE order_id = :order_id
		', [
		 	':order_id' => $order_id
		]) !== false;
	}
	public static function savePayData($order_id, $paydata) {
		return Db::exec('UPDATE cart_orders
		 	SET paydata = :paydata, dateedit = now()
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