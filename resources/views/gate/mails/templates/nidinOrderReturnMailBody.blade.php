管理者您好,<br>
{{ $details['subject'] }}<br><br>
資料如下<br>
@for($i=0;$i<count($details['returnItems']);$i++)
票券號碼：{{ $details['returnItems'][$i]['ticket_no'] }}<br>
@endfor
<br><br>
請盡快至<a href="https://{{ env('GATE_DOMAIN') }}"> iCarry中繼後台 </a>(https://{{ env('GATE_DOMAIN') }}) 處理退貨商品資料。
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
