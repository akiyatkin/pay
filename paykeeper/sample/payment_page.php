<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
<head> 
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
	<meta name="language" content="ru" /> 
</head>
<body>
<h1>Оплатите ваш заказ:</h1>
<?php
/*      
 *      ==========================================================
 *               Интеграция формы выбора способа оплаты         
 *      ==========================================================
 *	Эта страница следует после подтверждения заказа, когда уже окончательно известна его сумма и номер.
 */

	$test_login = "user1"; // Рекомендуется указывать всегда.
	$test_orderid = "232"; // Если указывается номер заказа, рекомендуется также указать сумму
	$test_sum = "7500.42"; // В рублях, копейки - дробная часть
	$test_optional_phone = "9101234567"; // Номер используется для выставления квитанции Киви. Если он известен, рекомендуется заполнить.

	$payment_parameters = http_build_query(array(   "clientid"=>$test_login,
							"orderid"=>$test_orderid,
							"sum"=>$test_sum,
							"phone"=>$test_optional_phone));
	$options = array("http"=>array(
					"method"=>"POST",
					"header"=>"Content-type: application/x-www-form-urlencoded",
					"content"=>$payment_parameters
					));
	$context = stream_context_create($options);

	// Это отобразит форму выбора способа оплаты.
	// С неё пользователь, выбрав способ оплаты, переходит на сайт нужной платёжной системы.
	echo file_get_contents("https://el-car63.server.paykeeper.ru/order/inline", false, $context); 
?>
</body>
</html>
