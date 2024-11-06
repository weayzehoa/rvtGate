<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    {{-- Theme style --}}
    <link rel="stylesheet" href="./adminlte.min.css">
    {{-- Font Awesome Icons --}}
    <link rel="stylesheet" href="{{ asset('vendor/Font-Awesome/css/all.min.css') }}">
    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/admin.custom.css') }}">
</head>
<style>
    body {
        font-family: 'TaipeiSansTCBeta-Regular';
    }
    .text-danger {
        color: #dc3545 !important;
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
    .f14{
        font-size: 14px;
    }
    .f12{
        font-size: 12px;
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
    .text-primary {
        color: #007bff !important;
    }
    .text-danger {
        color: #dc3545 !important;
    }
    .align-top {
        vertical-align: top !important;
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
    .text-bold, .text-bold.table td, .text-bold.table th {
        font-weight: 700;
    }
    .badge {
        display: inline-block;
        padding: 0.25em 0.4em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .badge-purple {
        color: #ffffff;
        background-color: #6f42c1;
    }
    .badge-primary {
        color: #ffffff;
        background-color: #007bff;
    }
    .badge-secondary {
        color: #ffffff;
        background-color: #6c757d;
    }
    .badge-success {
        color: #ffffff;
        background-color: #28a745;
    }
    .badge-info {
        color: #ffffff;
        background-color: #17a2b8;
    }
    .badge-warning {
        color: #1F2D3D;
        background-color: #ffc107;
    }

    .badge-danger {
        color: #ffffff;
        background-color: #dc3545;
    }
    .badge-light {
        color: #1F2D3D;
        background-color: #f8f9fa;
    }
    .badge-dark {
        color: #ffffff;
        background-color: #343a40;
    }
</style>

<body>
    @if(!empty($orders))
        @foreach ($orders as $order)
        <div class="">
            <span class="f20 text-primary">購買人資訊：</span> <span class="f16"><b>ID: {{ $order->user_id }} {{ isset($order->user->name) ? $order->user->name ? '( '.$order->user->name.' ) ｜' : '' : '｜'}} {{ isset($order->user->mobile) ? $order->user->mobile ? '電話：'.$order->user->mobile : '' : ''}}</b></span>
            @if($order->user_memo)
            <span class="f16 text-danger">　註：{!! $order->user_memo !!}</span>
            @endif
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="1">
            <thead>
                <tr>
                    <th class="text-left" width="25%">訂單資訊 / 訂單狀態 / 物流及金流</th>
                    <th class="text-left" width="75%">購買品項<br></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="align-top" width="25%">
                        <span class="f16 text-primary">{{ $order->order_number }}</span>
                        @if($order->book_shipping_date)
                        <span class="badge badge-purple">{{ $order->book_shipping_date ? '預：'.$order->book_shipping_date : '預定出貨日' }}</span>
                        @endif
                        <hr>
                        @if($order->created_at)
                        <span class="f12">建單：{{ $order->created_at }}</span><br>
                        @endif
                        @if($order->pay_time)
                        <span class="f12">付款：{{ $order->pay_time }}</span><br>
                        @endif
                        @if($order->is_invoice == 1)
                        <span class="f12">發票：{{ $order->invoice_time ?? '' }}</span><br>
                        @endif
                        @if($order->is_invoice_no)
                        <span class="f12">發票號碼：{{ $order->is_invoice_no ?? '' }}</span><br>
                        @endif
                        <span class="text-danger text-bold">
                        @if($order->deleted_at)
                            前台使用者刪除訂單
                        @else
                            @if($order->status == -1)
                            後台取消訂單
                            @elseif($order->status == 0)
                            訂單成立，等待付款
                            @elseif($order->status == 1)
                            訂單付款，等待出貨
                            @elseif($order->status == 2)
                            訂單集貨中
                            @elseif($order->status == 3)
                            訂單已出貨
                            @elseif($order->status == 4)
                            訂單已完成
                            @endif
                        @endif
                        </span>
                        @if($order->deleted_at == '')
                        <span class="text-primary">{{ $order->admin_memo ? '('.$order->admin_memo.')' : '' }}</span>
                        @endif
                        <hr>
                        物流：<span class="badge badge-primary">
                            @if($order->ship_to)
                            @if($order->origin_country == $order->ship_to)
                            {{ $order->origin_country }}寄送當地
                            @else
                            {{ $order->origin_country }}寄送{{ $order->ship_to }}
                            @endif
                            @else
                            {{ $order->pay_method }}
                            @endif
                            </span>
                        @if($order->receiver_keyword)
                        <span class="badge badge-primary">航班/旅店：{{ $order->receiver_keyword }}</span><br>
                        @endif
                        @if($order->receiver_key_time)
                        <span class="badge badge-warning">{{ $order->receiver_key_time ? '提貨日：'.str_replace('-','/',substr($order->receiver_key_time,0,16)) : '提貨日註記'}}</span>
                        @endif
                        <br>
                        @if($order->status != 0)
                        @if($order->pay_method)
                        金流：<span class="badge badge-danger">{{ $order->pay_method }}</span> <span class="badge badge-purple">{{ number_format($order->total_pay) }} 元</span>
                        @endif
                        @endif
                        @if($order->shipping_memo_vendor || $order->new_shipping_memo || $order->buy_memo || $order->billOfLoading_memo || $order->special_memo)<hr>@endif
                        @if($order->shipping_memo_vendor)<span class="badge badge-primary">{{ $order->shipping_memo_vendor ? '物流商：'.$order->shipping_memo_vendor : '' }}</span>@endif
                        @if($order->new_shipping_memo)<span class="badge badge-secondary">{{ $order->new_shipping_memo ? '物流日：'.$order->new_shipping_memo : '' }}</span>@endif
                        @if($order->buy_memo)<span class="badge badge-success">{{ $order->buy_memo ? '採購日：'.$order->buy_memo : '' }}</span>@endif
                        @if($order->billOfLoading_memo)<span class="badge badge-info">{{ $order->billOfLoading_memo ? '提單日：'.$order->billOfLoading_memo : '' }}</span>@endif
                        @if($order->special_memo)<span class="badge badge-warning">{{ $order->special_memo ? '特註：'.$order->special_memo : '' }}</span>@endif
                    </td>
                    <td class="align-top" width="75%">
                        <table width="100%" cellpadding="1" cellspacing="0">
                            <tr>
                                <th width="15%" class="bg f12 text-left" style="border-bottom:1px #000000 solid;">商家</th>
                                <th width="18%" class="bg f12 text-left" style="border-bottom:1px #000000 solid;">貨號</th>
                                <th width="25%" class="bg f12 text-left" style="border-bottom:1px #000000 solid;">品名</th>
                                <th width="6%" class="bg f12 text-center" style="border-bottom:1px #000000 solid;">單位</th>
                                <th width="6%" class="bg f12 text-right" style="border-bottom:1px #000000 solid;">單價</th>
                                <th width="6%" class="bg f12 text-right" style="border-bottom:1px #000000 solid;">重量(g)</th>
                                <th width="6%" class="bg f12 text-right" style="border-bottom:1px #000000 solid;">應稅</th>
                                <th width="6%" class="bg f12 text-right" style="border-bottom:1px #000000 solid;">數量</th>
                                <th width="6%" class="bg f12 text-right" style="border-bottom:1px #000000 solid;">總價</th>
                                <th width="6%" class="bg f12 text-right" style="border-bottom:1px #000000 solid;">總重(g)</th>
                            </tr>
                            @foreach($order->items as $item)
                            <tr>
                                <td class="f12 text-left wrap" style="border-bottom:1px #000000 solid;">{{ $item->vendor_name }}</td>
                                <td class="f12 text-left" style="border-bottom:1px #000000 solid;">{{ $item->sku }}</td>
                                <td class="f12 text-left wrap" style="border-bottom:1px #000000 solid;">{{ $item->product_name }}</td>
                                <td class="f12 text-center" style="border-bottom:1px #000000 solid;">{{ $item->unit_name }}</td>
                                <td class="f12 text-right" style="border-bottom:1px #000000 solid;">{{ number_format($item->price) }}</td>
                                <td class="f12 text-right" style="border-bottom:1px #000000 solid;">{{ number_format($item->gross_weight) }}</td>
                                <td class="f12 text-right" style="border-bottom:1px #000000 solid;">{{ $item->is_tax_free }}</td>
                                <td class="f12 text-right" style="border-bottom:1px #000000 solid;">{{ number_format($item->quantity) }}</td>
                                <td class="f12 text-right" style="border-bottom:1px #000000 solid;">{{ number_format($item->price * $item->quantity) }}</td>
                                <td class="f12 text-right" style="border-bottom:1px #000000 solid;">{{ number_format($item->gross_weight * $item->quantity) }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="4" class="f12 text-left">運費 {{ number_format($order->shipping_fee) }}　　使用購物金 {{ number_format($order->spend_point) }}　　跨境稅 {{ $order->parcel_tax ?? 0 }}　　折扣 {{ number_format($order->discount) }}</td>
                                <td colspan="3" class="f12 text-right">商品總計</td>
                                <td class="f12 text-right">{{ number_format($order->totalQty) }}</td>
                                <td class="f12 text-right">{{ number_format($order->totalPrice) }}</td>
                                <td class="f12 text-right">{{ number_format($order->totalWeight) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        <span class="f20 text-primary">收件人資訊： </span>
        <span class="f14">
            @if($order->receiver_name) 收件人：{{ $order->receiver_name }} | @endif
            @if($order->receiver_phone_number) 行動電話：{{ $order->receiver_phone_number }} | @endif
            @if($order->receiver_tel) 電話：{{ $order->receiver_tel }} | @endif
            @if($order->receiver_email) E-Mail：{{ $order->receiver_email }} | @endif
            @if($order->receiver_keyword) 班機號碼：{{ $order->receiver_keyword }} | @endif
            @if($order->receiver_key_time) 提貨時間：{{ $order->receiver_key_time }} | @endif
            @if($order->receiver_address) 地址：{{ $order->receiver_address }} | @endif
            @if($order->receiver_id_card) 收件人中國身分證：{{ $order->receiver_id_card }} | @endif
            @if($order->buyer_id_card) 訂購人中國身分證：{{ $order->buyer_id_card }} | @endif
            @if($order->shipping_number) 物流單號：{{ $order->shipping_number }} | @endif
        </span>
        @if(count($orders) > 1 )
        @if(count($orders) != $loop->iteration )
        <div class="page-break"></div>
        @endif
        @endif
        @endforeach
    @else
    <h3>無資料</h3>
    @endif
</body>

</html>

