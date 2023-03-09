<?php

namespace akiyatkin\pay\paykeeper;

use infrajs\config\Config;
use infrajs\cart\Cart;
use infrajs\load\Load;
use infrajs\db\Db;
use infrajs\ans\Ans;
use infrajs\user\User;
use akiyatkin\city\City;



class Paykeeper
{
	public static function err($ans, $msg) {
		$ans['msg'] = $msg;
		$ans['result'] = 0;
		$ans['post'] = $_POST;
		$ans['request'] = $_SERVER['QUERY_STRING'];
		$ans['date'] = date('d.m.Y H:i');
		$json = json_encode($ans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$save = 'data/auto/.paykeeper-callback-error.json';
		file_put_contents($save, $json);
		return Ans::ans($ans);
	}
	
	public static function mail($order) {
		$user = User::getByEmail($order['email']);
		$user['order'] = $order;
		$r1 = Cart::mailtoadmin($user, $user['lang'], 'AdmOrderToCheck');
		$r2 = Cart::mail($user, $user['lang'], 'orderToCheck');
		return $r1 && $r2;
	}
	public static function getId($order_nick, $amount, $email, $phone, $fio)
	{
		$conf = Config::get('pay');
		$conf = $conf['paykeeper'];


		# Логин и пароль от личного кабинета PayKeeper
		$user = $conf['userName'];
		$password = $conf['password'];
		$server = $conf['server'];

		# Basic-авторизация передаётся как base64
		$base64 = base64_encode("$user:$password");
		$headers = array();
		array_push($headers, 'Content-Type: application/x-www-form-urlencoded');
		# Подготавливаем заголовок для авторизации
		array_push($headers, 'Authorization: Basic ' . $base64);

		# Параметры платежа, сумма - обязательный параметр
		# Остальные параметры можно не задавать
		$payment_data = array(
			"pay_amount" => number_format($amount, 2, '.', ''),
			"clientid" => $fio . ' (' . $email . ')',
			"orderid" => $order_nick,
			"client_email" => $email,
			"service_name" => "Заказ",
			"client_phone" => $phone
		);

		# Готовим первый запрос на получение токена безопасности
		$uri = "/info/settings/token/";

		# Для сетевых запросов в этом примере используется cURL
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $server . $uri);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		
		//wtf
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		# Инициируем запрос к API
		$response = curl_exec($curl);
		$php_array = json_decode($response, true);

		# В ответе должно быть заполнено поле token, иначе - ошибка
		if (isset($php_array['token'])) $token = $php_array['token'];
		else return false;

		# Готовим запрос 3.4 JSON API на получение счёта
		$uri = "/change/invoice/preview/";

		# Формируем список POST параметров
		$request = http_build_query(array_merge($payment_data, array('token' => $token)));

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $server . $uri);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

		$response = json_decode(curl_exec($curl), true);
		# В ответе должно быть поле invoice_id, иначе - ошибка
		if (isset($response['invoice_id'])) $invoice_id = $response['invoice_id'];
		else return false;

		# В этой переменной прямая ссылка на оплату с заданными параметрами
		$link = "$server/bill/$invoice_id/";
		$info = [
			'order_nick' => $order_nick,
			'amount' => $amount,
			'orderId' => $token, 
			'formUrl' => $link
		];
		# Теперь её можно использовать как угодно, например, выводим ссылку на оплату
		return $info;
	}
}
