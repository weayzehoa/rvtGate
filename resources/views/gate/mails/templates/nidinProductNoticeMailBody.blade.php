管理者您好,<br>
{{ $details['subject'] }}<br><br>
{{ $details['message'] }}<br><br>
商品資料如下<br>
商品料號：{{ $details['product']['digiwin_no'] }}<br>
商品名稱：{{ $details['product']['name'] }}<br>
商品單價：{{ $details['product']['price'] }}<br>
商品狀態：{{ $details['product']['status'] }}<br>
<br>
請盡快至<a href="https://{{ env('ADMIN_DOMAIN') }}"> iCarry後台 </a>(https://{{ env('ADMIN_DOMAIN') }}) 審核商品，並通知商家審核狀況。
<br>
此信件為系統自動發出，請勿回覆，謝謝!!<br>
<br>
Best Regards,<br>
****************************************<br>
直流電通股份有限公司<br>
Http : www.icarry.me<br>
TEL：886-2-2508-2891<br>
FAX：886-2-2508-2902<br>
地址:台北市中山區南京東路三段103號11樓之一
