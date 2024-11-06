@extends('gate.layouts.master')

@section('title', '出貨單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>出貨單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('purchases') }}">出貨單管理</a></li>
                        <li class="breadcrumb-item active">清單</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-5">
                                    <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                    <button class="btn btn-sm btn-primary mr-2 sellImport" value="warehouse">倉庫出庫單匯入</button>
                                    <button class="btn btn-sm btn-primary mr-2 sellImport" value="directShip">廠商直寄單匯入</button>
                                </div>
                                <div class="col-7">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($sells) ? number_format($sells->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left">
                                        <span clas="d-flex align-items-center">查詢條件：</span>
                                        @if(!empty($created_at) || !empty($created_at_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('created_at')">X </span>
                                            採購時間區間：
                                            @if(!empty($created_at)){{ $created_at.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($created_at_end)){{ '至 '.$created_at_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($vendor_arrival_date) || !empty($vendor_arrival_date_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_arrival_date')">X </span>
                                            廠商入庫日區間：
                                            @if(!empty($vendor_arrival_date)){{ $vendor_arrival_date.' ' }}@else{{ '2022-01-01 00:00:00' }}@endif
                                            @if(!empty($vendor_arrival_date_end)){{ '至 '.$vendor_arrival_date_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if((!empty($book_shipping_date) || !empty($book_shipping_date_end)))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('book_shipping_date')">X </span>
                                            預定出貨日區間：
                                            @if(!empty($book_shipping_date)){{ $book_shipping_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($book_shipping_date_end)){{ '至 '.$book_shipping_date_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if((!empty($sell_date) || !empty($sell_date_end)))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('sell_date')">X </span>
                                            出貨日期區間：
                                            @if(!empty($sell_date)){{ $sell_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($sell_date_end)){{ '至 '.$sell_date_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($pay_method) && $pay_method != '全部')<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('pay_method')">X</span> 付款方式：{{ $pay_method }}</span>@endif
                                        @if(!empty($erp_stockin_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_stockin_no')">X</span> 鼎新入庫單號：{{ $erp_stockin_no }}</span>@endif
                                        @if(!empty($express_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('express_no')">X</span> 物流單號：{{ $express_no }}</span>@endif
                                        @if(!empty($express_way))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('express_way')">X</span> 物流商：{{ $express_way }}</span>@endif
                                        @if(!empty($product_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('product_name')">X</span> 商品名稱：{{ $product_name }}</span>@endif
                                        @if(!empty($sku))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('sku')">X</span> iCarry品號：{{ $sku }}</span>@endif
                                        @if(!empty($digiwin_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('digiwin_no')">X</span> 鼎新品號：{{ $digiwin_no }}</span>@endif
                                        @if(!empty($erp_purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_purchase_no')">X</span> 鼎新採購單號：{{ $erp_purchase_no }}</span>@endif
                                        @if(!empty($order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('order_number')">X</span> iCarry訂單單號：{{ $order_number }}</span>@endif
                                        @if(!empty($erp_order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_order_number')">X</span> ERP訂單單號：{{ $erp_order_number }}</span>@endif
                                        @if(!empty($sell_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('purchase_no')">X</span> 出貨單單號：{{ $sell_no }}</span>@endif
                                        @if(!empty($erp_sell_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_sell_no')">X</span> ERP出貨單單號：{{ $erp_sell_no }}</span>@endif
                                        @if(!empty($vendor_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_name')">X</span> 商家名稱：{{ $vendor_name }}</span>@endif
                                        @if(!empty($is_invoice))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_invoice')">X</span> 是否有發票：{{ isset($is_invoice) && $is_invoice == '' ? '不拘' : ($is_invoice == 1 ? '有' : '否') }}</span>@endif
                                        @if($list)<span class="badge badge-info mr-1">每頁：{{ $list }} 筆</span>@endif
                                    </div>
                                    {{-- <div class="col-4 float-right">
                                        <div class="float-right d-flex align-items-center">
                                                <div class="icheck-primary d-inline mr-2">
                                                    <input type="radio" id="selectorder" name="multiProcess" value="selected">
                                                    <label for="selectorder">自行勾選 <span id="chkallbox_text"></span></label>
                                                </div>
                                                <div class="icheck-primary d-inline mr-2">
                                                    <input type="radio" id="chkallbox" name="multiProcess" value="allOnPage">
                                                    <label for="chkallbox">目前頁面全選</label>
                                                </div>
                                                <div class="icheck-primary d-inline mr-2">
                                                    <input type="radio" id="queryorder" name="multiProcess" value="byQuery">
                                                    <label for="queryorder">依查詢條件</label>
                                                </div>
                                            <button class="btn btn-sm btn-info" id="multiProcess" disabled><span>多筆處理</span></button>
                                        </div>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="search" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('sell') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label for="sell_no">出貨單編號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="sell_no" name="sell_no" placeholder="出貨單單號" value="{{ isset($sell_no) && $sell_no ? $sell_no : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="erp_sell_no">ERP出貨單號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="erp_sell_no" name="erp_sell_no" placeholder="ERP出貨單編號" value="{{ isset($erp_sell_no) && $erp_sell_no ? $erp_sell_no : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="order_number">iCarry訂單編號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單單號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="erp_order_number">ERP訂單單號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="erp_order_number" name="erp_order_number" placeholder="ERP訂單編號" value="{{ isset($erp_order_number) && $erp_order_number ? $erp_order_number : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="sku">iCarry貨號:</label>
                                                        <input type="text" class="form-control" id="sku" name="sku" placeholder="填寫iCarry貨號: ECXXXXXXX" value="{{ isset($sku) ? $sku ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="digiwin_no">鼎新品號:</label>
                                                        <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫鼎新品號: 5TWXXXXXXX" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="vendor_name">商家名稱:</label>
                                                        <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱:海邊走走" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="product_name">商品名稱:</label>
                                                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-12 mt-2">
                                                        <label class="control-label" for="sell_date">銷貨日期區間:</label>
                                                        <div class="input-group">
                                                            <input type="datetime" class="form-control datepicker" id="sell_date" name="sell_date" placeholder="格式：2016-06-06" value="{{ isset($sell_date) ? $sell_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="datetime" class="form-control datepicker" id="sell_date_end" name="sell_date_end" placeholder="格式：2016-06-06" value="{{ isset($sell_date_end) ? $sell_date_end ?? '' : '' }}" autocomplete="off" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label for="pay_method">金流方式: (ctrl+點選可多選)</label>
                                                        <select class="form-control" id="pay_method" size="15" multiple>
                                                            <option value="全部" {{ !empty($pay_method) && $pay_method == '全部' ? 'selected' : 'selected' }}>全部</option>
                                                            @foreach($payMethods as $payMethod)
                                                            @if(!empty($payMethod->name))
                                                            <option value="{{ $payMethod->name }}" {{ isset($pay_method) ? in_array($payMethod->name ,explode(',',$pay_method)) ? 'selected' : '' : '' }}>{{ $payMethod->name }}</option>
                                                            @endif
                                                            @endforeach
                                                        </select><input type="hidden" value="" name="pay_method" id="pay_method_hidden" />
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="row">
                                                            <div class="col-12 mt-2">
                                                                <label for="express_way">物流商:</label>
                                                                <select class="form-control" id="express_way" name="express_way">
                                                                    <option value="" {{ isset($express_way) ? $express_way == '' ? 'selected' : '' : '' }}>全部</option>
                                                                    @foreach($shippingVendors as $shippingVendor)
                                                                    <option value="{{ $shippingVendor->name }}" {{ isset($express_way) ? $express_way == $shippingVendor->name ? 'selected' : '' : '' }}>{{ $shippingVendor->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-12 mt-2">
                                                                <label for="express_no">物流單號:</label>
                                                                <input type="text" class="form-control" id="express_no" name="express_no" placeholder="填寫物流單號" value="{{ isset($express_no) ? $express_no ?? '' : '' }}" autocomplete="off" />
                                                            </div>

                                                            <div class="col-12 mt-2">
                                                                <label for="is_invoice">是否有發票:</label>
                                                                <select class="form-control" id="is_invoice" name="is_invoice">
                                                                    <option value="" {{ isset($is_invoice) ? $is_invoice == '' ? 'selected' : '' : '' }}>不拘</option>

                                                                    <option value="1" {{ isset($is_invoice) ? $is_invoice == '1' ? 'selected' : '' : '' }}>有</option>
                                                                    <option value="X" {{ isset($is_invoice) ? $is_invoice == 'X' ? 'selected' : '' : '' }}>否</option>

                                                                </select>
                                                            </div>
                                                            <div class="col-12 mt-2">
                                                                <label class="control-label" for="list">每頁筆數:</label>
                                                                <select class="form-control" id="list" name="list">
                                                                    <option value="50" {{ $list == 50 ? 'selected' : '' }}>50</option>
                                                                    <option value="100" {{ $list == 100 ? 'selected' : '' }}>100</option>
                                                                    <option value="300" {{ $list == 300 ? 'selected' : '' }}>300</option>
                                                                    <option value="500" {{ $list == 500 ? 'selected' : '' }}>500</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                        <button type="button" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                           @if(!empty($sells))
                           <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="25%">出貨單資訊</th>
                                            <th class="text-left" width="75%">銷貨品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sells as $sell)
                                        <tr style="border-bottom:3px #000000 solid;border-bottom:3px #000000 solid;">
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    {{-- <input type="checkbox" class="chk_box_{{ $sell->id }}" name="chk_box" value="{{ $sell->id }}"> --}}
                                                    <a href="{{ route('gate.sell.show', $sell->id) }}" class="mr-2">
                                                        <span class="text-lg text-bold order_number_{{ $sell->id }}">{{ $sell->sell_no }}</span>
                                                    </a>
                                                    @if(empty($sell->erp_sell_no))
                                                    <button type="button" value="{{ $sell->id }}" class="badge btn-sm btn btn-danger btn-cancel">取消出貨單</button>
                                                    @endif
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <span class="text-sm">鼎　新銷貨單號：{{ $sell->erp_sell_no }}</span><br>
                                                        <span class="text-sm">訂　單　單　號：<a href="{{ route('gate.orders.show',$sell->order_id) }}" target="_blank">{{ $sell->order_number }}</a></span><br>
                                                        <span class="text-sm">鼎　新訂單單號：{{ $sell->erp_order_number }}</span><br>
                                                        <span class="text-sm">銷　貨　日　期：{{ $sell->sell_date }}</span><br>
                                                        @if(!empty($sell->is_invoice_no))
                                                        <span class="text-sm">發　票　號　碼：{{ $sell->is_invoice_no }}</span><br>
                                                        @endif
                                                    </div>
                                                    <div class="col-6 float-left">
                                                        <span class="text-sm">金　額：{{ number_format(round($sell->amount,0)) }}</span><br>
                                                        <span class="text-sm">數　量：{{ $sell->quantity }}</span>
                                                    </div>
                                                    <div class="col-6 float-right">
                                                        <span class="text-sm">稅　金：{{ number_format(round($sell->tax,0)) }}</span><br>
                                                        <span class="text-sm"><span class="text-sm">總金額：{{ number_format(round($sell->amount + $sell->tax,0)) }}</span><br>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-left align-top p-0">
                                                <table class="table table-sm">
                                                    <thead class="table-info">
                                                        <th width="10%" class="text-left align-middle text-sm">鼎新銷貨單號<br>鼎新訂單單號</th>
                                                        <th width="10%" class="text-left align-middle text-sm">物流資訊</th>
                                                        <th width="15%" class="text-left align-middle text-sm">商家</th>
                                                        <th width="10%" class="text-left align-middle text-sm">貨號</th>
                                                        <th width="25%" class="text-left align-middle text-sm">品名</th>
                                                        <th width="5%" class="text-center align-middle text-sm">單位</th>
                                                        <th width="5%" class="text-right align-middle text-sm">出貨量</th>
                                                        <th width="5%" class="text-right align-middle text-sm">出貨價</th>
                                                        <th width="5%" class="text-right align-middle text-sm">金額</th>
                                                    </thead>
                                                    <tbody>
                                                        <form id="itemsform_order_{{ $sell->id }}" method="POST">
                                                            @foreach($sell->items as $item)
                                                            <tr>
                                                                <td class="text-left align-middle text-sm">{{ $item->erp_sell_no.'-'.$item->erp_sell_sno }}<br>{{ $item->erp_order_no.'-'.$item->erp_order_sno }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->express_way }}<br>{{ $item->express_no }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->vendor_name }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->digiwin_no }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->product_name ?? $item->memo }}</td>
                                                                <td class="text-center align-middle text-sm">{{ $item->unit_name ?? '個' }}</td>
                                                                <td class="text-right align-middle text-sm">{{ $item->sell_quantity }}</td>
                                                                <td class="text-right align-middle text-sm">{{ number_format(round($item->sell_price)) }}</td>
                                                                <td class="text-right align-middle text-sm">{{ number_format($item->sell_quantity * $item->sell_price) }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </form>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <h3>無資料</h3>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($sells) ? number_format($sells->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $sells->appends($appends)->render() }}
                                @else
                                {{ $sells->render() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection


@section('modal')
{{-- 匯入Modal --}}
<div id="importModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">請選擇匯入格式</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="importForm" action="{{ url('sell/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" id="filename" name="filename" class="custom-file-input" required autocomplete="off">
                                <label class="custom-file-label" for="filename">瀏覽選擇EXCEL檔案</label>
                            </div>
                            <div class="input-group-append">
                                <button id="importBtn" type="button" class="btn btn-md btn-primary btn-block">上傳</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div>
                    <span class="text-danger" id="importModalNotice">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/入庫報表範本.xls" target="_blank">入庫報表範本</a> ，製作正確的檔案。</span>
                </div>
            </div>
        </div>
    </div>
    <form id="cancelForm" action="{{ url('sell/cancel') }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">
{{-- DataTable --}}
<link rel="stylesheet" href="{{ asset('vendor/datatables/media/css/jquery.dataTables.min.css') }}">
{{-- <link rel="stylesheet" href="{{ asset('vendor/datatables/media/css/dataTables.bootstrap4.min.css') }}"> --}}
<link rel="stylesheet" href="{{ 'https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css' }}">

@section('script')
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
{{-- DataTable --}}
<script src="{{ asset('vendor/datatables/media/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ 'https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js' }}"></script>
{{-- <script src="{{ asset('vendor/datatables/media/js/dataTables.bootstrap4.min.js') }}"></script> --}}

@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('[data-toggle="popover"]').popover({
            html: true,
            sanitize: false,
        });
        // date time picker 設定
        $('.datetimepicker').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });
        $('.datepicker').datepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('.timepicker').timepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('#importBtn').click(function(){
            let form = $('#importForm');
            $('#importBtn').attr('disabled',true);
            form.submit();
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
        });

        var num_all = $('input[name="chk_box"]').length;
        var num = $('input[name="chk_box"]:checked').length;
        $("#chkallbox_text").text("("+num+"/"+num_all+")");

        $('input[name="chk_box"]').change(function(){
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            num > 0 ? $("#selectorder").prop("checked",true) : $("#selectorder").prop("checked",false);
            if(num == 0){
                $('#chkallbox').prop("checked",false);
                $('#multiProcess').prop("disabled",true);
            }else if(num > 0){
                $("#selectorder").prop("checked",true)
                $('#multiProcess').prop("disabled",false);
            }else if(num == num_all){
                $("#chkallbox").prop("checked",true);
                $('#multiProcess').prop("disabled",false);
            }
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('input[name="multiProcess"]').click(function(){
            if($(this).val() == 'allOnPage'){
                $('input[name="chk_box"]').prop("checked",true);
                $('#multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",false);
            }else if($(this).val() == 'selected'){
                $('input[name="chk_box"]').prop("checked",false);
                $('#multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",false);
            }else if($(this).val() == 'byQuery'){
                $('input[name="chk_box"]').prop("checked",false);
                $('#multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",true);
            }else{
                $('#multiProcess').prop("disabled",true);
                $('#oit').prop("disabled",false);
            }
            $('#search').hide();
            $('#showForm').html('使用欄位查詢');
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('.multiProcess').click(function (e){
            let form = $('#multiProcessForm');
            let cate = $(this).val().split('_')[0];
            let type = $(this).val().split('_')[1];
            let filename = $(this).html();
            let condition = null;
            let multiProcess = $('input[name="multiProcess"]:checked').val();
            let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
            if(multiProcess == 'allOnPage' || multiProcess == 'selected'){
                if(cate == 'Export'){
                    let start = $('#vendor_arrival_date').val();
                    let end = $('#vendor_arrival_date_end').val();
                    form.append($('<input type="hidden" class="formappend" name="vendor_arrival_date">').val(start));
                    form.append($('<input type="hidden" class="formappend" name="vendor_arrival_date_end">').val(end));
                }
                if(ids.length > 0){
                    for(let i=0;i<ids.length;i++){
                        form.append($('<input type="hidden" class="formappend" name="id['+i+']">').val(ids[i]));
                    }
                }else{
                    alert('尚未選擇訂單或商品');
                    return;
                }
            }else if(multiProcess == 'byQuery'){ //by條件
                let sel="";
                $("#shipping_method>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#shipping_method_hidden").val(sel.substring(1));

                sel = "";
                $("#status>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#status_hidden").val(sel.substring(1));

                sel = "";
                $("#pay_method>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#pay_method_hidden").val(sel.substring(1));

                sel = "";
                $("#origin_country>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#origin_country_hidden").val(sel.substring(1));
                condition = $('#searchForm').serializeArray();
                let con_val = $('#searchForm').serializeArray().map( item => item.value );
                let con_name = $('#searchForm').serializeArray().map( item => item.name );
                for(let j=0; j<con_name.length;j++){
                    let tmp = '';
                    tmp = $('<input type="hidden" class="formappend" name="con['+con_name[j]+']" value="'+con_val[j]+'">');
                    form.append(tmp);
                }
            }else if(cate == 'SyncToGate'){ //鼎新同步至中繼
                form.submit();
            }else{
                return;
            }
            let export_method = $('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">');
            let export_cate = $('<input type="hidden" class="formappend" name="cate" value="'+cate+'">');
            let export_type = $('<input type="hidden" class="formappend" name="type" value="'+type+'">');
            form.append(export_method);
            form.append(export_cate);
            form.append(export_type);
            form.append( $('<input type="hidden" class="formappend" name="filename" value="'+filename+'">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="purchase">') );
            if(cate == 'Export' || cate == 'Notice'){
                alert('注意! 匯出採購單與通知廠商僅會 匯出/通知 已採購、已完成入庫 狀態，\n尚未採購狀態的採購單，請先同步至鼎新才可使用此功能。')
            }
            form.submit();
            $('.formappend').remove();
            $('#multiModal').modal('hide');
        });

        $('.purchaseProcess').click(function(){
            let form = $('#multiProcessForm');
            form.append( $('<input type="hidden" class="formappend" name="filename" value="商品採購">') );
            $('#multiProcessForm > input[name=type]').val($(this).val());
            if($(this).val() == 'all'){
                form.submit();
            }else{
                $('#multiProcessForm > input[name=orderIds]').remove();
                let selected = $('.purchase_data:checked').serializeArray().map( item => item.value );
                for(let j=0; j<selected.length;j++){
                    let tmp = '';
                    tmp = $('<input type="hidden" class="formappend" name="selected['+j+']" value="'+selected[j]+'">');
                    form.append(tmp);
                }
                form.submit();
            }
            $('.formappend').remove();
        });

        $('#multiProcess').click(function(){
            if($('input[name="multiProcess"]:checked').val() == 'selected'){
                let num = $('input[name="chk_box"]:checked').length;
                if(num == 0){
                    alert('尚未選擇訂單');
                    return;
                }
            }
            $('#multiModal').modal('show');
        });

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#search').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });

        $('.moreOption').click(function(){
            $('#MoreSearch').toggle();
            $(this).html() == '更多選項' ? $(this).html('隱藏更多選項') : $(this).html('更多選項');
        });

        $('.modifybyQuerySend').click(function(){
            $('#mark').submit();
        });

        $('.btn-cancel').click(function (e) {
            let id = $(this).val();
            let form = $('#cancelForm');
            if(confirm('請確認是否要取消這筆出貨單?')){
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
        });

        $('.sellImport').click(function(){
            let type = $(this).val();
            let form = $('#importForm');
            let label = '';
            let notice = '';
            form.append('<input type="hidden" name="type" value="'+type+'">');
            type == 'warehouse' ? label = '請選擇倉庫出庫單檔案' : label = '請選擇廠商直寄檔案';
            type == 'warehouse' ? notice = '注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/倉庫發貨明細範本.xlsx" target="_blank">倉庫發貨明細範本</a> ，製作正確的檔案。' : notice = '注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/廠商直寄報表範本.xlsx" target="_blank">廠商直寄報表範本</a> ，製作正確的檔案。';
            $('#importModalLabel').html(label);
            $('#importModalNotice').html(notice);
            $('#importModal').modal('show');
        });

        $('input[type=file]').change(function(x) {
            let name = this.name;
            let file = x.currentTarget.files;
            let filename = file[0].name; //不檢查檔案直接找出檔名
            if (file.length >= 1) {
                if (filename) {
                    $('label[for=' + name + ']').html(filename);
                } else {
                    $(this).val('');
                    $('label[for=' + name + ']').html('瀏覽選擇EXCEL檔案');
                }
            } else {
                $(this).val('');
                $('label[for=' + name + ']').html('瀏覽選擇EXCEL檔案');
            }
        });

        $('.btn-stockinModify').click(function(){
            let url = window.location.href;
            let form = $('#stockinModifyForm');
            form.append($('<input type="hidden" class="formappend" name="url" value="'+url+'">'));
            form.submit();
        });
    })(jQuery);

    function formSearch(){
        let sel="";
        $("#shipping_method>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#shipping_method_hidden").val(sel.substring(1));

        sel = "";
        $("#status>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#status_hidden").val(sel.substring(1));

        sel = "";
        $("#pay_method>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#pay_method_hidden").val(sel.substring(1));

        sel = "";
        $("#origin_country>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#origin_country_hidden").val(sel.substring(1));

        $("#searchForm input").each(function(){
            if($(this).val() == ''){
                $(this).remove();
            }
        })

        $("#searchForm select").each(function(){
            if($(this).val() == ''){
                $(this).remove();
            }
        })

        $("#searchForm").submit();
    }

    function getLog(purchase_number,purchase_id,column_name,column_value,e,item_id){
        let token = '{{ csrf_token() }}';
        let id = purchase_id;
        let datepicker = '';
        let dateFormat = 'yy-mm-dd';
        let timeFormat = 'HH:mm:ss';
        let html = '';
        let label = '採購單號：'+purchase_number+'，同步紀錄';
        $('#myform').html('');
        $('#record').html('');
        $('#syncRecord').html('');
        $('#myrecord').addClass('d-none');
        $('#syncModal').modal('show');
        $('#myrecord').removeClass('d-none');
        $.ajax({
            type: "post",
            url: 'purchases/getlog',
            data: { id: id, _token: token },
            success: function(data) {
                let record = '';
                if(data.length > 0){
                        let status = data[0]['status'];
                        let purchaseNo = data[0]['purchase_no'];
                        let erpPurchaseNo = data[0]['erp_purchase_no'];
                        let purchaseOrderId = data[0]['purchase_order_id'];
                        for(let i=0; i<data.length; i++){
                            let dateTime = data[i]['synced_time'];
                            let amount = data[i]['amount'];
                            let quantity = data[i]['quantity'];
                            let tax = data[i]['tax'];
                            let noticeTime = data[i]['notice_time'];
                            noticeTime == null ? noticeTime = '' : '';
                            let total = parseFloat(amount) + parseFloat(tax);
                            let record = '<tr><td class="text-center">'+(i+1)+'</td><td class="text-left">'+dateTime+'</td><td class="text-left">'+quantity+'</td><td class="text-right">'+amount+'</td><td class="text-right">'+tax+'</td><td class="text-right">'+total+'</td><td class="text-center">'+noticeTime+'</td></tr>';
                            $('#syncRecord').append(record);
                            $('#purchaseOrderId').val(purchaseOrderId);
                        }
                        label = '採購單編號：'+purchaseNo+'，鼎新採購單編號：'+erpPurchaseNo+'，同步紀錄';
                        status == 3 ? $('#NoticeBtn').hide() : $('#NoticeBtn').show();
                }
                $('#syncModalLabel').html(label);
                $('#syncModal').modal('show');
            }
        });
    }

    function getChange(purchase_number,purchase_id,e)
    {
        let token = '{{ csrf_token() }}';
        let id = purchase_id;
        let datepicker = '';
        let dateFormat = 'yy-mm-dd';
        let timeFormat = 'HH:mm:ss';
        let html = '';
        let label = '採購單號：'+purchase_number+'，退貨紀錄';
        $('#modifyRecord').html('');
        $('#modifyModal').modal('show');
        $.ajax({
            type: "post",
            url: 'purchases/getChangeLog',
            data: { purchase_no: purchase_number, _token: token },
            success: function(data) {
                let record = '';
                if(data.length > 0){
                    let purchaseNo = data[0]['purchase_no'];
                    let erpPurchaseNo = data[0]['erp_purchase_no'];
                    let purchaseOrderId = data[0]['purchase_order_id'];
                    for(let i=0; i<data.length; i++){
                        let dateTime = data[i]['modify_time'];
                        let admin = data[i]['admin_name'] != null ? data[i]['admin_name'] : '';
                        let sku = data[i]['sku'] != null ? data[i]['sku'] : '';
                        let digiwinNo = data[i]['digiwin_no'] != null ? data[i]['digiwin_no'] : '';
                        let productName = data[i]['product_name'] != null ? data[i]['product_name'] : '';
                        let quantity = data[i]['quantity'] != null ? data[i]['quantity'] : '';
                        let price = data[i]['price'] != null ? data[i]['price'] : '';
                        let date = data[i]['date'] != null ? data[i]['date'] : '';
                        let status = data[i]['status'] != null ? data[i]['status'] : '';
                        let memo = data[i]['memo'] != null ? data[i]['memo'] : '' ;
                        let record = '<tr><td class="text-center">'+status+'</td><td class="text-left">'+dateTime+'</td><td class="text-left">'+admin+'</td><td class="text-left">'+sku+'<br>'+digiwinNo+'</td><td class="text-left">'+productName+'</td><td class="text-right">'+price.replace(' => ', '<br>')+'</td><td class="text-right">'+quantity.replace(' => ', '<br>')+'</td><td class="text-left">'+date.replace(' => ', '<br>')+'</td><td class="text-left">'+memo+'</td></tr>';
                        $('#modifyRecord').append(record);
                    }
                    label = '採購單編號：'+purchaseNo+'，鼎新採購單編號：'+erpPurchaseNo+'，修改紀錄';
                }
                $('#modifyModalLabel').html(label);
                $('#modifyModal').modal('show');
            }
        });
    }

    function itemmemo (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let memo = $(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/itemmemo',
                data: { id: id, memo: memo , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        $("#item_memo_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="itemmemo(event,'+id+');">'+memo+'</textarea>');
                        $("#item_memo_"+id).html('<i class="fa fa-info-circle"></i>');
                        $("#item_memo_"+id).popover('hide');
                    }
                }
            });
        }
    }

    function itemQty (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let qty = $(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/qtyModify',
                data: { id: id, type: 'item', qty: qty , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        // if(qty == 0){
                        //     $("#item_qty_"+id).popover('hide');
                        //     $("#item_qty_"+id).remove();
                        //     $(".stockin_item_"+id).html('');
                        //     $(".stockin_item_"+id).attr("data-content",'');
                        //     $(".item_qty_"+id).html('');
                        // }else{
                            $("#item_qty_"+id).popover('hide');
                            $("#item_qty_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="itemQty(event,'+id+');">'+qty+'</textarea>');
                            $("#item_qty_"+id).html('<span class="new_item_qty_'+id+'"></span>');
                            $(".new_item_qty_"+id).html('<u>'+qty+'</u>');
                        // }
                    }
                }
            });
        }
    }

    function packageQty (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let qty = $(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/qtyModify',
                data: { id: id, type: 'package', qty: qty , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        // if(qty == 0){
                        //     $("#package_qty_"+id).popover('hide');
                        //     $("#package_qty_"+id).remove();
                        //     $(".stockin_package_"+id).html('');
                        //     $(".stockin_package_"+id).attr("data-content",'');
                        //     $(".package_qty_"+id).html('');
                        // }else{
                            $("#package_qty_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="packageQty(event,'+id+');">'+qty+'</textarea>');
                            $("#package_qty_"+id).html('<span class="new_package_qty_'+id+'"></span>');
                            $("#package_qty_"+id).popover('hide');
                            $(".new_package_qty_"+id).html('<u>'+qty+'</u>');
                        // }
                    }
                }
            });
        }
    }

    function removeCondition(name){
        if(name == 'sell_date'){
            $('input[name="'+name+'"]').val('');
            $('input[name="'+name+'_end"]').val('');
        }else if(name == 'express_way'){
            $('select[name="'+name+'"]').val('');
        }else if(name == 'is_invoice'){
            $('select[name="'+name+'"]').val('');
        }else if(name == 'pay_method'){
            $('#'+name).empty();
        }else{
            $('input[name="'+name+'"]').val('');
        }
        $("#searchForm").submit();
    }

    function stockinModify(poisId){
        $('#stockinModifyRecord').html('');
        let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/getStockin',
                data: { poisId: poisId, _token: token },
                success: function(data) {
                    if(data){
                        let label = '採購單號：' + data[0]['purchase_no'] + '，鼎新單號：'+ data[0]['erp_purchase_no'] + ' 入庫單數量修改 <br><span class="text-sm text-primary">' + data[0]['sku'] + ' ' + data[0]['product_name'] + '</span>';
                        let purchaseQty = '<span class="text-primary text-bold">總採購數量：'+data[0]['quantity']+'</span>';
                        for(let i=0; i<data.length; i++){
                            let record = '<tr><td class="text-center">'+(i+1)+'</td><td class="text-left">'+data[i]['erp_stockin_no']+'</td><td class="text-left">'+data[i]['erp_stockin_sno']+'</td><td class="text-left">'+data[i]['product_name']+'</td><td class="text-right">'+data[i]['purchase_price']+'</td><td class="text-right"><input type="hidden" class="form-control form-control-sm text-right" name="data['+i+'][id]" value="'+data[i]['id']+'"><input type="number" class="form-control form-control-sm text-right" name="data['+i+'][qty]" placeholder="輸入修改數量" value="'+data[i]['stockin_quantity']+'"></td><td class="text-right">'+data[i]['stockin_date']+'</td></tr>';
                            $('#stockinModifyRecord').append(record);
                        }
                        $('#stockinModifyModalLabel').html(label);
                        $('#purchaseQty').html(purchaseQty);
                    }
                }
            });
        $('#stockinModifyModal').modal('show');
    }
</script>
@endsection
