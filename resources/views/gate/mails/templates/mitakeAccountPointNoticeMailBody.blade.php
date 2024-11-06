管理者您好,<br><br>
@if($details['AccountPoint'] == 0)
直流電通於三竹簡訊點數餘額已用盡，系統已無法發送簡訊，請立即補充。<br>
@else
直流電通於三竹簡訊點數餘額已小於{{ $details['AccountPoint'] }}，請及時補充，以免造成系統無法發送簡訊。<br>
@endif
<br>
此為中繼系統自動發出，請勿回信。<br>
<br>
--<br>
謝謝!!<br>
Best Regards,<br>
<br>
****************************************<br>
直流電通股份有限公司<br>
Http : www.icarry.me<br>
TEL：886-2-2508-2891<br>
FAX：886-2-2508-2902<br>
地址:台北市中山區南京東路三段103號11樓之一
