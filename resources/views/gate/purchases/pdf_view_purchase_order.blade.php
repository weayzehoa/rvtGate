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
        text-align: center;
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
    .f18{
        font-size: 18px;
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
    .pd3 {
        padding:3px;
    }
    .pd10 {
        padding:10px;
    }
    .a td {
        text-align: left;
    }

    .b td {
        border-style: solid;
        border-color: black;
    }

    tbody {
        border-style: solid;
        border-color: black;
        font-size: 14px;
    }
</style>

<body>
    @if(count($orders) > 0)
    @foreach($orders as $order)
    <span class="f24">直流電通股份有限公司</span><br>
    <span class="f20">訂單採購單</span><br><br>
    <table width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
        <tbody border="1">
            <tr>
                <td width="18%" class="text-left pd3" colspan="2">
                    <div style="color:red">
                    </div>
                    <div>
                        採購單號:{{ $order->purchase_no }}
                    </div>
                    <div>
                        單據日期:{{ date('Y-m-d') }}
                    </div>
                </td>
                <td width="29%" class="text-left pd3">
                    <div>
                        廠商代號:{{ 'A'.str_pad($order->vendor_id,5,'0',STR_PAD_LEFT).' '.$order->vendor_name }}
                    </div>
                    <div>
                        廠商全名:{{ $order->company }}
                    </div>
                    <div style="word-wrap: break-word;">
                        廠商地址:{{ $order->address }}
                    </div>
                </td>
                <td width="20%" class="text-left pd3" colspan="2">
                    <div>
                        聯 絡 人: {{ $order->contact_person }}
                    </div>
                    <div>
                        廠商電話: {{ $order->tel }}
                    </div>
                    <div>
                        廠商傳真: {{ $order->fax }}
                    </div>
                </td>
                <td width="33%" class="text-left pd3" colspan="5">
                    <div>
                        交易幣別:NTD
                    </div>
                    <div>
                        課 稅 別:{{ $order->taxType }}
                    </div>
                    <div>
                        營業稅率:{{ $order->taxType == '不計稅' ? '0%' : '5%' }}
                    </div>
                    <div>
                        付款條件:{{ $order->payCondition }}
                    </div>
                </td>
            </tr>
            <tr class="b" border="1">
                <td width="5%" class="align-middle text-left pd3">序號</td>
                <td width="15%" class="align-middle text-left pd3">iCarry品號<br>廠商料號</td>
                <td width="25%" class="align-middle text-left pd3">品名</td>
                <td width="15%" class="align-middle text-left pd3">規格</td>
                <td width="5%" class="align-middle text-right pd3">採購<br>數量</td>
                <td width="5%" class="align-middle text-center pd3">單位</td>
                <td width="5%" class="align-middle text-center pd3">交貨<br>庫別</td>
                <td width="10%" class="align-middle text-right pd3">採購單價<br>採購金額</td>
                <td width="10%" class="align-middle text-center pd3">指定到貨</td>
                <td width="5%" class="align-middle text-left pd3">備註</td>
            </tr>
            @foreach($order->exportItems as $item)
            @if($item->quantity > 0)
            <tr class="b" border="1">
                <td class="align-middle pd3 text-left">{{ $item->sno }}</td>
                <td class="align-middle pd3 text-left">{{ $item->digiwin_no }}<br>{{ $item->vendor_product_model_id }}</td>
                <td class="align-middle pd3 text-left" style="word-wrap: break-word;">{{ $item->product_name }}</td>
                <td class="align-middle pd3 text-left" style="word-wrap: break-word;">{{ $item->serving_size }}</td>
                <td class="align-middle pd3 text-right">{{ $item->quantity }}</td>
                <td class="align-middle pd3 text-center">{{ $item->unit_name }}</td>
                <td class="align-middle pd3 text-center">{{ $item->direct_shipment == 1 ? 'W02' : 'W01' }}</td>
                <td class="align-middle pd3 text-right">{{ round($item->purchase_price,2) }}<br>{{ round($item->purchase_price * $item->quantity,0) }}</td>
                <td class="align-middle pd3 text-center">{{ str_replace('-','/',$item->vendor_arrival_date) }}</td>
                <td class="align-middle pd3 text-left"></td>
            </tr>
            @endif
            @endforeach
            <tr class="b" border="1">
                <td colspan="10" class="text-center">
                    <span class="pd10">數量合計:{{ $order->totalQty }}　　　　</span><span class="pd10">採購金額：{{ round($order->purchasePrice,0) }} 　　　　</span><span class="pd10">稅額：{{ $order->taxType == '不計稅' ? 0 : round($order->tax,0) }} 　　　　<span class="pd10">金額合計：{{ $order->taxType == '不計稅' ? round($order->amount,0) : round($order->amount + $order->tax,0) }}</span>
                </td>
            </tr>
        </tbody>
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

