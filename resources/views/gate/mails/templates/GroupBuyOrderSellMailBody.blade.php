<style>
    body{
        font-family: Microsoft JhengHei,arial,sans-serif !important;
    }
</style>
<body>
    <table align="center" style="width:820px; border:1px #000000 solid;">
        <tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">訂單通知</td></tr>
        <tr><td>親愛的 團購主 您好：</td></tr>
        <tr><td></br></td></tr>
        <tr><td>您的團購訂單【{{ $details['order']['order_number'] }}】已經出貨，您可以透過<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢物流狀態。</td></tr>
        <tr><td>提醒您！</td></tr>
        <tr><td>本通知函為已出貨之通知，並不代表訂單已配達或完成。</td></tr>
    </table>
    <br />
    <table align="center" style="width:820px; border:1px #000000 solid;">
        <tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">訂單資訊</td></tr>
        <tr><td>訂單編號：【{{ $details['order']['order_number'] }}】</td></tr>
        <tr><td>成團日期：【{{ $details['order']['create_time'] }}】</td></tr>
        <tr><td>訂單明細：請於<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢。</td></tr>
    </table>

    <br />
    <table align="center" style="width:820px; border:1px #000000 solid;background-color:#DDDDDD;">
        <tr><td>※ 此信件為系統發出信件，請勿直接回覆。若您有訂單方面問題請洽詢線上客服，</td></tr>
        <tr><td>或撥打+886 906486688，將會有專人為您服務。</td></tr>
        <tr><td></br></td></tr>
        <tr><td>iCarry官方網站：https://icarry.me</td></tr>
        <tr><td>公司名稱：直流電通股份有限公司</td></tr>
        <tr><td>客服電話：+886 906486688</td></tr>
    </table>
    <div align="center">Copyright © {{ date('Y') }} icarry.me直流電通股份有限公司｜台北市中山區南京東路三段103號11樓之1</div>
    <br />
    <div align="center"><img src="https://api.icarry.me/image/logo_test.png" style="width:200px;"></div>
</body>
