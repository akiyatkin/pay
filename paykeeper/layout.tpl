{ans::}-ans/ans.tpl
{model::}-catalog/model.tpl
{ordernick:}№{data.order.order_nick}
{root:}
	{(:Онлайн оплата):utilcrumb}
	<h1>Оплата заказа <span>{:ordernick}</span></h1>
	{data.formURL:redirect}
	{data.msg?data:ans.msg}
	{data.order:INFO}
	{redirect:}
		<p>После нажатия на кнопку откроется страница банка для ввода платёжных данных.</p>
		<a target2="about:blank" class="btn btn-lg btn-success" href="{.}">Оплатить {~cost(data.order.total)}{:model.unit}</a>
		<script>
			location.replace("{.}")
		</script>
{INFO:}
	{paydata.sum?paydata:showinfo}
	{showinfo:}	
	<div class="alert alert-success">
		<div>Выполнена оплата <b>{~date(:d.m.Y H:i,..datecheck)}</b>.</div>
		<div>Сумма <b>{~cost(sum)}{:model.unit}</b></div>
	</div>
{DESCR:}
	<i>После нажатия на кнопку <b>Оплатить</b> откроется платёжный шлюз, где будет предложено ввести платёжные данные карты для оплаты заказа.</i>
	<center>
		<img class="img-fluid my-3" src="/-cart/paykeeper/logo3h.png">
	</center>
	<p>
		Ознакомьтесь с информацией <a href="/company">о компании</a>, <a href="/contacts">контакты и реквизиты</a>, <a href="/guaranty">гарантийные условия</a>, <a href="/terms">политика конфиденциальности</a>, <a href="/return">возврат и обмен</a>.
	</p>
{utilcrumb:}
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a class="{data.user.admin?:text-danger}" href="/cart">Личный кабинет</a></li>
		<li class="breadcrumb-item"><a href="/cart/orders/{data.order.order_nick|:active}">Оформление заказа {data.order.order_nick}</a></li>
		<li class="breadcrumb-item active">{.}</li>
	</ol>