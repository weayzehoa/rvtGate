<style>
    body{
        font-family: Microsoft JhengHei,arial,sans-serif !important;
    }
</style>
<body>
<table align="center" border="1" style="border:1px #000000 solid; width:820px">
	<tbody>
		<tr>
			<td style="border-color:#000000; border-style:solid; border-width:1px">訂單通知</td>
		</tr>
		<tr>
			<td>親愛的 顧客 您好：</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr>
			<td>您的訂單【{{ $details['order']['order_number'] }}】已經出貨，您可以透過<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢物流狀態。</td>
		</tr>
		<tr>
			<td>提醒您！</td>
		</tr>
		<tr>
			<td>本通知函為已出貨之通知，並不代表訂單已配達或完成。</td>
		</tr>
	</tbody>
</table>
<br>
<table align="center" border="1" style="border:1px #000000 solid; width:820px">
	<tbody>
		<tr>
			<td style="border-color:#000000; border-style:solid; border-width:1px">訂單資訊</td>
		</tr>
		<tr>
			<td>訂單編號：【{{ $details['order']['order_number'] }}】</td>
		</tr>
		<tr>
			<td>訂購日期：【{{ $details['order']['create_time'] }}】</td>
		</tr>
		<tr>
			<td>付款日期：【{{ $details['order']['pay_time'] }}】</td>
		</tr>
		<tr>
			<td>訂單明細：請於<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢。</td>
		</tr>
	</tbody>
</table>
<br>
<table align="center" border="1" style="background-color:#dddddd; border:1px #000000 solid; width:820px">
	<tbody>
		<tr>
			<td>※ 此信件為系統發出信件，請勿直接回覆。若您有訂單方面問題請洽詢線上客服，</td>
		</tr>
		<tr>
			<td>或撥打+886 906486688，將會有專人為您服務。</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr>
			<td>iCarry官方網站：https://icarry.me</td>
		</tr>
		<tr>
			<td>公司名稱：直流電通股份有限公司</td>
		</tr>
		<tr>
			<td>客服電話：+886 906486688</td>
		</tr>
	</tbody>
</table>
<br>
<div style="text-align:center">Copyright &copy; {{ date('Y') }} icarry.me直流電通股份有限公司｜台北市中山區南京東路三段103號11樓之1</div>

<p style="text-align:center"><img src="https://api.icarry.me/image/logo_test.png" style="width:200px" /></p>
</body>