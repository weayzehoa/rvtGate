<style>
    body{
        font-family: Microsoft JhengHei,arial,sans-serif !important;
    }
</style>
<body>
    @if($details['order']['shipping_method'] != 1)
        <table align="center" style="width:820px; border:1px #000000 solid;">
            <tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">訂單通知</td></tr>
            <tr><td>親愛的 顧客 您好：</td></tr>
            <tr><td></br></td></tr>
            <tr><td>您的訂單【{{ $details['order']['order_number'] }}】已經出貨，您可以透過<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢物流狀態。</td></tr>
            <tr><td>提醒您！</td></tr>
            <tr><td>本通知函為已出貨之通知，並不代表訂單已配達或完成。</td></tr>
        </table>
        <br />
        <table align="center" style="width:820px; border:1px #000000 solid;">
            <tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">訂單資訊</td></tr>
            <tr><td>訂單編號：【{{ $details['order']['order_number'] }}】</td></tr>
            <tr><td>訂購日期：【{{ $details['order']['create_time'] }}】</td></tr>
            <tr><td>付款日期：【{{ $details['order']['pay_time'] }}】</td></tr>
            <tr><td>訂單明細：請於<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢。</td></tr>
        </table>

        @if($details['order']['create_type'] == 'ASIAMILES')
        <br />
		<table align="center" style="width:820px; border:1px #000000 solid;">
			<tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">Asiamiles 訂購注意事項</td></tr>
			<tr><td>感謝您使用 Asiamiles 里數訂購商品，提供給您商品購買憑証如下：<a target="_blank" href="https://icarry.me/asiamiles-print.php?o={{ $details['order']['am_md5'] }}">https://icarry.me/asiamiles-print.php?o={{ $details['order']['am_md5'] }}</a></td></tr>
			<tr><td></td></tr>
			<tr><td>若有任何問題，請不吝聯絡我們，您可以於本信件最下方找到聯絡方式，感謝您的訂購。</td></tr>
		</table>
        @endif

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

    @else
        <table align="center" style="width:820px; border:1px #000000 solid;">
            <tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">訂單通知</td></tr>
            <tr><td>親愛的 顧客 您好：</td></tr>
            <tr><td></br></td></tr>
            @if($details['order']['receiver_address'] == '桃園機場/第一航廈出境大廳門口')
            <tr><td>您的訂購的【訂單編號/{{ $details['order']['order_number'] }}】已經出貨，可在【{{ str_replace("-","/",substr($details['order']['receiver_key_time'],5,5)) }}】於【第一航廈-台灣宅配通櫃檯：位於 1 樓出境大廳（近 12 號報到櫃檯）】,</td></tr>
            @elseif($details['order']['receiver_address'] == '桃園機場/第二航廈出境大廳門口')
            <tr><td>您的訂購的【訂單編號/{{ $details['order']['order_number'] }}】已經出貨，可在【{{ str_replace("-","/",substr($details['order']['receiver_key_time'],5,5)) }}】於【第二航廈-台灣宅配通櫃檯：位於 3 樓出境大廳（近 19 號報到櫃檯）】,</td></tr>
            @elseif($details['order']['receiver_address'] == '松山機場/第一航廈台灣宅配通（E門旁）')
            <tr><td>您的訂購的【訂單編號/{{ $details['order']['order_number'] }}】已經出貨，可在【{{ str_replace("-","/",substr($details['order']['receiver_key_time'],5,5)) }}】於【第一航廈-台灣宅配通櫃檯：位於 1 樓入境大廳內】,</td></tr>
            @elseif($details['order']['receiver_address'] == '花蓮航空站/挪亞方舟旅遊')
            <tr><td>您的訂購的【訂單編號/{{ $details['order']['order_number'] }}】已經出貨，可在【{{ str_replace("-","/",substr($details['order']['receiver_key_time'],5,5)) }}】於【諾亞方舟旅遊位於 1 樓國際線入境大廳出口處】,</td></tr>
            @endif
            <tr><td>您可以透過<a href="https://support.icarry.me/zh-tw/">常見問答</a>Q21查看詳細位置。</td></tr>
            <tr><td></br></td></tr>
            @if($details['order']['receiver_address'] == '桃園機場/第一航廈出境大廳門口')
            <tr><td>提貨地點:第一航廈-台灣宅配通櫃檯：位於 1 樓出境大廳（近 12 號報到櫃檯）</td></tr>
            @elseif($details['order']['receiver_address'] == '桃園機場/第二航廈出境大廳門口')
            <tr><td>提貨地點:第二航廈-台灣宅配通櫃檯：位於 3 樓出境大廳（近 19 號報到櫃檯）</td></tr>
            @elseif($details['order']['receiver_address'] == '松山機場/第一航廈台灣宅配通（E門旁）')
            <tr><td>提貨地點:第一航廈-台灣宅配通櫃檯：位於 1 樓入境大廳內</td></tr>
            @elseif($details['order']['receiver_address'] == '花蓮航空站/挪亞方舟旅遊')
            <tr><td>提貨地點:諾亞方舟旅遊位於 1 樓國際線入境大廳出口處</td></tr>
            @endif
            <tr><td>提貨時間:{{ $details['order']['receiver_key_time'] }}</td></tr>
            <tr><td>商品取貨號:{{ $details['order']['shipping_number'] }}</td></tr>
            <tr><td>取件人:{{ $details['order']['receiver_name'] }}</td></tr>
        </table>
        <br />
        <table align="center" style="width:820px; border:1px #000000 solid;">
            <tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">訂單資訊</td></tr>
            <tr><td>訂單編號：【{{ $details['order']['order_number'] }}】</td></tr>
            <tr><td>訂購日期：【{{ $details['order']['create_time'] }}】</td></tr>
            <tr><td>付款日期：【{{ $details['order']['pay_time'] }}】</td></tr>
            <tr><td>訂單明細：請於<a href="https://icarry.me/">iCarry官方網站</a>「會員中心」-「歷史訂單」中作查詢。</td></tr>
        </table>

        @if($details['order']['create_type'] == 'ASIAMILES')
        <br />
		<table align="center" style="width:820px; border:1px #000000 solid;">
			<tr style="background-color:#DDDDDD;"  ><td align="center" style="border:1px #000000 solid;">Asiamiles 訂購注意事項</td></tr>
			<tr><td>感謝您使用 Asiamiles 里數訂購商品，提供給您商品購買憑証如下：<a target="_blank" href="https://icarry.me/asiamiles-print.php?o={{ $details['order']['am_md5'] }}">https://icarry.me/asiamiles-print.php?o={{ $details['order']['am_md5'] }}</a></td></tr>
			<tr><td></td></tr>
			<tr><td>若有任何問題，請不吝聯絡我們，您可以於本信件最下方找到聯絡方式，感謝您的訂購。</td></tr>
		</table>
        @endif

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

    @endif
</body>
