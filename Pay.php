<?php
namespace akiyatkin\pay;
use infrajs\lang\LangAns;
use infrajs\db\Db;
use infrajs\cache\CacheOnce;

class Pay {
	public static $infoprops = ['total','orderId','formUrl','date','order_nick','result','description','error'];
	public static $conf = [];
	public static $name = 'pay';
	use CacheOnce;
	use LangAns;
	public static function savePayData($order_id, $paydata) {
		return Db::exec('UPDATE cart_orders
		 	SET paydata = :paydata, paid = 1, dateedit = now()
			WHERE order_id = :order_id
		', [
		 	':order_id' => $order_id,
		 	':paydata' => json_encode($paydata, JSON_UNESCAPED_UNICODE)
		]) !== false;
	}
}