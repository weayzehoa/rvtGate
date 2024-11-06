<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
</head>
<style>
    body {
        font-family: 'TaipeiSansTCBeta-Regular';
    }
    .css_table {
        display: table;
    }
    .css_tr {
        display: table-row;
    }
    .css_td {
        display: table-cell;
        word-wrap: break-word;
        word-break: break-all;
        overflow: hidden;
        padding: 5px;
    }
    .wrap{
        word-wrap: break-word;
        word-break: break-all;
    }
    .page-break {
        page-break-after: always;
    }
    .text-left {
        text-align: left !important;
    }
    .text-right {
        text-align: right !important;
    }
    .text-center {
        text-align: center !important;
    }
    .bg {
        background-color: #d4d4d4 !important;
    }
    .align-top {
        vertical-align: top !important;
    }

    .align-middle {
        vertical-align: middle !important;
    }

    .align-bottom {
        vertical-align: bottom !important;
    }

    .f24{
        font-size: 24px;
    }
    .f20{
        font-size: 20px;
    }
    .f16{
        font-size: 16px;
    }
    .w100{
        width:100%;
    }
    .w50{
        width:50%;
    }
    .boarder{
        border:2px #000000 solid;
    }
</style>

<body>
    @if(isset($orders))
    @foreach($orders as $order)
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="align-middle text-left f20 w50">訂單編號：{{  $order->order_number }}</td>
            <td class="align-middle text-right f20 w50">iCarry 我來寄</td>
        </tr>
        <tr>
            <td class="align-middle f20">收件人：{{  $order->receiver_name }}</td>
        </tr>
        <tr>
            <td class="align-middle f20">訂購人：{{  $order->buyer_name }}</td>
        </tr>
        <tr>
            <td class="align-middle text-left f20 w50">訂單日期：{{ substr($order->created_at,0,10) }}</span></div>
            <td class="align-middle text-right f20 w50">物流：{{ $order->shippingMethod->name }}</span></div>
        </div>
        @if($order->shippingMethod->name == "旅店提貨 " || $order->shippingMethod->name == "機場提貨 ")
        <tr>
            <td class="align-middle f20">提貨時間：{{ substr($order->receiver_key_time) }}</td>
            <td class="align-middle f20">提貨地址：{{ $order->receiver_address }}</td>
        </tr>
        @endif
    </table>
    <p>　</p>
    <table width="100%" cellpadding="2" cellspacing="0" border="1">
        <tr>
            <td class="bg align-middle text-left f20" style="width:15%">貨號</td>
            <td class="bg align-middle text-left f20" style="width:20%">商家</td>
            <td class="bg align-middle text-left f20" style="width:38%">品名</td>
            <td class="bg align-middle text-center f20" style="width:7%">單位</td>
            <td class="bg align-middle text-center f20" style="width:7%">數量</td>
            <td class="bg align-middle text-center f20" style="width:7%">撿貨</td>
            <td class="bg align-middle text-center f20" style="width:7%">確認</td>
        </tr>
        @foreach($order->items as $item)
        <tr>
            <td class="align-middle text-left f16" style="width:15%">
                {{ $item->sku }}
            </td>
            <td class="align-middle text-left f16" style="width:20%">
                <div class="wrap" style="width:95%">{{ $item->vendor_name }}</div>
            </td>
            <td class="align-middle text-left f16" style="width:38%">
                <div class="wrap" style="width:95%">{{ $item->product_name }}</div>
            </td>
            <td class="align-middle text-center f16" style="width:7%">{{ $item->unit_name }}</td>
            <td class="align-middle text-center f16" style="width:7%">{{ $item->quantity }}</td>
            <td class="align-middle text-center f16" style="width:7%"></td>
            <td class="align-middle text-center f16" style="width:7%"></td>
        </tr>
        @endforeach
    </table>
    <p>　</p>
    <p>　</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="align-middle text-left f24 w50" style="height:100px">撿貨人員：</td>
            <td class="align-middle text-left f24 w50" style="height:100px">包裝人員：</td>
        </tr>
    </table>
    <p>　</p>
    <p>　</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="align-middle text-left f20">訂單備註：{{ $order->user_memo }}</td>
        </tr>
    </table>
    @if(count($orders) > 1 )
    @if(count($orders) != $loop->iteration )
    <div class="page-break"></div>
    @endif
    @endif
    @endforeach
    @endif
</body>

</html>

