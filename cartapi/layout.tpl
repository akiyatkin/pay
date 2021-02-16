{ans::}-ans/ans.tpl
{model::}-catalog/model.tpl
{SUCCESS:}
	{(:Успешная оплата):utilcrumb}
	<h1>Онлайн оплата</h1>
	{data.msg?data:ans.msg}
	{data.info:INFO}
	{:links}
{ERROR:}
	{(:Ошибка при оплате):utilcrumb}
	<h1>Заказ {data.id}</h1>
	{data.msg?data:ans.msg}
	{data.order:INFO}
	{:links}
{utilcrumb:}
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a class="{Session.get().safe.manager?:text-danger}" href="/cart">Личный кабинет</a></li>
		<li class="breadcrumb-item"><a href="/cart/{data.place}/{data.order.id|:my}">Оформление заказа {data.order.id}</a></li>
		<li class="breadcrumb-item active">{.}</li>
	</ol>
{redirect:}
	<p>После нажатия на кнопку откроется страница банка для ввода платёжных данных.</p>
	<a class="btn btn-lg btn-success" href="{data.formUrl}">Оплатить</a>
	<script>
		location.replace("{data.formUrl}")
	</script>
{links:}
	<!-- <p>
		<a href="/cart/orders/{data.id}">Данные заказа</a><br>
		<a href="/cart/orders/{data.id}/list">Корзина</a>
	</p> -->
{INFO:}	
	{result?:good?:bad}
	<p>{description}</p>
	{bad:}
		<p>Не оплачен</p>
		<div class="alert alert-success">{error}</div>
	{good:}
		<table style="width:auto" class="table table-sm table-striped">
			<tr><th>Оплачен</th><td>{~date(:d.m.Y H:i,date)}</td></tr>
			<tr><th>Сумма</th><td>{~cost(total)}{:model.unit}</td></tr>
		</table>
{DESCR:}
	<i>После нажатия на кнопку <b>Оплатить</b> откроется платёжный шлюз <b>ПАО&nbsp;СБЕРБАНК</b>, где будет предложено ввести платёжные данные карты для оплаты заказа.
	Введённая информация не будет предоставлена третьим лицам за исключением случаев, предусмотренных законодательством РФ. 
	Оплата происходит с использованием карт следующих платёжных систем:</i>
	<center>
		<img class="img-fluid my-3" src="/vendor/infrajs/cart/sbrfpay/cards.png">
	</center>
	<p>
		Ознакомьтесь с информацией <a href="/company">о компании</a>, <a href="/contacts">контакты и реквизиты</a>, <a href="/guaranty">гарантийные условия</a>, <a href="/terms">политика конфиденциальности</a>, <a href="/return">возврат и обмен</a>.
	</p>