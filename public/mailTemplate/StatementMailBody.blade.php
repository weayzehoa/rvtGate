<style>
    body{
        font-family: Microsoft JhengHei,arial,sans-serif !important;
    }
</style>
<body>
    <h2>您好</h2>
    <span style="font-size:24px">附件為 貴司 {{ $details['statementMonth'] }} 月份（{{ $details['statementDateRang'] }}）出貨明細，供貴司查閱，</span>
    <span style="font-size:32px">如對於對帳單有疑問，請於3個工作天內提出 。</span>
    <br>
    <span style="font-size:24px">請開立 {{ $details['statementYear'] }} 年 {{ $details['statementMonth'] }} 月份收據/發票（日期不拘），並</span>
    <span style="font-size:32px">盡速寄出，我司需於 <span style="background-color:yellow">{{ $details['getBefore'] }}</span> 前 收到發票，以利當月立帳。</span><span style="color:red; font-size:24px;">(*如遇假日請提前安排)</span>
    <br>
    <p><span style="font-size:20px">若未於上述日期收到發票，iCarry 將順延至下期付款，</span></p>
    <p style="font-size:20px">*當期對帳單的發票，最晚需於2個月內寄回。</p>
    <br>
    <p style="font-size:20px;"><b>本期帳款付款日為 {{ $details['payDate'] }}（如遇假日，順延至下一工作日）</b></p>
    <br>
    <p style="font-size:18px"><u>發票抬頭及統編 ：</u></p>
    <p style="font-size:20px"><b>公司：直流電通股份有限公司</b></p>
    <p style="font-size:20px"><b>統編：46452701</b></p>
    <br>
    <p style="font-size:18px"><u>發票寄至：</u></p>
    <p style="font-size:20px"><b>地址：台北市中山區南京東路三段103號11樓之一</b></p>
    <p style="font-size:20px"><b>收件人：直流電通_杜雅媛 收</b></p>
    <p style="font-size:20px"><b>電話：02-2508-2891</b></p>
    <br>
    <p style="font-size:20px">以上若有任何問題，請不吝告知</p>
    <br>
    <br>
    --<br>
    謝謝!!<br>
    Best Regards,<br>
    Anita 杜雅媛<br>
    ****************************************<br>
    直流電通股份有限公司<br>
    Http : www.icarry.me<br>
    TEL：886-2-2508-2891<br>
    FAX：886-2-2508-2902<br>
    地址:台北市中山區南京東路三段103號11樓之一<br>
</body>
