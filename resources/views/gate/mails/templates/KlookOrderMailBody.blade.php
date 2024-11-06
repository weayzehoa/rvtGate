<div>Dear customer,<br />
    Your order from KLOOK 【{{ $details['order']['partner_order_number'] }}】 has been shipped.<br />
    Your parcel&rsquo;s tracking number is 【{{ $details['order']['shippingData'] }}】.</div>

    <ul>
        <li>Below are the pickup locations for Airport Pickups. Please provide your parcel's number at the counter to collect parcel:
        <ul>
            <li>Taoyuan International Airport Terminal 1 (T1) Pelican Counter: 1st floor Departure Hall, next to check-in counter 12. (Service hours: 24hrs)</li>
            <li>Taoyuan International Airport Terminal 2 (T2) Pelican Counter: 3rd floor Departure Hall, next to check-in counter 19. (Service hours: 06:00-23:00)</li>
            <li>Taipei Songshan Airport Pelican Counter: 1st floor Arrival Hall.<br>(Service hours: 05:00-22:00)</li>
        </ul>
        </li>
        <li>For Hotel Pickups, please be sure to notify your hotel&rsquo;s front desk to receive the parcel on your behalf to avoid any refusals.</li>
        <li>For home delivery orders, please pay attention for any incoming calls from logistics to confirm delivery<br>arrangements.</li>
    </ul>

    <div>※ This email is only to notify that the order has been shipped, and does not mean that the order has been delivered or completed.<br />
    ※ This is an automated email sent by system, please do not reply.<br />
    ※ For any further inquiries, please email us at icarry@icarry.me, or contact us via phone +886 906486688.<br />
    <br />
    親愛的客戶您好，<br />
    您於 KLOOK 的訂單【{{ $details['order']['partner_order_number'] }}】已經出貨。<br />
    您訂單的包裹單號為【{{ $details['order']['shippingData'] }}】。</div>

    <ul>
        <li>機場提貨訂單，相關取貨地點如下。請提供櫃檯人員包裹單號已領取您的包裹：
        <ul>
            <li>桃園機場第一航廈-台灣宅配通櫃檯：位於 1 樓出境大廳 - 近 12 號報到櫃檯（服務時間：24小時）</li>
            <li>桃園機場第二航廈-台灣宅配通櫃檯：位於 3 樓出境大廳 - 近 19 號報到櫃檯（服務時間：06:00-23:00）</li>
            <li>松山機場第一航廈-台灣宅配通櫃檯：位於 1 樓入境大廳內（服務時間：05:00-22:00）</li>
        </ul>
        </li>
        <li>旅店提貨訂單，請您務必通知旅店櫃檯代收包裹以免發生拒收包裹狀況。</li>
        <li>一般宅配訂單，請您留意物流人員來電以利接收包裹。</li>
    </ul>

    <div>※ 本通知函為已出貨之通知，並不代表訂單已配達或完成。<br />
    ※ 此信件為系統發出信件，請勿直接回覆。<br />
    ※ 若有任何問題請發送郵件至 icarry@icarry.me，或撥打+886 906486688，將會有專人為您服務。<br />
    <br />
    Copyright &copy; {{ date('Y') }} icarry.me直流電通股份有限公司｜台北市中山區南京東路三段103號11樓之1</div>

    <p><img src="https://api.icarry.me/image/logo_test.png" style="width:200px" /></p>
