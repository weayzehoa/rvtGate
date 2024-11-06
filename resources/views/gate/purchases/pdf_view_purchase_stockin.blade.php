<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    {{-- Theme style --}}
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
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
    .a td {
        text-align: left;
    }

    .b td {
        border-style: solid;
        border-color: black;
    }

    .float-right {
        float: right !important;
    }

    tbody {
        border-style: solid;
        border-color: black;
        font-size: 14px;
    }
</style>

<body>
    {{-- <div>
        <span class="f24">直流電通股份有限公司</span><br>
        <span class="f20">商品入庫管理表</span>
        <div style="height:60px; margin-top: -10px;">
            <div style="float: left;">
                <img src="data:image/png;base64, {!! DNS1D::getBarcodePNG($order->purchase_no, 'C39',1,40,array(1,1,1), true) !!} " alt="barcode" />
            </div>
            <div style="float: right;">
                <p style="text-align: right;"> 日期：{{ date('Y-m-d') }}</p>
            </div>
        </div>
    </div> --}}
    <div>
        <span class="f24">直流電通股份有限公司</span><br>
        <span class="f20">商品入庫管理表</span>
        <p style="text-align: right; margin-top: -10px"> 日期：{{ date('Y-m-d') }}</p>
    </div>
    <div>
        <table width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
            <tbody border="1">
                <tr class="b" border="1">
                    <td width="5%" class="text-left pd3">序號</td>
                    <td width="15%" class="text-left pd3">參考號(國際條碼)</td>
                    <td width="10%" class="text-left pd3">廠商</td>
                    <td width="25%" class="text-left pd3">品名</td>
                    <td width="15%" class="text-left pd3">規格</td>
                    <td width="8%" class="text-right pd3">預收數量</td>
                    <td width="12%" class="text-center pd3">有效日期</td>
                    <td width="10%" class="text-center pd3">指定到貨</td>
                </tr>
                @foreach($items as $item)
                @if($item->direct_shipment == 0)
                <tr class="b" border="1">
                    <td class="pd3 text-left">{{ $item->snoForStockin }}</td>
                    <td class="pd3 text-left">{{ !empty($item->gtin13) ? $item->gtin13 : $item->sku }}</td>
                    <td class="pd3 text-left" style="word-wrap: break-word;">{{ $item->vendor_name }}</td>
                    <td class="pd3 text-left" style="word-wrap: break-word;">{{ $item->product_name }}</td>
                    <td class="pd3 text-left" style="word-wrap: break-word;">{{ $item->serving_size }}</td>
                    <td class="pd3 text-right">{{ $item->purchase_quantity }}</td>
                    <td class="pd3 text-center"></td>
                    <td class="pd3 text-center">{{ str_replace('-','/',$item->vendor_arrival_date) }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="text-align: left;">
        @for($i = 0; $i < count($pNos); $i++)
        <div style="text-align: left; margin-top: 30px; margin-right: 40px ; display:inline-block;">
            <img src="data:image/png;base64, {!! DNS1D::getBarcodePNG($pNos[$i], 'C39',1,40,array(1,1,1), true) !!} " alt="barcode" />
        </div>
        @endfor
    </div>
</body>

</html>

