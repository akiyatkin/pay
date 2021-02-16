<?php
/*    =====================================================
 *       Приём сообщений о входящем платеже от PayKeeper
 *    =====================================================
 */

  $secret_seed = "verysecretseed";

  $id = $_POST['id'];
  $sum = $_POST['sum'];
  $clientid = $_POST['clientid'];
  $orderid = $_POST['orderid'];
  $key = $_POST['key'];

  if ($key != md5($id.sprintf("%.2lf", $sum).$clientid.$orderid.$secret_seed))
  {
    echo "NO ".md5($id.$secret_seed);
    exit;
  }

  if ($orderid == "")
  {
      # Это пополнение счёта пользователя $clientid
  }
  else
  {
      # Это оплата заказа, номер заказа $orderid
  }
  echo "OK ".md5($id.$secret_seed);
?>
