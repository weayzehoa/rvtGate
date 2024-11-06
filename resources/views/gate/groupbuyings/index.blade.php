@extends('gate.layouts.master')

@section('title', '團購訂單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>團購訂單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('groupBuyingOrders') }}">團購訂單管理</a></li>
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
                                <div class="col-7">
                                    <button id="showForm" class="btn btn-sm btn-success" title="使用欄位查詢">使用欄位查詢</button>
                                </div>
                                <div class="col-5">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($orders) ? number_format(isset($list) && $list == 'all' ? count($orders) : $orders->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-7 float-left">
                                        <span clas="d-flex align-items-center">查詢條件：</span>
                                        <span class="badge badge-info mr-1">
                                            @if(!empty($status) && $status != '-1,0,1,2,3,4')
                                            <span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('status')">X</span>
                                            @endif
                                            訂單狀態：
                                            @if(empty($status))全部@else
                                            @if($status == '-1,0,1,2,3,4')全部@else
                                            @if(in_array(-1,explode(',',$status)))已取消,@endif
                                            @if(in_array(0,explode(',',$status)))未付款,@endif
                                            @if(in_array(1,explode(',',$status)))待出貨,@endif
                                            @if(in_array(2,explode(',',$status)))集貨中,@endif
                                            @if(in_array(3,explode(',',$status)))已出貨,@endif
                                            @if(in_array(4,explode(',',$status)))已完成@endif
                                            @endif
                                            @endif
                                        </span>
                                        @if(!empty($shipping_time) || !empty($shipping_time_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('shipping_time')">X </span>
                                            出貨時間區間：
                                            @if(!empty($shipping_time)){{ $shipping_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($shipping_time_end)){{ '至 '.$shipping_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if((!empty($invoice_time) || !empty($invoice_time_end)))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('invoice_time')">X </span>
                                            發票開立時間區間：
                                            @if(!empty($invoice_time)){{ $invoice_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($invoice_time_end)){{ '至 '.$invoice_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($invoice_type))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('invoice_type')">X</span> 發票開立種類：{{ $invoice_type == 2 ? '二聯式' : '三聯式' }}</span>@endif
                                        @if(!empty($invoice_address))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('invoice_address')">X</span> 發票地址是否為空值：{{ $invoice_address == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($invoice_no_empty))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('invoice_no_empty')">X</span> 發票號碼是否為空值：{{ $invoice_no_empty == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($invoice_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('invoice_number')">X</span> 是否有統編：{{ $invoice_number == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($invoice_title))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('invoice_title')">X</span> 發票抬頭：{{ $invoice_title }}</span>@endif
                                        @if(!empty($is_invoice_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('is_invoice_no')">X</span> 發票號碼：{{ $is_invoice_no }}</span>@endif
                                        @if(!empty($book_shipping_date_not_fill))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('book_shipping_date_not_fill')">X </span>預定出貨日區間：未預定</span>
                                        @else
                                            @if((!empty($book_shipping_date) || !empty($book_shipping_date_end)))
                                            <span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('book_shipping_date')">X </span>
                                                預定出貨日區間：
                                                @if(!empty($book_shipping_date)){{ $book_shipping_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                @if(!empty($book_shipping_date_end)){{ '至 '.$book_shipping_date_end.' ' }}@else{{ '至 現在' }}@endif
                                            </span>
                                            @endif
                                        @endif
                                        @if(!empty($order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('order_number')">X</span> 團購訂單編號：{{ $order_number }}</span>@endif
                                        @if(!empty($partner_order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('partner_order_number')">X</span> iCarry訂單編號：{{ $partner_order_number }}</span>@endif
                                        @if(!empty($pay_time) || !empty($pay_time_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('pay_time')">X </span>
                                            付款時間區間：
                                            @if(!empty($pay_time)){{ $pay_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($pay_time_end)){{ '至 '.$pay_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($group_buying_id))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('group_buying_id')">X</span> 團購ID編號：{{ $group_buying_id }}</span>@endif
                                        @if(!empty($receiver_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('receiver_name')">X</span> 收件人姓名：{{ $receiver_name }}</span>@endif
                                        @if(!empty($receiver_tel))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('receiver_tel')">X</span> 收件人電話：{{ $receiver_tel }}</span>@endif
                                        @if(!empty($receiver_address))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('receiver_address')">X</span> 收件人地址：{{ $receiver_address }}</span>@endif
                                        @if(!empty($product_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('product_name')">X</span> 商品名稱：{{ $product_name }}</span>@endif
                                        @if(!empty($sku))<span class="badge badge-info mr-1"><span class="text-danger text-bold" style="cursor:pointer" onclick="removeCondition('sku')">X</span> 商品貨號：{{ $sku }}</span>@endif
                                        @if(isset($list) && $list == 'all')<span class="badge badge-info mr-1">全部：{{ count($orders) }} 筆</span>@else<span class="badge badge-info mr-1">每頁：{{ $list }} 筆</span>@endif
                                    </div>
                                    <div class="col-5 float-right">
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
                                    </div>
                                </div>
                                <div class="col-12 mt-2" id="showExpressTable" style="display: none">
                                    <div id="expressData" class="row"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="orderSearchForm" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('groupBuyingOrders') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-3 mt-2">
                                                <label for="order_number">團購ID編號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="group_buying_id" name="group_buying_id" placeholder="團購ID編號" value="{{ isset($group_buying_id) && $group_buying_id ? $group_buying_id : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">團購訂單編號:</label>
                                                <input type="text" class="form-control" id="order_number" name="order_number" placeholder="團購訂單編號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="pay_time">付款時間區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="pay_time" name="pay_time" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($pay_time) && $pay_time ? $pay_time : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="pay_time_end" name="pay_time_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($pay_time_end) && $pay_time_end ? $pay_time_end : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">iCarry訂單編號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="partner_order_number" name="partner_order_number" placeholder="成團後，iCarry訂單編號" value="{{ isset($partner_order_number) && $partner_order_number ? $partner_order_number : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="receiver_name">收件人姓名:</label>
                                                <input type="text" class="form-control" id="receiver_name" name="receiver_name" placeholder="填寫收件人姓名" value="{{ isset($receiver_name) ? $receiver_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="receiver_tel">收件人電話:</label>
                                                <input type="text" class="form-control" id="receiver_tel" name="receiver_tel" placeholder="填寫收件人電話" value="{{ isset($receiver_tel) ? $receiver_tel ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="receiver_address">收件地址:</label>
                                                <input type="text" class="form-control" id="receiver_address" name="receiver_address" placeholder="填寫地址" value="{{ isset($receiver_address) ? $receiver_address ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <select class="form-control" id="status" size="6" multiple>
                                                    <option value="-1" {{ isset($status) ? in_array(-1,explode(',',$status)) ? 'selected' : '' : 'selected' }}  class="text-danger">訂單已取消</option>
                                                    <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">訂單成立未付款</option>
                                                    <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">已付款待出貨</option>
                                                    <option value="2"  {{ isset($status) ? in_array(2,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">訂單集貨中</option>
                                                    <option value="3"  {{ isset($status) ? in_array(3,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">訂單已出貨</option>
                                                    <option value="4"  {{ isset($status) ? in_array(4,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">訂單已完成</option>
                                                </select><input type="hidden" value="-1,0,1,2,3,4" name="status" id="status_hidden" />
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="product_name">商品名稱:</label>
                                                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="sku">商品貨號:(EC/BOM/鼎新貨號)</label>
                                                        <input type="text" class="form-control" id="sku" name="sku" placeholder="填寫商品貨號ex:EC00527014399" value="{{ isset($sku) ? $sku ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="shipping_time">出貨時間區間:</label>
                                                        <div class="input-group">
                                                            <input type="datetime" class="form-control datetimepicker" id="shipping_time" name="shipping_time" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($shipping_time) ? $shipping_time ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="datetime" class="form-control datetimepicker" id="shipping_time_end" name="shipping_time_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($shipping_time_end) ? $shipping_time_end ?? '' : '' }}" autocomplete="off" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="list">每頁筆數:</label>
                                                        <select class="form-control" id="list" name="list">
                                                            <option value="50" {{ $list == 50 ? 'selected' : '' }}>50</option>
                                                            <option value="100" {{ $list == 100 ? 'selected' : '' }}>100</option>
                                                            <option value="300" {{ $list == 300 ? 'selected' : '' }}>300</option>
                                                            <option value="500" {{ $list == 500 ? 'selected' : '' }}>500</option>
                                                            <option value="1000" {{ $list == 1000 ? 'selected' : '' }}>1000</option>
                                                        </select>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="row" id="MoreSearch" style="display:none">
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="invoice_time">發票開立時間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="invoice_time" name="invoice_time" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($invoice_time) ? $invoice_time ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="invoice_time_end" name="invoice_time_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($invoice_time_end) ? $invoice_time_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="is_invoice_no">發票號碼:</label>
                                                        <input type="text" class="form-control" id="is_invoice_no" name="is_invoice_no" placeholder="填寫發票號碼" value="{{ isset($is_invoice_no) ? $is_invoice_no ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="invoice_title">發票抬頭:</label>
                                                        <input type="text" class="form-control" id="invoice_title" name="invoice_title" placeholder="填寫發票抬頭" value="{{ isset($invoice_title) ? $invoice_title ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="invoice_type">發票開立種類:</label>
                                                        <select class="form-control" id="invoice_type" name="invoice_type">
                                                            <option value="" {{ isset($invoice_type) ? $invoice_type == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="2" {{ isset($invoice_type) ? $invoice_type == 2 ? 'selected' : '' : '' }}>二聯式</option>
                                                            <option value="3" {{ isset($invoice_type) ? $invoice_type == 3 ? 'selected' : '' : '' }}>三聯式</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="invoice_number">是否有統編:</label>
                                                        <select class="form-control" id="invoice_number" name="invoice_number">
                                                            <option value="" {{ isset($invoice_number) ? $invoice_number == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($invoice_number) ? $invoice_number == 1 ? 'selected' : '' : '' }}>有</option>
                                                            <option value="x" {{ isset($invoice_number) ? $invoice_number == 'x' ? 'selected' : '' : '' }}>無</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="invoice_address">發票地址是否為空值:</label>
                                                        <select class="form-control" id="invoice_address" name="invoice_address">
                                                            <option value="" {{ isset($invoice_address) ? $invoice_address == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($invoice_address) ? $invoice_address == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($invoice_address) ? $invoice_address == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="invoice_no_empty">發票號碼是否為空值:</label>
                                                        <select class="form-control" id="invoice_no_empty" name="invoice_no_empty">
                                                            <option value="" {{ isset($invoice_no_empty) ? $invoice_no_empty == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($invoice_no_empty) ? $invoice_no_empty == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($invoice_no_empty) ? $invoice_no_empty == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                        <button type="button" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                        <button type="button" class="btn btn-success moreOption">更多選項</button>
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                           @if(!empty($orders))
                           <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="28%">訂單資訊 / 訂單狀態 / 物流及金流</th>
                                            <th class="text-left" width="72%">購買品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                        <tr>
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    <input type="checkbox" class="chk_box_{{ $order->id }}" name="chk_box" value="{{ $order->id }}">
                                                    <a href="{{ route('gate.groupBuyingOrders.show', $order->id) }}">
                                                        <span class="text-lg text-bold order_number_{{ $order->id }}">{{ $order->order_number }}</span>
                                                    </a>
                                                    <button class="badge badge-sm btn-primary userInfo" value="{{ $order->id }}">訂單資訊</button>
                                                    @if($order->status == 1)
                                                    <a href="javascript:" class="badge badge-sm btn-danger text-sm cancel_{{ $order->id }}" onclick="modify('{{ $order->order_number }}',{{ $order->id }},'cancel','',this)">取消訂單</span></a>
                                                    @endif
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-6">
                                                        @if($order->create_time)
                                                        <span class="text-sm">建單：{{ $order->create_time }}</span><br>
                                                        @endif
                                                        @if($order->pay_time)
                                                        <span class="text-sm">付款：{{ $order->pay_time }}</span><br>
                                                        @endif
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="status_{{ $order->id }} text-bold">
                                                        @if($order->is_del == 1)
                                                            前台使用者刪除訂單</span>
                                                        @else
                                                            @if($order->status == -1)
                                                            <span class="text-danger">訂單已取消</span>
                                                            @elseif($order->status == 0)
                                                            <span class="text-secondary">訂單成立未付款</span>
                                                            @elseif($order->status == 1)
                                                            <span class="text-primary">已付款待出貨</span>
                                                            @elseif($order->status == 2)
                                                            <span class="text-info">訂單集貨中</span>
                                                            @elseif($order->status == 3)
                                                            <span class="text-success">訂單已出貨</span>
                                                            @elseif($order->status == 4)
                                                            <span class="text-success">訂單已完成</span>
                                                            @endif
                                                            <br>
                                                        @endif
                                                        </span>
                                                    </div>
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row mb-1">
                                                    <div class="col-6 text-sm">
                                                        團購編號：<span class="badge badge-primary">{{ $order->group_buying_id }}</span><br>
                                                        寄送國家：<span class="badge badge-warning">{{ $order->ship_to }}</span>
                                                    </div>
                                                    <div class="col-6 text-sm">
                                                        <span>金流：<a href="javascript:" class="badge badge-danger" data-toggle="popover" title="訂單 {{ $order->order_number }}" data-content="
                                                            <small>
                                                                總價：{{ number_format($order->amount) ?? 0 }}<br>
                                                                折扣：<span class='text-danger'>-{{ $order->discount ?? 0 }}</span><br>
                                                                運費：{{ $order->shipping_fee ?? 0 }}<br>
                                                                行郵稅：{{ $order->parcel_tax ?? 0 }}<hr>
                                                                金流支付：{{ number_format($order->amount + $order->shipping_fee + $order->parcel_tax - $order->discount) ?? 0 }}
                                                            </small>
                                                            ">{{ $order->pay_method }}</a>
                                                        </span>
                                                        <span class="badge badge-purple">{{ number_format($order->amount + $order->shipping_fee + $order->parcel_tax - $order->discount) ?? 0 }} 元</span>
                                                    </div>
                                                </div>
                                                @if(!empty($order->partner_order_number))
                                                <hr class="mb-1 mt-1">
                                                <div class="row mb-1">
                                                    <div class="col-6 text-sm">
                                                        iCarry訂單號：<span class="badge badge-success">{{ $order->partner_order_number }}</span><br>
                                                        @if($order->status >= 3)
                                                            @if(!empty($order->is_invoice_no))
                                                            <span class="text-sm">發票號碼：
                                                                @if(strstr($order->is_invoice_no,'廢'))
                                                                <span style="text-decoration:line-through">{{ $order->is_invoice_no ?? '' }}</span>
                                                                @else
                                                                {{ $order->is_invoice_no ?? '' }}
                                                                @endif
                                                            </span>
                                                            @if(!strstr($order->is_invoice_no,'廢'))
                                                            @if(in_array($menuCode.'CI', explode(',',Auth::user()->power)))
                                                            <a href="javascript:cancelInvoice({{ $order->id }})" class="forhide badge badge-danger">作廢</a>
                                                            @endif
                                                            @endif
                                                            <br>
                                                            <span class="text-sm">發票日期：{{ explode(' ',$order->invoice_time)[0] ?? '' }}</span><br>
                                                            @else
                                                                @if(in_array($menuCode.'NI', explode(',',Auth::user()->power)))
                                                                <span class="text-sm">發票：</span>
                                                                <a href="javascript:openInvoice({{ $order->id }})" class="forhide badge badge-purple">開立發票</a>
                                                                @endif
                                                            @endif
                                                        @else
                                                            @if($order->is_ticket_order == 1)
                                                            @if(!empty($order->is_invoice_no))
                                                            <span class="text-sm">發票號碼：
                                                                @if(strstr($order->is_invoice_no,'廢'))
                                                                <span style="text-decoration:line-through">{{ $order->is_invoice_no ?? '' }}</span>
                                                                @else
                                                                {{ $order->is_invoice_no ?? '' }}
                                                                @endif
                                                            </span>
                                                            @if(!strstr($order->is_invoice_no,'廢'))
                                                            @if(in_array($menuCode.'CI', explode(',',Auth::user()->power)))
                                                            <a href="javascript:cancelInvoice({{ $order->id }})" class="forhide badge badge-danger">作廢</a>
                                                            @endif
                                                            @endif
                                                            <br>
                                                            <span class="text-sm">發票日期：{{ explode(' ',$order->invoice_time)[0] ?? '' }}</span><br>
                                                            @else
                                                            @if(in_array($menuCode.'NI', explode(',',Auth::user()->power)))
                                                            @if(in_array($order->digiwin_payment_id,$allowDigiwinPaymentIds))
                                                            <span class="text-sm">發票：</span>
                                                            <a href="javascript:tickOrderOpenInvoice({{ $order->id }})" class="forhide badge badge-purple">開立發票</a>
                                                            @endif
                                                            @endif
                                                            @endif
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <div class="col-6 text-sm">
                                                        預定出貨日：<span class="badge badge-warning">{{ $order->book_shipping_date }}</span><br>
                                                        @if($order->status >= 3)
                                                        出貨日期：<span class="badge badge-info">{{ !empty($order->shipping_time) ? explode(' ',$order->shipping_time)[0] : '' }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                            <td class="text-left align-top p-0">
                                                <table class="table table-sm">
                                                    <thead class="table-info">
                                                        <th width="12%" class="text-left align-middle text-sm">商家</th>
                                                        <th width="15%" class="text-left align-middle text-sm">貨號</th>
                                                        <th width="21%" class="text-left align-middle text-sm">品名</th>
                                                        <th width="6%" class="text-center align-middle text-sm">單位</th>
                                                        <th width="6%" class="text-right align-middle text-sm">重量(g)</th>
                                                        <th width="6%" class="text-right align-middle text-sm">總重(g)</th>
                                                        <th width="6%" class="text-right align-middle text-sm">單價</th>
                                                        <th width="5%" class="text-right align-middle text-sm">數量</th>
                                                        <th width="5%" class="text-right align-middle text-sm">總價</th>
                                                    </thead>
                                                    <tbody>
                                                        <form id="itemsform_order_{{ $order->id }}" method="POST">
                                                            @foreach($order->items as $item)
                                                            <tr>
                                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }} {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ $item->vendor_name }}
                                                                </td>
                                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ $item->digiwin_no }}
                                                                </td>
                                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ $item->product_name }}
                                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已下架)</span>@endif
                                                                </td>
                                                                <td class="text-center align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ $item->unit_name }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->gross_weight) }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->gross_weight * $item->quantity) }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->price) }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ number_format($item->quantity) }}
                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->price * $item->quantity) }}</td>
                                                            </tr>
                                                            @if(strstr($item->sku,'BOM'))
                                                                @if(count($item->package)>0)
                                                                <tr class="item_package_{{ $item->id }} m-0 p-0">
                                                                    <td colspan="12" class="text-sm p-0">
                                                                        <table width="100%" class="table-sm m-0 p-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th width="12%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                                    <th width="21%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                                    <th width="6%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                                    <th colspan="2" width="12%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="6%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($item->package as $packageItem)
                                                                                <tr>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</td>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</td>
                                                                                    <td class="text-center align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</td>
                                                                                    <td colspan="2" class="text-right align-middle text-sm"></td>
                                                                                    <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                                    <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ number_format($packageItem['quantity']) }}</td>
                                                                                    <td width="5%" class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                                </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                @endif
                                                            @endif
                                                            @endforeach
                                                            <tr>
                                                                <td colspan="4" class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">折扣 {{ number_format($order->discount) }}　運費 {{ number_format($order->shipping_fee) }}　行郵稅 {{ number_format($order->parcel_tax) }}</td>
                                                                <td colspan="1" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">總重</td>
                                                                <td colspan="1" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalWeight) }}</td>
                                                                <td colspan="1" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">商品總計</td>
                                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalQty) }}</td>
                                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalPrice) }}</td>
                                                            </tr>
                                                        </form>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"  style="border-bottom:3px #000000 solid;border-bottom:3px #000000 solid;">
                                                <span class="text-sm user_memo_{{ $order->id }} text-danger">顧　客：</span><span class="text-sm">{{ $order->user_memo ?? '' }}</span><br>
                                                @if(!empty($order->greeting_card))<span class="text-sm user_card_{{ $order->id }} text-info">賀　卡：</span><span class="text-sm">{{ $order->greeting_card ?? '' }}</span><br>@endif
                                                @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                                                <a href="javascript:" class="text-sm admin_memo_{{ $order->id }}" onclick="modify('{{ $order->order_number }}',{{ $order->id }},'admin_memo','{{ $order->admin_memo ?? '' }}',this)">管理者：{{ $order->admin_memo ?? '' }}</span></a>
                                                @else
                                                @if($order->admin_memo)
                                                <span class="text-sm admin_memo_{{ $order->id }} text-primary">管理者：</span><span>{{ $order->admin_memo ?? '' }}</span></a>
                                                @endif
                                                @endif
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：<span id="totalCount">{{ !empty($orders) ? number_format(isset($list) && $list == 'all' ? count($orders) : $orders->total()) : 0 }}</span></span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ isset($list) && $list == 'all' ? '' : $orders->appends($appends)->render() }}
                                @else
                                {{ $orders->render() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="multiProcessForm" action="{{ url('groupBuyingOrders/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="mark" action="{{ url('groupBuyingOrders/modify') }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('modal')
{{-- 多處理Modal --}}
<div id="multiModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="multiModalLabel">請選擇功能</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        @if(in_array($menuCode.'EX', explode(',',Auth::user()->power)))
                        <button class="btn btn-sm btn-primary multiProcess mr-2" value="export_OrderDetail">訂單明細匯出</button>
                        <button class="btn btn-sm btn-warning multiProcess mr-2" value="export_ShipList">團主發貨清單</button>
                        @endif
                        @if(in_array($menuCode.'NI', explode(',',Auth::user()->power)))
                        <button class="btn btn-sm btn-purple multiProcess mr-2" value="invoice_create">開立發票</button>
                        @endif
                        @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                        <button class="btn btn-sm btn-success orderMark" value="admin_memo"><span>管理員註記</span></button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 訂單資訊 Modal --}}
<div id="orderInfoModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;height: 95%;">
        <div class="modal-content" style="max-height: 95%;">
            <div class="modal-header">
                <h5 class="modal-title" id="orderInfoModalLabel">訂單資訊</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="orderInfoModalData" class="modal-body table-responsive" style="max-height: calc(100% - 60px);overflow-y: auto;">
            </div>
        </div>
    </div>
</div>

{{-- 註記 Modal --}}
<div id="myModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 50%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                <div class="form-group col-10 offset-1" id="myform"></div>
                @endif
                <div class="form-group form-group-sm" id="myrecord">
                    <label for="message-text" class="col-form-label">修改紀錄</label>
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th width="10%">#</th>
                                        <th width="25%">時間</th>
                                        <th width="20%">註記者</th>
                                        <th width="20%">欄位名稱</th>
                                        <th width="25%">紀錄內容</th>
                                    </tr>
                                </thead>
                                <tbody id="record"></tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

        $('#memo').keydown(function(){
            $('input[name="is_memo"]').prop('checked',false);
        });

        $('#is_memo').click(function(){
            if($('input[name="is_memo"]:checked').length > 0){
                $('#memo').val('');
            };
        });

        $('#is_call').keydown(function(){
            $('input[name="all_is_call"]').prop('checked',false);
        });

        $('#all_is_call').click(function(){
            if($('input[name="all_is_call"]:checked').length > 0){
                $('#is_call').val('');
            };
        });

        $('#is_print').keydown(function(){
            $('input[name="all_is_print"]').prop('checked',false);
        });

        $('#all_is_print').click(function(){
            if($('input[name="all_is_print"]:checked').length > 0){
                $('#is_print').val('');
            };
        });

        $('#item_is_call').keydown(function(){
            $('input[name="all_item_is_call"]').prop('checked',false);
        });

        $('#all_item_is_call').click(function(){
            if($('input[name="all_item_is_call"]:checked').length > 0){
                $('#item_is_call').val('');
            };
        });

        $('#book_shipping_date').change(function(){
            $('input[name="book_shipping_date_not_fill"]').prop('checked',false);
        });

        $('#book_shipping_date_end').change(function(){
            $('input[name="book_shipping_date_not_fill"]').prop('checked',false);
        });

        $('#vendor_arrival_date').change(function(){
            $('input[name="vendor_arrival_date_not_fill"]').prop('checked',false);
        });

        $('#vendor_arrival_date_end').change(function(){
            $('input[name="book_shipping_date_not_fill"]').prop('checked',false);
        });

        $('#synced_date').change(function(){
            $('input[name="synced_date_not_fill"]').prop('checked',false);
        });

        $('#synced_date_end').change(function(){
            $('input[name="synced_date_not_fill"]').prop('checked',false);
        });

        $('#synced_date_not_fill').click(function(){
            if($('input[name="synced_date_not_fill"]:checked').length > 0){
                $('#synced_date').val('');
                $('#synced_date_end').val('');
            };
        });

        $('#book_shipping_date_not_fill').click(function(){
            if($('input[name="book_shipping_date_not_fill"]:checked').length > 0){
                $('#book_shipping_date').val('');
                $('#book_shipping_date_end').val('');
            };
        });

        $('#vendor_arrival_date_not_fill').click(function(){
            if($('input[name="vendor_arrival_date_not_fill"]:checked').length > 0){
                $('#vendor_arrival_date').val('');
                $('#vendor_arrival_date_end').val('');
            };
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
        });

        $('.receiverKeyTime').click(function(){
            $("#book_shipping_date").val('');
            $("#book_shipping_date_end").val('');
            $("#receiver_key_time").val($(this).val());
            $("#receiver_key_time_end").val($(this).val());
            $('#status option[value=1]').prop('selected',true);
            $('#status option[value=2]').prop('selected',true);
            $('#status option[value=3]').prop('selected',false);
            $('#status option[value=4]').prop('selected',false);
            formSearch();
        });

        $('.bookingShippingDate').click(function(){
            $("#receiver_key_time").val('');
            $("#receiver_key_time_end").val('');
            $('#status option[value=1]').prop('selected',true);
            $('#status option[value=2]').prop('selected',true);
            $('#status option[value=3]').prop('selected',false);
            $('#status option[value=4]').prop('selected',false);
            $("#book_shipping_date").val($(this).val());
            $("#book_shipping_date_end").val($(this).val());
            formSearch();
        });

        $('#pchkbox').click(function(){
            if($(this).prop("checked") == true){
                $('.purchase_data').prop('checked',true);
            }else{
                $('.purchase_data').prop('checked',false);
            }
        });

        $('#selectAll').click(function(){
            $('.purchase_data').prop('checked',true);
        });

        $('#cancelAll').click(function(){
            $('.purchase_data').prop('checked',false);
        });

        $('#selectAll2').click(function(){
            $('.remove_data').prop('checked',true);
        });

        $('#cancelAll2').click(function(){
            $('.remove_data').prop('checked',false);
        });

        $('.chkallitem').change(function(){
            let order_id = $(this).val();
            if($(this).prop("checked") == true){
                $('.order_item_id_'+order_id).prop("checked",true);
                $('.chk_box_'+order_id).prop("checked",true);
            }else{
                $('.order_item_id_'+order_id).prop("checked",false);
                $('.chk_box_'+order_id).prop("checked",false);
            }
        });

        $('input[name="order_item_id"]').change(function(e){
            let order_id = e.target.attributes[4].value;
            let chk = $('input[name="order_item_id"]:checked').length;
            chk == 0 ? $('.chk_box_'+order_id).prop("checked",false) : $('.chk_box_'+order_id).prop("checked",true);
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
            }else if($(this).val() == 'selected'){
                $('input[name="chk_box"]').prop("checked",false);
                $('#multiProcess').prop("disabled",false);
            }else if($(this).val() == 'byQuery'){
                $('input[name="chk_box"]').prop("checked",false);
                $('#multiProcess').prop("disabled",false);
            }else{
                $('#multiProcess').prop("disabled",true);
            }
            $('#orderSearchForm').hide();
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
            if(cate == 'Purchase'){
                $('#purchaseData').html('');
                $("#purchaseTable").DataTable().destroy();
                let token = '{{ csrf_token() }}';
                $.ajax({
                    type: "post",
                    url: 'groupBuyingOrders/getUnPurchase',
                    data: { id: ids, condition: condition, cate: cate, filename: filename, method: multiProcess, model: 'groupbuyOrders', _token: token },
                    success: function(data) {
                        let record = '';
                        let items = null;
                        let orderIds = null;
                        let itemData = [];
                        data != null ? items = data['items'] : '';
                        data != null ? orderIds = data['orderIds'] : '';
                        if(items != null){
                            let s = 0;
                            for(let i=0; i<items.length; i++){
                                if(items[i]['quantity'] != 0){
                                    let value = items[i]['product_model_id']+'_@_'+items[i]['orderItemIds']+'_@_'+items[i]['syncedOrderItemIds']+'_@_'+items[i]['quantity'];
                                    itemData[s] = [
                                        items[i]['vendor_arrival_date'],
                                        items[i]['sku'],
                                        items[i]['vendor_name'],
                                        items[i]['product_name'],
                                        items[i]['direct_shipment'],
                                        items[i]['purchase_price'],
                                        items[i]['quantity'],
                                        '<div class="icheck-primary"><input type="checkbox" id="pchkbox'+i+'" name="purchase_data[]" class="purchase_data" value="'+value+'"><label for="pchkbox'+i+'"></label></div>'
                                    ];
                                    s++;
                                }
                            }
                            $('#purchaseTable').DataTable({
                                "data": itemData,
                                // "columns": [ // 列的標題一般是從DOM中讀取（也可以使用這個屬性為表格創建列標題)
                                //     { title: "廠商到貨日"},
                                //     { title: "品號"},
                                //     { title: "廠商名稱"},
                                //     { title: "品名"},
                                //     { title: "數量",},
                                //     { title: "勾選",},
                                // ],
                                // select: true,
                                // order: [[ 1, 'asc' ]],
                                // select: {
                                //     style:    'multi',
                                //     selector: 'td:first-child'
                                // },
                                "columnDefs":[
                                    // {width: "5%", orderable: false, targets:0, className: 'dt-body-center select-checkbox'},
                                    { width: "10%", targets: 0, className: 'dt-body-left'},
                                    { width: "10%", targets: 1, className: 'dt-body-left'},
                                    { width: "15%", targets: 2, className: 'dt-body-left'},
                                    { width: "25%", targets: 3, className: 'dt-body-left'},
                                    { width: "10%", targets: 4, className: 'dt-body-center' },
                                    { width: "10%", targets: 5, className: 'dt-body-right' },
                                    { width: "10%", targets: 6, className: 'dt-body-right' },
                                    { width: "10%", targets: 7, className: 'dt-body-center',orderable: false },
                                ],
                                "paging": true,
                                "pageLength": 10,
                                "lengthChange": true,
                                "lengthMenu": [[10, 25, 50, 100 ,300 ,500, -1], [10, 25, 50, 100 ,300 ,500, "全部"]],
                                "searching": true,
                                "ordering": true,
                                "info": true,
                                "autoWidth": true,
                                "responsive": true,
                                "deferRender": true,
                                // "scrollY": 600,
                                "scrollCollapse": true,
                                "scroller": true,
                                "autoWidth": false,
                                "language": {
                                    "decimal": ",",
                                    "thousands": "."
                                },
                                "oLanguage": {
                                    "sUrl": "//cdn.datatables.net/plug-ins/1.11.3/i18n/zh_Hant.json"
                                }
                            });

                            form.append( $('<input type="hidden" class="formappend" name="orderIds" value="'+orderIds+'">') );
                            form.append( $('<input type="hidden" class="formappend" name="cate" value="Purchase">') );
                            $('.purchaseProcess').prop('disabled',false);
                        }else{
                            record = '<tr><td class="text-left" colspan="6"><h3>無未採購商品</h3></td></tr>';
                            $('#purchaseData').append(record);
                            $('.purchaseProcess').prop('disabled',true);
                        }

                        $('#multiModal').modal('hide');
                        $('#purchaseModel').modal('show');
                    }
                });
            }else if(cate == 'Shipping'){
                $('#multiModal').modal('hide');
                $('#shippingModel').modal('show');
                return;
            }else if(cate == 'RemovePurchase'){
                $('#removePurchaseData').html('');
                $("#removeTable").DataTable().destroy();
                $('#removeModal').modal('show');
                let token = '{{ csrf_token() }}';
                $.ajax({
                    type: "post",
                    url: 'groupBuyingOrders/getPurchasedItems',
                    data: { id: ids, condition: condition, cate: cate, filename: filename, method: multiProcess, model: 'groupbuyOrders', _token: token },
                    success: function(items) {
                        let record = '';
                        let itemData = [];
                        if(items.length > 0){
                            for(let i=0; i<items.length; i++){
                                let value = items[i]['id'];
                                itemData[i] = [
                                    items[i]['book_shipping_date'],
                                    items[i]['order_number'],
                                    items[i]['purchase_no'],
                                    items[i]['vendor_name'],
                                    items[i]['product_name'],
                                    items[i]['quantity'],
                                    items[i]['purchase_price'],
                                    '<div class="icheck-primary"><input type="checkbox" id="rpchkbox'+i+'" name="remove_data[]" class="remove_data" value="'+value+'"><label for="rpchkbox'+i+'"></label></div>'
                                ];
                            }
                            $('#removeTable').DataTable({
                                "data": itemData,
                                // "columns": [ // 列的標題一般是從DOM中讀取（也可以使用這個屬性為表格創建列標題)
                                //     { title: "廠商到貨日"},
                                //     { title: "品號"},
                                //     { title: "廠商名稱"},
                                //     { title: "品名"},
                                //     { title: "數量",},
                                //     { title: "勾選",},
                                // ],
                                // select: true,
                                order: [[ 1, 'asc' ]],
                                // select: {
                                //     style:    'multi',
                                //     selector: 'td:first-child'
                                // },
                                "columnDefs":[
                                    // {width: "5%", orderable: false, targets:0, className: 'dt-body-center select-checkbox'},
                                    { width: "10%", targets: 0, className: 'dt-body-left'},
                                    { width: "10%", targets: 1, className: 'dt-body-left'},
                                    { width: "10%", targets: 2, className: 'dt-body-left'},
                                    { width: "15%", targets: 3, className: 'dt-body-left'},
                                    { width: "25%", targets: 4, className: 'dt-body-left'},
                                    { width: "10%", targets: 5, className: 'dt-body-right' },
                                    { width: "10%", targets: 6, className: 'dt-body-right' },
                                    { width: "10%", targets: 7, className: 'dt-body-center',orderable: false },
                                ],
                                "paging": true,
                                "pageLength": 10,
                                "lengthChange": true,
                                "lengthMenu": [[10, 25, 50, 100 ,300 ,500, -1], [10, 25, 50, 100 ,300 ,500, "全部"]],
                                "searching": true,
                                "ordering": true,
                                "info": true,
                                "autoWidth": true,
                                "responsive": true,
                                "deferRender": true,
                                // "scrollY": 600,
                                "scrollCollapse": true,
                                "scroller": true,
                                "autoWidth": false,
                                "language": {
                                    "decimal": ".",
                                    "thousands": ","
                                },
                                "oLanguage": {
                                    "sUrl": "//cdn.datatables.net/plug-ins/1.11.3/i18n/zh_Hant.json"
                                }
                            });
                            $('.removeProcess').prop('disabled',false);
                        }else{
                            record = '<tr><td class="text-left" colspan="6"><h3>無採購商品</h3></td></tr>';
                            $('#removePurchaseData').append(record);
                            $('.removeProcess').prop('disabled',true);
                        }
                        $('#multiModal').modal('hide');
                        $('#removeModal').modal('show');
                    }
                });
            }else{
                form.submit();
                $('.formappend').remove();
                $('#multiModal').modal('hide');
            }
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

        $('.removeProcess').click(function(){
            let form = $('#multiProcessForm');
            form.append( $('<input type="hidden" class="formappend" name="filename" value="移除採購註記">') );
            $('#multiProcessForm > input[name=type]').val($(this).val());
            $('#multiProcessForm > input[name=orderIds]').remove();
            let selected = $('.remove_data:checked').serializeArray().map( item => item.value );
            for(let j=0; j<selected.length;j++){
                let tmp = '';
                tmp = $('<input type="hidden" class="formappend" name="synceOrderItemIds['+j+']" value="'+selected[j]+'">');
                form.append(tmp);
            }
            form.submit();
            $('.formappend').remove();
        });

        $('#multiProcess').click(function(){
            let multiProcess = $('input[name="multiProcess"]:checked').val();
            let totalCount = $('#totalCount').html().replace(/[^\d]/g, "");
            if(multiProcess == 'byQuery' && totalCount > 10000){ //by條件
                alert('目前查詢條件已超過 10,000 筆，請增加搜尋條件後再操作');
                return;
            }
            if($('input[name="multiProcess"]:checked').val() == 'selected'){
                let num = $('input[name="chk_box"]:checked').length;
                if(num == 0){
                    alert('尚未選擇訂單');
                    return;
                }
            }
            $('#multiModal').modal('show');
        });

        $('#hidemodify').click(function (e) {
            $('.forhide').toggle('display');
            $(this).html() == '隱藏所有註記' ? $(this).html('顯示所有註記') : $(this).html('隱藏所有註記');
        });

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#orderSearchForm').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });
        var shippingVendor = '{{ !empty($shipping_vendor_name) ? $shipping_vendor_name : null }}';
        sessionStorage.setItem('shippingData',null);
        $('#showExpress').click(function(){
            let text = $('#showExpress').html();
            let token = '{{ csrf_token() }}';
            let param = null;
            $('#showExpressTable').toggle();
            text == '顯示各物流總數' ? $('#showExpress').html('隱藏各物流總數') : $('#showExpress').html('顯示各物流總數');
            if(text == '顯示各物流總數'){
                $('#expressData').html('<div class="col-12">各物流總數計算中，請稍後...</div>');
                let dataStr = sessionStorage.getItem('shippingData');
                let newData = JSON.parse(dataStr);
                if(dataStr == 'null'){
                    let url = window.location.href;
                    param = url.split('?')[1];
                    param == null ? param = 'all' : '';
                    $.ajax({
                        type: "get",
                        url: 'groupBuyingOrders/getExpressData',
                        data: {getExpress:param},
                        success: function(data) {
                            if(data){
                                let dataString = JSON.stringify(data);
                                sessionStorage.setItem('shippingData',dataString);
                                let html = '';
                                let chk = '';
                                for(let i=0;i<data.length;i++){
                                    shippingVendor == data[i]['name'] ? chk = 'checked' : chk = '';
                                    if(data[i]['count'] > 0){
                                        html += '<div class="icheck-primary d-inline mb-3 col-2"><input type="radio" class="mb-3" id="express_'+i+'" name="express_way" value="'+data[i]['name']+'" '+chk+'><label for="express_'+i+'" class="mb-3 mr-3 expressWay">'+data[i]['name']+'<sub class="text-primary">('+data[i]['count']+')</sub></label></div>';
                                    }
                                }
                                $('#expressData').html(html);
                                $('input[name="express_way"]').click(function(){
                                    $("#shipping_vendor_name").val($(this).val());
                                    formSearch();
                                });
                            }
                        }
                    });
                }else{
                    let html = '';
                    let chk = '';
                    for(let i=0;i<newData.length;i++){
                        shippingVendor == newData[i]['name'] ? chk = 'checked' : chk = '';
                        if(newData[i]['count'] > 0){
                            html += '<div class="icheck-primary d-inline mb-3 col-2"><input type="radio" class="mb-3" id="express_'+i+'" name="express_way" value="'+newData[i]['name']+'" '+chk+'><label for="express_'+i+'" class="mb-3 mr-3 expressWay">'+newData[i]['name']+'<sub class="text-primary">('+newData[i]['count']+')</sub></label></div>';
                        }
                    }
                    $('#expressData').html(html);
                    $('input[name="express_way"]').click(function(){
                        $("#shipping_vendor_name").val($(this).val());
                        formSearch();
                    });
                }
            }
        });

        $('.moreOption').click(function(){
            $('#MoreSearch').toggle();
            $(this).html() == '更多選項' ? $(this).html('隱藏更多選項') : $(this).html('更多選項');
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

        $('.orderMark').click(function (e) {
            let multiProcess = $('input[name="multiProcess"]:checked').val();
            if(multiProcess == 'allOnPage' || multiProcess == 'selected'){
                $('#multiModal').modal('hide');
                let orderids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
                orderids.length > 0 ? modify('',orderids,$(this).val(),'','') : alert('尚未選擇訂單，請重新選擇');
            }else if(multiProcess == 'byQuery'){ //by條件
                $('#multiModal').modal('hide');
                modifyByQuery($(this).val());
            }
        });

        $('.modifybyQuerySend').click(function(){
            $('#mark').submit();
        });

        $('.syncedOrderItem').click(function(){
            alert($(this).val());
        });

        $('#showShippingNote').click(function(){
            $('#shippingNote').show();
        });

        $('input[name=type]').click(function(){
            let type = $(this).val();
            if(type == '自行挑選'){
                $('#shippingNote').hide();
                $('#shippingVendor').show();
            }else if(type=='廠商發貨'){
                $('#shippingNote').hide();
                $('#shippingVendor').hide();
            }else if(type=='移除物流'){
                $('#shippingVendor').hide();
                $('#shippingNote').hide();
            }else if(type=='依系統設定'){
                $('#shippingNote').hide();
                $('#shippingVendor').hide();
            }
        });

        $('#orderImport').click(function(){
            $('#importModal').modal('show');
        });

        $('.modifyInfo').click(function(){
            $('#modifyRecord').html('');
            let token = '{{ csrf_token() }}';
            let id = $(this).val();
            $.ajax({
                type: "post",
                url: 'groupBuyingOrders/getlog',
                data: { order_id: id, _token: token },
                success: function(data) {
                    let record = '';
                    if(data.length > 0){
                        for(let i=0; i<data.length; i++){
                            let dateTime = data[i]['created_at'];
                            let name = data[i]['name'];
                            let log = data[i]['log'];
                            let col_name = data[i]['column_name'];
                            let record = '<tr class="record"><td class="align-middle">'+(data.length - i)+'</td><td class="align-middle">'+dateTime+'</td><td class="align-middle">'+name+'</td><td class="text-left align-middle">'+col_name+'</td><td class="align-middle">'+log+'</td></tr>';
                            $('#modifyRecord').append(record);
                        }
                    }
                    $('#modifyModal').modal('show');
                }
            });
        });

        $('.userInfo').click(function(){
            $('#orderInfoModalData').html('');
            let token = '{{ csrf_token() }}';
            let pwd = prompt("請輸入密碼，輸入錯誤超過三次帳號將會被鎖住。");
            if(pwd != null){
                let id = $(this).val();
                $.ajax({
                    type: "post",
                    url: 'groupBuyingOrders/getInfo',
                    data: {pwd:pwd, id: id, _token:token },
                    success: function(data) {
                        let message = data['message'];
                        let order = data['order'];
                        let count = data['count'];
                        if(count >= 3){ //滾
                            alert(message);
                            location.href = 'logout';
                        }else if(message != null){
                            alert(message);
                        }else{
                            order['china_id_img1'] == null ? order['china_id_img1'] = '' : '';
                            order['china_id_img2'] == null ? order['china_id_img2'] = '' : '';
                            let chinaIdImg1 = order['china_id_img1'];
                            let chinaIdImg2 = order['china_id_img2'];
                            order['invoice_type'] == 2 ? order['invoice_type'] = '二聯式' : '';
                            order['invoice_type'] == 3 ? order['invoice_type'] = '三聯式' : '';
                            order['receiver_name'] == '' || order['receiver_name'] == null ? order['receiver_name'] = '' : '';
                            order['receiver_email'] == '' || order['receiver_email'] == null ? order['receiver_email'] = '' : '';
                            order['user_memo'] == '' || order['user_memo'] == null ? order['user_memo'] = '' : '';
                            order['receiver_tel'] == '' || order['receiver_tel'] == null ? order['receiver_tel'] = '' : '';
                            order['greeting_card'] == '' || order['greeting_card'] == null ? order['greeting_card'] = '' : '';
                            order['receiver_keyword'] == '' || order['receiver_keyword'] == null ? order['receiver_keyword'] = '' : '';
                            order['receiver_key_time'] == '' || order['receiver_key_time'] == null ? order['receiver_key_time'] = '' : '';
                            order['buyer_email'] == '' || order['buyer_email'] == null ? order['buyer_email'] = '' : '';
                            order['buyer_name'] == '' || order['buyer_name'] == null ? order['buyer_name'] = '' : '';
                            order['invoice_number'] == '' || order['invoice_number'] == null ? order['invoice_number'] = '' : '';
                            order['invoice_title'] == '' || order['invoice_title'] == null ? order['invoice_title'] = '' : '';
                            order['carrier_num'] == '' || order['carrier_num'] == null ? order['carrier_num'] = '' : '';
                            order['love_code'] == '' || order['love_code'] == null ? order['love_code'] = '' : '';
                            order['is_invoice_no'] == '' || order['is_invoice_no'] == null ? order['is_invoice_no'] = '' : '';
                            order['carrier_type'] == '' || order['carrier_type'] == null ? order['carrier_type'] = '不使用載具' : '';
                            order['carrier_type'] == '0' ? order['carrier_type'] = '手機條碼' : '';
                            order['carrier_type'] == '1' ? order['carrier_type'] = '自然人憑證條碼' : '';
                            order['carrier_type'] == '2' ? order['carrier_type'] = '智富寶載具' : '';
                            order['invoice_sub_type'] == 1 ? order['invoice_sub_type'] = '發票捐贈：慈善基金會' : '';
                            order['invoice_sub_type'] == 2 ? order['invoice_sub_type'] = '個人戶' : '';
                            order['invoice_sub_type'] == 3 ? order['invoice_sub_type'] = '公司' : '';
                            let html = '<div class="row align-items-center"><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">團購ID :</span></div><input type="text" class="form-control" value="'+order['group_buying_id']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">收件人資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收件人</span></div><input type="text" class=" form-control" value="'+order['receiver_name']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">電話</span></div><input type="text" class=" form-control" value="'+order['receiver_tel']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">E-Mail</span></div><input type="text" class=" form-control" value="'+order['receiver_email']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">發票資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票號碼</span></div><input type="text" class=" form-control" value="'+order['is_invoice_no']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">類別</span></div><input type="text" class=" form-control" value="'+order['invoice_sub_type']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">愛心碼</span></div><input type="text" class=" form-control" value="'+order['love_code']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">載具</span></div><input type="text" class=" form-control" value="'+order['carrier_type']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">手機條碼/自然人憑證條碼</span></div><input type="text" class=" form-control" value="'+order['carrier_num']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票聯式</span></div><input type="text" class=" form-control" value="'+order['invoice_type']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">統編</span></div><input type="text" class=" form-control" value="'+order['invoice_number']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">抬頭</span></div><input type="text" class=" form-control" value="'+order['invoice_title']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收受人真實姓名</span></div><input type="text" class=" form-control" value="'+order['buyer_name']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票收受人E-Mail</span></div><input type="text" class=" form-control" value="'+order['buyer_email']+'" disabled></div></div>';
                            chinaIdImg1 != '' ? html += '<div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">中國身分證照片</span></div><div class="col-12 mb-2"><div class="row"><div class="col-6"><img class="col-12" src="'+chinaIdImg1+'"></div><div class="col-6"><img class="col-12" src="'+chinaIdImg2+'"></div></div></div>' : '';
                            html += '</div>';
                            $('#orderInfoModalData').append(html);
                            $('#orderInfoModal').modal('show');
                        }
                    }
                });
            }
        });

        $('#importBtn').click(function(){
            let form = $('#importForm');
            $('#importBtn').attr('disabled',true);
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

        $("#searchForm").submit();
    }

    function itemmemo (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let admin_memo=$(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'groupBuyingOrders/itemmemo',
                data: { id: id, admin_memo: admin_memo , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        $("#item_memo_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="itemmemo(event,'+id+');">'+admin_memo+'</textarea>');
                        $("#item_memo_"+id).html('<i class="fa fa-info-circle"></i>');
                        $("#item_memo_"+id).popover('hide');
                    }
                }
            });
        }
    }

    function modifyByQuery(column_name){
        let token = '{{ csrf_token() }}';
        let datepicker = '';
        let dateFormat = 'yy-mm-dd';
        let timeFormat = 'HH:mm:ss';
        let note = '<div><span class="text-primary">清空內容為取消註記</span>，<span class="text-danger">訂單狀態為【已付款待出貨 或 集貨中】才會被變更喔(防呆機制)</span></div>';
        $('#myform').html('');
        $('#record').html('');
        $('#myrecord').addClass('d-none');
        if(column_name == 'book_shipping_date'){
            label = title = '預定出貨日';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
        }else if(column_name == 'buy_memo'){
            label = title = '採購日註記';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'billOfLoading_memo'){
            label = title = '提單日註記';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'special_memo'){
            label = title = '特殊註記';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'new_shipping_memo'){
            label = title = '物流日註記';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'shipping_memo_vendor'){
            label = title = '選擇物流商';
            placeholder = '';
        }else if(column_name == 'is_call'){
            label = title = '已叫貨註記';
            dateFormat = 'yymmdd';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：20180709';
        }else if(column_name == 'is_print'){
            label = title = '已列印註記';
            dateFormat = 'yymmdd';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：20180709';
        }else if(column_name == 'receiver_key_time'){
            label = title = '提貨日註記';
            dateFormat = 'yy-mm-dd';
            datepicker = '<div id="data_datetimepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09 15:30:00';
        }else if(column_name == 'shipping_time'){
            label = title = '已出貨日註記';
            dateFormat = 'yy-mm-dd';
            datepicker = '<div id="data_datetimepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09 15:30:00';
        }else if(column_name == 'admin_memo'){
            label = title = '管理者註記';
            placeholder = '請輸入'+title+'內容';
            note = '<div><span class="text-primary">清空內容為取消註記</span></div>';
        }else if(column_name == 'order_item_modify'){
            alert('依查詢條件不支援此功能');
            return;
        }

        if(column_name == 'shipping_memo_vendor'){
            $.ajax({
                type: "post",
                url: 'groupBuyingOrders/getshippingvendors',
                data: { _token: token },
                success: function(data) {
                    var options = '';
                    for(i=0;i<data.length;i++){
                        options = options + '<option value="'+data[i]['name']+'">'+data[i]['name']+'</option>';
                    }
                    options = options + '<option value="">移除物流商</option>';
                    html = '<div class="input-group"><span class="input-group-text">下拉選擇物流商</span><select class="form-control col-12" id="data" name="data">'+options+'</select><button type="button" class="btn btn-primary" onclick="modifyByQuerySend(\''+column_name+'\')">確定</button><button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">取消</span></button></div>';
                    $('#myform').html(html);
                }
            });
        }else{
            html = '<div class="input-group"><span class="input-group-text">輸入內容</span><input type="text" class="form-control col-12" id="data" name="data" value="" placeholder="'+placeholder+'" autocomplete="off"><button type="button" class="btn btn-primary" onclick="modifyByQuerySend(\''+column_name+'\')">確定</button><button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">取消</span></button></div>'+note+datepicker;
            $('#myform').html(html);
        }

        $('#ModalLabel').html(label);
        $('#myModal').modal('show');

        $('#data').click(function(){
            $('#data_datepicker').toggle();
            $('#data_datetimepicker').toggle();
        });

        $('#data_datepicker').datepicker({
            dateFormat: dateFormat,
            onSelect: function (date) {
                $('input[name=data]').val(date);
                $('#data_datepicker').toggle();
            }
        });

        $('#data_datetimepicker').datetimepicker({
            timeFormat: timeFormat,
            dateFormat: dateFormat,
            onSelect: function (date) {
                $('input[name=data]').val(date);
            }
        });
    }

    function modifyByQuerySend(column_name){
        let form = $('#mark');
        let column_value = $('input[name=data]').val();
        let multiProcess = $('input[name="multiProcess"]:checked').val();
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
        let con_val = $('#searchForm').serializeArray().map( item => item.value );
        let con_name = $('#searchForm').serializeArray().map( item => item.name );
        for(let j=0; j<con_name.length;j++){
            let tmp = '';
            tmp = $('<input type="hidden" class="formappend" name="con['+con_name[j]+']" value="'+con_val[j]+'">');
            form.append(tmp);
        }
        form.append($('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">'));
        form.append($('<input type="hidden" class="formappend" name="column_name" value="'+column_name+'">'));
        form.append($('<input type="hidden" class="formappend" name="column_data" value="'+column_value+'">'));
        form.submit();
        $('.formappend').remove();
        $('#multiModal').modal('hide');
    }

    function modify(order_number,order_id,column_name,column_value,e,item_id){
        let token = '{{ csrf_token() }}';
        let itemIds = [];
        let id = [];
        let datepicker = '';
        let dateFormat = 'yy-mm-dd';
        let timeFormat = 'HH:mm:ss';
        let note = '<div><span class="text-primary">清空內容為取消註記</span>，<span class="text-danger">訂單狀態為【已付款待出貨 或 集貨中】才會被變更喔(防呆機制)</span></div>';
        !Array.isArray(order_id)? id[0] = order_id : id = order_id;
        $('#myform').html('');
        $('#record').html('');
        $('#syncRecord').html('');
        $('#myrecord').addClass('d-none');
        if(column_name == 'sync_date'){
            id.length >=2 ? label = '同步紀錄' : label = '訂單編號：'+order_number+'，同步紀錄';
        }else if(column_name == 'book_shipping_date'){
            title = '預定出貨日';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('預定出貨日：','');
            column_value == 'null' ? column_value = '' : '';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
        }else if(column_name == 'vendor_arrival_date'){
            title = '廠商到貨日';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('廠商到貨日：','');
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
        }else if(column_name == 'buy_memo'){
            title = '採購日註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('採購日：','');
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'billOfLoading_memo'){
            title = '提單日註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('提單日：','');
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'special_memo'){
            title = '特殊註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('特註：','');
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'new_shipping_memo'){
            title = '物流日註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('物流日：','');
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09';
            note = '<div><span class="text-primary">清空內容為取消註記</span>';
        }else if(column_name == 'shipping_memo_vendor'){
            title = '選擇物流商';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('物流商：','');
            placeholder = '';
        }else if(column_name == 'is_call'){
            title = '已叫貨註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('已叫貨','').replace('/','').replace('/','');
            dateFormat = 'yymmdd';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：20180709';
        }else if(column_name == 'is_print'){
            title = '已列印註記';
            dateFormat = 'yymmdd';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：20180709';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('已列印','').replace('/','').replace('/','');
        }else if(column_name == 'receiver_key_time'){
            title = '提貨日註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('提貨日：','');
            dateFormat = 'yy-mm-dd';
            datepicker = '<div id="data_datetimepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09 15:30:00';
        }else if(column_name == 'shipping_time'){
            title = '已出貨日註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            id.length >=2 ? '' : $(e).html() == title ? column_value = '' : column_value = column_value.replace('已出貨','');
            dateFormat = 'yy-mm-dd';
            datepicker = '<div id="data_datetimepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：2018-07-09 15:30:00';
        }else if(column_name == 'admin_memo'){
            title = '管理者註記';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            placeholder = '請輸入'+title+'內容';
            note = '<div><span class="text-primary">清空內容為取消註記</span></div>';
        }else if(column_name == 'shipping_number'){
            title = '物流單號';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            placeholder = '請輸入'+title+'內容';
            note = '<div><span class="text-primary">清空內容為取消物流單號</span></div>';
        }else if(column_name == 'cancel'){
            title = '取消訂單';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，'+title;
            placeholder = '請輸入取消訂單原因，例如：客戶要求取消';
            note = '<div><span class="text-danger">訂單狀態為【已付款待出貨】才會被變更喔(防呆機制)</span></div>';
        }else if(column_name == 'order_item_modify'){
            title = '商品叫貨註記';
            orderNum = '';
            for(i=0;i<id.length;i++){
                itemIds[i] = $('.order_item_id_'+id[i]).serializeArray().map( item => item.value );
                if(itemIds[i].length == 0){
                    orderNum = orderNum + '訂單編號：' + $('.order_number_'+id[i]).html() + '\n';
                }
            }
            if(orderNum){
                alert('下面訂單未選擇任何商品，無法繼續執行！\n'+orderNum);
                return;
            }
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，'+title;
            dateFormat = 'yymmdd';
            datepicker = '<div id="data_datepicker" style="display:none"></div>';
            placeholder = '請輸入'+title+'日期，格式：20180709';
            note = '';
            if(id.length == 1){
                if(itemIds[0].length < 1){
                    alert('請先選擇要註記的商品');
                    return;
                }
            }
        }else if(column_name == 'item_is_call_clear'){
            if(confirm('請確認是否取消此商品叫貨註記')){
                title = '商品叫貨註記';
                itemIds[0] = [item_id];
                id[0] = [order_id];
                modifysend(id,column_name,'',itemIds);
            }
            return
        }

        if(column_name == 'sync_date'){
            html = '';
        }else if(column_name == 'shipping_memo_vendor'){
            html = '<div class="input-group"><span class="input-group-text">下拉選擇物流商</span><select class="form-control col-12" id="data" name="data"></select><button type="button" class="btn btn-primary modifysend">確定</button><button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">取消</span></button></div>';
        }else{
            html = '<div class="input-group"><span class="input-group-text">輸入內容</span><input type="text" class="form-control col-12" id="data" name="data" value="'+column_value+'" placeholder="'+placeholder+'" autocomplete="off"><button type="button" class="btn btn-primary modifysend">確定</button><button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">取消</span></button></div>'+note+datepicker;
        }

        if( id.length == 1 ){
            if(column_name == 'sync_date'){
                $('#myrecord').removeClass('d-none');
                $.ajax({
                    type: "post",
                    url: 'groupBuyingOrders/getlog',
                    data: { order_id: id, column_name: column_name , _token: token },
                    success: function(data) {
                        let record = '';
                        if(data.length > 0){
                            let erpOrderNo = data[0]['erp_order_no'];
                            for(let i=0; i<data.length; i++){
                                let dateTime = data[i]['create_time'];
                                let name = data[i]['name'];
                                let amount = data[i]['amount'];
                                data[i]['total_item_quantity'] == null ? data[i]['total_item_quantity'] = '' : '';
                                let itemQty = data[i]['total_item_quantity'];
                                let shippingFee = data[i]['shipping_fee'];
                                let parcelTax = data[i]['parcel_tax'];
                                let discount = data[i]['discount'];
                                let status = data[i]['status'];
                                let spendPoint = data[i]['spend_point'];
                                let record = '<tr><td width="10%" class="text-center">'+(i+1)+'</td><td width="15%" class="text-left">'+dateTime+'</td><td width="15%" class="text-left">'+name+'</td><td width="10%" class="text-left">'+status+'</td><td width="10%" class="text-right">'+amount+'</td><td width="8%" class="text-right">'+itemQty+'</td><td width="8%" class="text-right">'+shippingFee+'</td><td width="8%" class="text-right">'+parcelTax+'</td><td width="8%" class="text-right">'+discount+'</td><td width="8%" class="text-right">'+spendPoint+'</td></tr>';
                                $('#syncRecord').append(record);
                            }
                            label = '訂單編號：'+order_number+'，鼎新訂單編號：'+erpOrderNo+'，同步紀錄';
                        }
                        $('#syncModalLabel').html(label);
                        $('#syncModal').modal('show');
                    }
                });
            }else if(column_name != 'order_item_modify' && column_name != 'sync_date'){
                $('#myrecord').removeClass('d-none');
                $.ajax({
                    type: "post",
                    url: 'groupBuyingOrders/getlog',
                    data: { order_id: id, column_name: column_name , _token: token },
                    success: function(data) {
                        let record = '';
                        if(data.length > 0){
                            for(let i=0; i<data.length; i++){
                                let dateTime = data[i]['created_at'];
                                let name = data[i]['name'];
                                let log = data[i]['log'];
                                let col_name = data[i]['column_name'];
                                let record = '<tr class="record"><td class="align-middle">'+(data.length - i)+'</td><td class="align-middle">'+dateTime+'</td><td class="align-middle">'+name+'</td><td class="text-left align-middle">'+col_name+'</td><td class="align-middle">'+log+'</td></tr>';
                                $('#record').append(record);
                            }
                        }
                    }
                });
            }
        }
        if(column_name != 'sync_date'){
            $('#ModalLabel').html(label);
            $('#myform').html(html);
            $('#myModal').modal('show');
        }

        if(column_name == 'shipping_memo_vendor'){
            $.ajax({
                type: "post",
                url: 'groupBuyingOrders/getshippingvendors',
                data: { _token: token },
                success: function(data) {
                    var options = '';
                    for(i=0;i<data.length;i++){
                        column_value == data[i]['name'] ? select = 'selected' : select = '';
                        options = options + '<option value="'+data[i]['name']+'" '+select+'>'+data[i]['name']+'</option>';
                    }
                    options = options + '<option value="">移除物流商</option>';
                    $('#data').html(options);
                }
            });
        }

        $('#data').click(function(){
            $('#data_datepicker').toggle();
            $('#data_datetimepicker').toggle();
        });

        $('#data_datepicker').datepicker({
            dateFormat: dateFormat,
            onSelect: function (date) {
                $('input[name=data]').val(date);
                $('#data_datepicker').toggle();
            }
        });

        $('#data_datetimepicker').datetimepicker({
            timeFormat: timeFormat,
            dateFormat: dateFormat,
            onSelect: function (date) {
                $('input[name=data]').val(date);
            }
        });

        $('.modifysend').click(function () {
            let column_data = $('#data').val();
            column_data ? column_data = column_data : column_data = null;
            if(column_name == 'cancel'){
                if(column_data == null){
                    alert('請填寫取消訂單原因');
                    return;
                }else{
                    if(confirm('請確認是否真的要取消該訂單？')){
                    modifysend(id,column_name,column_data,itemIds)
                    }else{
                        $('#myModal').modal('hide');
                    }
                }
            }else{
                modifysend(id,column_name,column_data,itemIds);
            }
        });
    }

    function modifysend(id,column_name,column_data,itemIds){
        let token = '{{ csrf_token() }}';
        $.ajax({
            type: "post",
            url: 'groupBuyingOrders/modify',
            data: { id: id, column_name: column_name, column_data: column_data, item_ids: itemIds, _token: token },
            success: function(orders) {
                console.log(orders);
                let column_name2 = 'vendor_arrival_date';
                if(orders){
                    for(i=0;i<orders.length;i++){
                        target = '.'+column_name+'_'+orders[i]['id'];
                        value = orders[i][column_name];
                        if(column_name == 'book_shipping_date'){
                            nullText = '預定出貨日：無';
                            value ? text = '預定出貨日：'+value.replace('-','/').replace('-','/').substring(0,16) : text = null;
                        }else if(column_name == 'vendor_arrival_date'){
                            nullText = '廠商到貨日：無';
                            value ? text = '廠商到貨日：'+value.replace('-','/').replace('-','/').substring(0,16) : text = null;
                        }else if(column_name == 'buy_memo'){
                            nullText = '採購日註記';
                            text = '採購日：'+value;
                        }else if(column_name == 'new_shipping_memo'){
                            nullText = '物流日註記';
                            text = '物流日：'+value;
                        }else if(column_name == 'shipping_memo_vendor'){
                            nullText = '選擇物流商';
                            text = '物流商：'+value;
                        }else if(column_name == 'billOfLoading_memo'){
                            nullText = '提單日註記';
                            text = '提單日：'+value;
                        }else if(column_name == 'special_memo'){
                            nullText = '特殊註記';
                            text = '特註：'+value;
                        }else if(column_name == 'is_call'){
                            nullText = '已叫貨註記：無';
                            value ? text = '已叫貨註記：'+value.substring(0,4)+'/'+value.substring(4,6)+'/'+value.substring(6,8) : text = null;
                        }else if(column_name == 'is_print'){
                            nullText = '已列印註記：無';
                            value ? text = '已列印註記：'+value.substring(0,4)+'/'+value.substring(4,6)+'/'+value.substring(6,8) : text = null;
                        }else if(column_name == 'shipping_time'){
                            nullText = '出貨日註記：無';
                            value ? text = '出貨日註記：'+value.replace('-','/').replace('-','/').substring(0,16) : text = null;
                        }else if(column_name == 'receiver_key_time'){
                            nullText = '提貨日註記：無';
                            value ? text = '提貨日：'+value.replace('-','/').replace('-','/').substring(0,16) : text = null;
                        }else if(column_name == 'admin_memo'){
                            nullText = '<span>管理者：</span>';
                            text = '<span>管理者：'+value+'</span>';
                        }else if(column_name == 'shipping_number'){
                            nullText = '<span>物流單號：</span>';
                            text = '<span>物流單號：'+value+'</span>';
                        }
                        if(column_name == 'cancel'){
                            target = '.admin_memo_'+orders[i]['id'];
                            value = orders[i]['admin_memo'];
                            nullText = '<span>管理者：</span>';
                            text = '<span>管理者：</span>'+value+'</span>';
                            target2 = '.status_'+orders[i]['id'];
                            text2 = '<span class="text-danger">訂單已取消</span>';
                            target3 = '.cancel_'+orders[i]['id'];
                            target4 = 'admin_memo';
                            $(target).attr('onclick','modify("'+orders[i]['order_number']+'",'+orders[i]['id']+',\''+target4+'\',\''+value+'\',this)');
                            value ? $(target).html(text) : $(target).html(nullText);
                            $(target2).html(text2);
                            $(target3).remove();
                        }else if(column_name == 'order_item_modify' || column_name == 'item_is_call_clear'){
                            items = orders[i]['items'];
                            for(j=0; j<items.length; j++){
                                value = items[j]['is_call'];
                                target = '.order_item_modify_'+items[j]['id'];
                                if(value){
                                    text = value.substring(0,4)+'/'+value.substring(4,6)+'/'+value.substring(6,8)+'叫貨';
                                    html = '<a href="javascript:" class="forhide badge badge-danger item_is_call_'+items[j]['id']+'" onclick="modify("'+orders[i]['order_number']+'",'+orders[i]['id']+',\'item_is_call_clear\',\''+value+'\',this,'+items[j]['id']+')"><span>'+text+'</span></a> '+items[j]['vendor_name'];
                                }else{
                                    html = '<input class="order_item_id_'+orders[i]['id']+'" type="checkbox" name="order_item_id" value="'+items[j]['id']+'"> '+items[j]['vendor_name'];
                                }
                                $(target).html(html);
                            }
                            $('.chk_all_item_'+orders[i]['id']).prop('checked',false);
                            $('.chk_box_'+orders[i]['id']).prop('checked',false);
                            $('#multiFunc').hide()
                            $('#multiProcess').html('多筆處理');
                            $("#multiFunc>button").attr("disabled",true);
                            var num_all = $('input[name="chk_box"]').length;
                            var num = $('input[name="chk_box"]:checked').length;
                            $("#chkallbox_text").text("("+num+"/"+num_all+")");
                        }else{
                            $(target).attr('onclick','modify("'+orders[i]['order_number']+'",'+orders[i]['id']+',\''+column_name+'\',\''+value+'\',this)');
                            value ? $(target).html(text) : $(target).html(nullText);
                        }
                    }
                    $('#myModal').modal('hide');
                }
            }
        });
    }

    function removeCondition(name){
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
        if(name == 'pay_time' || name == 'created_at' || name == 'shipping_time' || name == 'invoice_time' || name == 'book_shipping_date' || name == 'synced_date' || name == 'purchase_time'){
            $('input[name="'+name+'"]').val('');
            $('input[name="'+name+'_end"]').val('');
        }else if(name == 'status'){
            $('input[name="'+name+'"]').val('-1,0,1,2,3,4');
        }else if(name == 'shipping_method'){
            $('input[name="'+name+'"]').val('1,2,3,4,5,6');
        }else if(name == 'spend_point' || name == 'is_discount' || name == 'is_asiamiles' || name == 'is_shopcom' || name == 'channel_order' || name == 'domain' || name == 'shipping_vendor_name' || name == 'pay_method' || name == 'invoice_type' || name == 'invoice_number' || name == 'invoice_no_empty' || name == 'invoice_address'){
            $('#'+name).empty();
        }else{
            $('input[name="'+name+'"]').val('');
        }
        $("#searchForm").submit();
    }

    function openPackage($val)
    {
        $('.item_package_'+$val).toggle('display');
    }

    function openInvoice(orderId)
    {
        if(confirm("注意！訂單狀態已出貨才能開立發票，請確認是否要開立發票？")){
            let form = $('#multiProcessForm');
            form.append($('<input type="hidden" class="formappend" name="id[]" value="'+orderId+'">'));
            form.append($('<input type="hidden" class="formappend" name="method" value="OneOrder">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="invoice">'));
            form.append($('<input type="hidden" class="formappend" name="type" value="create">'));
            form.append( $('<input type="hidden" class="formappend" name="filename" value="開立發票">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
            form.submit();
        }
    }

    function tickOrderOpenInvoice(orderId)
    {
        if(confirm("注意！此訂單為票券訂單，請確認是否要開立發票？")){
            let form = $('#multiProcessForm');
            form.append($('<input type="hidden" class="formappend" name="id[]" value="'+orderId+'">'));
            form.append($('<input type="hidden" class="formappend" name="method" value="OneOrder">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="invoice">'));
            form.append($('<input type="hidden" class="formappend" name="type" value="create">'));
            form.append( $('<input type="hidden" class="formappend" name="filename" value="開立發票">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="ticketOrderOpenInvoice">') );
            form.submit();
        }
    }

    function cancelInvoice(orderId)
    {
        let reason = null;
        if(reason = prompt("注意！訂單狀態已出貨且有發票號碼才能作廢，\n請輸入理由並按確認按鈕作廢發票？")){
            let form = $('#multiProcessForm');
            form.append($('<input type="hidden" class="formappend" name="reason" value="'+reason+'">'));
            form.append($('<input type="hidden" class="formappend" name="id[]" value="'+orderId+'">'));
            form.append($('<input type="hidden" class="formappend" name="method" value="OneOrder">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="invoice">'));
            form.append($('<input type="hidden" class="formappend" name="type" value="cancel">'));
            form.append( $('<input type="hidden" class="formappend" name="filename" value="作廢發票">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
            form.submit();
        }
    }

    function purchase(orderId)
    {
        let form = $('#multiProcessForm');
        let ids = $('.order_item_id_'+orderId+':checkbox:checked').serializeArray().map( item => item.value );
        for(let j=0; j<ids.length;j++){
            let tmp = '';
            tmp = $('<input type="hidden" class="formappend" name="id[]" value="'+ids[j]+'">');
            form.append(tmp);
        }
        let export_method = $('<input type="hidden" class="formappend" name="method" value="OneOrder">');
        let export_cate = $('<input type="hidden" class="formappend" name="cate" value="Purchase">');
        let export_type = $('<input type="hidden" class="formappend" name="type" value="OneOrder">');
        form.append(export_method);
        form.append(export_cate);
        form.append(export_type);
        form.append( $('<input type="hidden" class="formappend" name="filename" value="單一訂單">') );
        form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
        form.submit();
        $('.formappend').remove();
    }

    function purchaseCancel(id)
    {
        if(confirm('注意!! 移除採購註記，並不會移除採購單內的商品資料，請自行手動修改採購單內商品資料。請確認是否移除採購註記??')){
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'groupBuyingOrders/purchaseCancel',
                data: { id: id, _token: token },
                success: function(data) {
                    console.log(data);
                    if(data){
                        $('.syncedOrderItem_'+id).popover('hide');
                        $('.syncedOrderItem_'+id).remove();
                    }
                }
            });
        }
    }

    function pickupShipping(itemId){
        $('#shippingRecord').html('');
        let token = '{{ csrf_token() }}';
        if(itemId){
            $.ajax({
                type: "post",
                url: 'groupBuyingOrders/getlog',
                data: { order_item_id: itemId, column_name: 'shipping_memo' , _token: token },
                success: function(data) {
                    if(data.length > 0){
                        $('#myShippingRecord').removeClass('d-none');
                        for(let i=0; i<data.length; i++){
                            let dateTime = data[i]['created_at'];
                            let name = data[i]['name'];
                            let log = data[i]['log'];
                            let col_name = data[i]['column_name'];
                            let record = '<tr class="record"><td class="align-middle">'+(data.length - i)+'</td><td class="align-middle">'+dateTime+'</td><td class="align-middle">'+name+'</td><td class="text-left align-middle">'+col_name+'</td><td class="align-middle">'+log+'</td></tr>';
                            $('#shippingRecord').append(record);
                        }
                    }else{
                        $('#myShippingRecord').addClass('d-none');
                    }
                }
            });
        }
        $('#shippingModel').modal('show');
        let cate = 'pickupShipping';
        let form = $('#pickupShippingForm');
        let condition = null;
        if(itemId){ //訂單商品id
            let multiProcess = 'selected';
            form.append($('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="'+cate+'">'));
            form.append($('<input type="hidden" class="formappend" name="order_item_id">').val(itemId));
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
        }else{ //訂單多重
            let multiProcess = $('input[name="multiProcess"]:checked').val();
            let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
            if(multiProcess == 'allOnPage' || multiProcess == 'selected'){
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
            }else{
                return;
            }
            form.append($('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="'+cate+'">'));
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
        }
    }

    function searchNG(orderNumber){
        let form = $('#searchForm');
        $('#order_number').val(orderNumber);
        form.submit();
    }

    function notPurchase(id,e){
        let token = '{{ csrf_token() }}';
        let text = $(e).parent().text().replace(/\s/g, '');
        $.ajax({
            type: "post",
            url: 'groupBuyingOrders/markNotPurchase',
            data: { order_item_id: id, _token: token },
            success: function(data) {
                // console.log(data);
                // return;
                if(data == 0){
                    $(e).removeClass('active')
                    alert(text+'已取消不採購標記')
                }else if(data == 1){
                    $(e).addClass('active')
                    alert(text+'已增加不採購標記')
                }
            }
        });
    }

    function ksort(obj){
        var keys = Object.keys(obj).sort()
            , sortedObj = {};
        for(var i in keys) {
            sortedObj[keys[i]] = obj[keys[i]];
        }
        return sortedObj;
    }
</script>
@endsection
