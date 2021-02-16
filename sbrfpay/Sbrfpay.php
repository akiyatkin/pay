<?php
namespace akiyatkin\pay\sbrfpay;
use infrajs\config\Config;
use infrajs\cart\Cart;
use infrajs\view\View;
use akiyatkin\pay\Pay;

/*//0,1,2,3,4,5,6
	$statuses = [
		"Заказ зарегистрирован, но не оплачен",
		"Предавторизованная сумма захолдирована (для двухстадийных платежей)",
		"Проведена полная авторизация суммы заказа",
		"Авторизация отменена",
		"По транзакции была проведена операция возврата",
		"Инициирована авторизация через ACS банка-эмитента",
		"Авторизация отклонена"
	];
	$msg = $statuses[$info['orderStatus']];*/

class Sbrfpay {
	public static function getId($id, $amount) {
		

		$vars = array();
		/* ID заказа в магазине */

		$vars['orderNumber'] = $id;
		 
		/* Корзина для чека (необязательно) */
		/* $cart = array(
			array(
				'positionId' => 1,
				'name' => 'Название товара',
				'quantity' => array(
					'value' => 1,    
					'measure' => 'шт'
				),
				'itemAmount' => 1000 * 100,
				'itemCode' => '123456',
				'tax' => array(
					'taxType' => 0,
					'taxSum' => 0
				),
				'itemPrice' => 1000 * 100,
			)
		);
		 
		$vars['orderBundle'] = json_encode(
			array(
				'cartItems' => array(
					'items' => $cart
				)
			), 
			JSON_UNESCAPED_UNICODE
		);*/
		 
		/* Сумма заказа в копейках */
		$vars['amount'] = $amount * 100;

		/* URL куда клиент вернется в случае успешной оплаты */
		$vars['returnUrl'] = View::getPath().'cart/orders/'.$id.'/sbrfpay/success';
			
		/* URL куда клиент вернется в случае ошибки */
		$vars['failUrl'] = View::getPath().'cart/orders/'.$id.'/sbrfpay/error';
		 
		/* Описание заказа, не более 24 символов, запрещены % + \r \n */
		$vars['description'] = 'Заказ №' . $id . ' на '.View::getHost();

		/*
		expirationDate Дата и время окончания жизни заказа. Формат: yyyy-MM-ddTHH:mm:ss .
		Если этот параметр не передаётся в запросе, то для определения времени окончания жизни заказа используется sessionTimeoutSecs.
		*/
		$vars['expirationDate'] = (date('Y')+1).'-'.date('m').'-01T00:00:00';
		$res = Sbrfpay::request('/payment/rest/register.do', $vars);
		return $res;
	}
	
	public static function getInfo ($orderId) {
		$vars = array();
		$vars['orderId'] = $orderId;
		$info = Sbrfpay::request('/payment/rest/getOrderStatusExtended.do', $vars);
		if ($info) {
			$info['orderId'] = $orderId;
		}
		if (isset($info['terminalId'])) {
			$info['formUrl'] = 'https://3dsec.sberbank.ru/payment/merchants/'.$info['terminalId'].'/payment_ru.html?mdOrder='.$orderId;	
		}
		if (isset($info['orderNumber'])) $info['order_nick'] = $info['orderNumber'];
		if (isset($info['orderDescription'])) $info['description'] = $info['orderDescription'];
		
		if ($info['orderStatus'] == 2) $info['result'] = 1;
		else $info['result'] = 0;

		$info['error'] = '';
		if (isset($info['actionCodeDescription'])) $info['error'] = $info['actionCodeDescription'];
		
		$testres = array_intersect_key($info, array_flip(Pay::$infoprops));
		if (sizeof($testres) != sizeof(Pay::$infoprops)) return false;
		return $info;
	}
	public static function request ($src, $vars = []) {

		$conf = Pay::$conf['sbrfpay'];
		$vars['server'] = $conf['server'];
		$vars['userName'] = $conf['userName'];
		$vars['password'] = $conf['password'];

		$ch = curl_init($vars['server'].$src.'?' . http_build_query($vars));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res, JSON_OBJECT_AS_ARRAY);
		if (isset($res['amount'])) $res['total'] = round($res['amount']/100,2);
		
		if (isset($res['date'])) $res['date'] = round($res['date']/1000);
		if (isset($res['authDateTime'])) $res['authDateTime'] = round($res['authDateTime']/1000);

		return $res;
	}
	
}
