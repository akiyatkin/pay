{ans::}-ans/ans.tpl?v={~conf.index.v}
{model::}-catalog/model.tpl?v={~conf.index.v}
{root:}
	{(:Онлайн оплата):utilcrumb}
	<h1>Онлайн оплата</h1>
	{data.msg?data:ans.msg}
	{data.info:INFO}
	{data.info.result??(data.info.formUrl?:redirect)}
	{:links}
{SUCCESS:}
	{(:Успешная оплата):utilcrumb}
	<h1>Онлайн оплата</h1>
	{data.msg?data:ans.msg}
	{data.info:INFO}
	{:links}
{ERROR:}
	{(:Ошибка при оплате):utilcrumb}
	<h1>Ошибка при оплате</h1>	
	{data.info:INFO}
	{:links}
{utilcrumb:}
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a class="{data.user.admin?:text-danger}" href="/cart">Личный кабинет</a></li>
		<li class="breadcrumb-item"><a href="/cart/orders/{data.order_nick}">Оформление заказа {data.order_nick}</a></li>
		<li class="breadcrumb-item active">{.}</li>
	</ol>
{redirect:}
	<p>После нажатия на кнопку откроется страница банка для ввода платёжных данных.</p>
	<a class="btn btn-lg btn-success" href="{data.info.formUrl}">Оплатить</a>
	<script>
		location.replace("{data.info.formUrl}")
	</script>
{links:}
	<!-- <p>
		<a href="/cart/orders/{data.id}">Данные заказа</a><br>
		<a href="/cart/orders/{data.id}/list">Корзина</a>
	</p> -->
{INFO:}
	{result?:good?(error?:bad?:nothing)}
	{nothing:}
	{bad:}
		<p>Не оплачен</p>
		<div class="alert alert-danger">
			<p>{error}</p>
			<p>Обратитесь в отдел продаж по нашим <a href="/contacts">контактам</a>.</p>
		</div>
	{good:}
		<table style="width:auto" class="table table-sm table-striped">
			<tr><th>Оплачен</th><td>{~date(:d.m.Y H:i,date)}</td></tr>
			<tr><th>Сумма</th><td>{~cost(total)}{:model.unit}</td></tr>
		</table>