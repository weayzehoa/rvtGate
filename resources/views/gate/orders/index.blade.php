@extends('gate.layouts.master')

@section('title', '訂單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>訂單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">訂單管理</a></li>
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
                                    <button id="hidemodify" class="btn btn-sm btn-secondary" title="隱藏所有註記">隱藏所有註記</button>
                                    <button id="showForm" class="btn btn-sm btn-success" title="使用欄位查詢">使用欄位查詢</button>
                                    <button id="showExpress" class="btn btn-sm btn-info">顯示各物流總數</button>
                                    @if(in_array($menuCode.'IM', explode(',',Auth::user()->power)))
                                    <button id="orderImport" class="btn btn-sm btn-warning mr-5">匯入</button>
                                    @endif
                                    @if(in_array($menuCode.'EX', explode(',',Auth::user()->power)))
                                    <button id="friendExport" class="btn btn-sm btn-secondary">好友推薦匯出</button>
                                    @endif
                                </div>
                                <div class="col-5">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg">總筆數：{{ !empty($orders) ? number_format(isset($list) && $list == 'all' ? count($orders) : $orders->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row mt-2">
                                    <div class="col-7">
                                        <button class="btn btn-sm bg-navy mr-1 mt-1">預定出貨日</button>
                                        @foreach($bookShippingDates as $bookShippingDate)
                                        <a href="{{ env('APP_URL').'/orders?status=1,2&book_shipping_date='.$bookShippingDate->book_shipping_date.'&book_shipping_date_end='.$bookShippingDate->book_shipping_date }}" class="{{ !empty($book_shipping_date) && $book_shipping_date == $bookShippingDate->book_shipping_date ? 'btn-primary' : 'fc-button text-primary' }} btn btn-sm mr-1 mt-1">{{ $bookShippingDate->book_shipping_date }} <span class="badge badge-sm badge-secondary">{{ $bookShippingDate->count }}</span></a>
                                        @endforeach
                                        <a href="{{ env('APP_URL').'/orders' }}" class="fc-button text-primary btn btn-sm mr-1 mt-1">清除選項</a>
                                    </div>
                                    <div class="col-5">
                                        <button class="btn btn-sm bg-navy mr-1 mt-1">提貨日提示</button>
                                        @foreach($pickupDates as $pickupDate)
                                        <a href="{{ env('APP_URL').'/orders?status=1,2&receiver_key_time='.$pickupDate->receiver_key_time.'&receiver_key_time_end='.$pickupDate->receiver_key_time }}" class="{{ !empty($receiver_key_time) && $receiver_key_time == $pickupDate->receiver_key_time ? 'btn-primary' : 'fc-button text-primary' }} btn btn-sm mr-1 mt-1">{{ $pickupDate->receiver_key_time }} <span class="badge badge-sm badge-secondary">{{ $pickupDate->count }}</span></a>
                                        @endforeach
                                        <a href="{{ env('APP_URL').'/orders' }}" class="fc-button text-primary btn btn-sm mr-1 mt-1">清除選項</a>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-7 float-left">
                                        <span clas="d-flex align-items-center">查詢條件：</span>
                                        <span class="badge badge-info mr-1">
                                            @if(!empty($status) && $status != '-1,0,1,2,3,4')
                                            <span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('status')">X</span>
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
                                        <span class="badge badge-info mr-1">
                                            @if(!empty($shipping_method) && $shipping_method != '1,2,3,4,5,6')
                                            <span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('shipping_method')">X</span>
                                            @endif
                                            物流方式：
                                            @if(empty($shipping_method))全部@else
                                            @if($shipping_method == '1,2,3,4,5,6')全部@else
                                            @if(in_array(1,explode(',',$shipping_method)))機場提貨,@endif
                                            @if(in_array(2,explode(',',$shipping_method)))旅店提貨,@endif
                                            @if(in_array(3,explode(',',$shipping_method)))現場提貨,@endif
                                            @if(in_array(4,explode(',',$shipping_method)))寄送海外,@endif
                                            @if(in_array(5,explode(',',$shipping_method)))寄送台灣,@endif
                                            @if(in_array(6,explode(',',$shipping_method)))寄送當地@endif
                                            @endif
                                            @endif
                                        </span>
                                        @if(!empty($origin_country))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('origin_country')">X</span> 發貨地：{{ $origin_country }}</span>@endif
                                        @if(!empty($all_is_call))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('all_is_call')">X</span> 訂單-已叫貨註記：有註記</span>
                                        @else
                                        @if(!empty($is_call))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_call')">X</span> 訂單-已叫貨註記：{{ $is_call == 'X' ? '尚無註記' : $is_call }}</span>@endif
                                        @endif
                                        @if(!empty($all_item_is_call))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('all_item_is_call')">X</span> 商品-已叫貨註記：有註記</span>
                                        @else
                                        @if(!empty($item_is_call))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('item_is_call')">X</span> 商品-已叫貨註記：{{ $item_is_call == 'X' ? '尚無註記' : $item_is_call }}</span>@endif
                                        @endif
                                        @if(!empty($all_is_print))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('all_is_print')">X</span> 列印註記：有註記</span>
                                        @else
                                        @if(!empty($is_print))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_print')">X</span> 列印註記：{{ $is_print == 'X' ? '尚無註記' : $is_print }}</span>@endif
                                        @endif
                                        @if(!empty($created_at) || !empty($created_at_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('created_at')">X </span>
                                            建單時間區間：
                                            @if(!empty($created_at)){{ $created_at.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($created_at_end)){{ '至 '.$created_at_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($shipping_time) || !empty($shipping_time_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('shipping_time')">X </span>
                                            出貨時間區間：
                                            @if(!empty($shipping_time)){{ $shipping_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($shipping_time_end)){{ '至 '.$shipping_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if((!empty($invoice_time) || !empty($invoice_time_end)))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_time')">X </span>
                                            發票開立時間區間：
                                            @if(!empty($invoice_time)){{ $invoice_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($invoice_time_end)){{ '至 '.$invoice_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($invoice_type))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_type')">X</span> 發票開立種類：{{ $invoice_type == 2 ? '二聯式' : '三聯式' }}</span>@endif
                                        @if(!empty($invoice_address))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_address')">X</span> 發票地址是否為空值：{{ $invoice_address == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($invoice_no_empty))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_no_empty')">X</span> 發票號碼是否為空值：{{ $invoice_no_empty == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($invoice_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_number')">X</span> 是否有統編：{{ $invoice_number == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($invoice_title))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_title')">X</span> 發票抬頭：{{ $invoice_title }}</span>@endif
                                        @if(!empty($is_invoice_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_invoice_no')">X</span> 發票號碼：{{ $is_invoice_no }}</span>@endif
                                        @if(!empty($direct_shipment))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('direct_shipment')">X</span> 是否有直寄：{{ $direct_shipment == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($spend_point))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('spend_point')">X</span> 使用購物金：{{ $spend_point == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($is_discount))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_discount')">X</span> 折扣：{{ $is_discount == 1 ? '有' : '無' }}</span>@endif
                                        @if(!empty($is_asiamiles))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_asiamiles')">X</span> Asiamiles訂單：{{ $is_asiamiles == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($is_shopcom))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_shopcom')">X</span> 美安訂單：{{ $is_shopcom == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($line_ecid))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('line_ecid')">X</span> LINE導購：{{ $line_ecid == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($greeting_card))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('greeting_card')">X</span> 賀卡留言：{{ $greeting_card == 1 ? '是' : '否' }}</span>@endif
                                        @if(!empty($promotion_code))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('promotion_code')">X</span> 折扣代碼：{{ $promotion_code ?? ''}}</span>@endif
                                        @if(!empty($digiwin_payment_id))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('digiwin_payment_id')">X</span> 渠道訂單：{{ $digiwin_payment_id }}</span>@endif
                                        @if(!empty($domain))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('domain')">X</span> 購買網址：{{ $domain }}</span>@endif
                                        @if(!empty($shipping_vendor_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('shipping_vendor_name')">X</span> 物流商：{{ $shipping_vendor_name }}</span>@endif
                                        @if(!empty($source))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('source')">X</span> 金流方式：{{ strlen($source) > 32 ? substr($source,0,32).'...etc' : '' }}</span>@endif
                                        @if(!empty($synced_date_not_fill))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('synced_date_not_fill')">X </span>同步鼎新：未同步</span>
                                        @else
                                            @if((!empty($synced_date) || !empty($synced_date_end)))
                                            <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('synced_date')">X </span>
                                                同步鼎新日期區間：
                                                @if(!empty($synced_date)){{ $synced_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                @if(!empty($synced_date_end)){{ '至 '.$synced_date_end.' ' }}@else{{ '至 現在' }}@endif
                                            </span>
                                            @endif
                                        @endif
                                        @if(!empty($vendor_arrival_date_not_fill))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_arrival_date_not_fill')">X </span>廠商出貨日區間：未預定</span>
                                        @else
                                            @if((!empty($vendor_arrival_date) || !empty($vendor_arrival_date_end)))
                                            <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_arrival_date')">X </span>
                                                廠商出貨日區間：
                                                @if(!empty($vendor_arrival_date)){{ $vendor_arrival_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                @if(!empty($vendor_arrival_date_end)){{ '至 '.$vendor_arrival_date.' ' }}@else{{ '至 現在' }}@endif
                                            </span>
                                            @endif
                                        @endif
                                        @if(!empty($book_shipping_date_not_fill))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('book_shipping_date_not_fill')">X </span>預定出貨日區間：未預定</span>
                                        @else
                                            @if((!empty($book_shipping_date) || !empty($book_shipping_date_end)))
                                            <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('book_shipping_date')">X </span>
                                                預定出貨日區間：
                                                @if(!empty($book_shipping_date)){{ $book_shipping_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                @if(!empty($book_shipping_date_end)){{ '至 '.$book_shipping_date_end.' ' }}@else{{ '至 現在' }}@endif
                                            </span>
                                            @endif
                                        @endif
                                        @if(!empty($pay_method) && $pay_method != '全部')<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('pay_method')">X</span> 付款方式：{{ $pay_method }}</span>@endif
                                        @if(!empty($order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('order_number')">X</span> 訂單編號：{{ $order_number }}</span>@endif
                                        @if(!empty($partner_order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('partner_order_number')">X</span> 合作廠商訂單號：{{ $partner_order_number }}</span>@endif
                                        @if(!empty($pay_time) || !empty($pay_time_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('pay_time')">X </span>
                                            付款時間區間：
                                            @if(!empty($pay_time)){{ $pay_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($pay_time_end)){{ '至 '.$pay_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($purchase_time) || !empty($purchase_time_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('pay_time')">X </span>
                                            採購時間區間：
                                            @if(!empty($purchase_time)){{ $purchase_time.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($purchase_time_end)){{ '至 '.$purchase_time_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($shipping_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('shipping_number')">X</span> 物流單號：{{ $shipping_number }}</span>@endif
                                        @if(!empty($user_id))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('user_id')">X</span> 購買者ID：{{ $user_id }}</span>@endif
                                        @if(!empty($buyer_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('buyer_name')">X</span> 購買者姓名：{{ $buyer_name }}</span>@endif
                                        @if(!empty($buyer_phone))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('buyer_phone')">X</span> 購買者電話：{{ $buyer_phone }}</span>@endif
                                        @if(!empty($receiver_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('receiver_name')">X</span> 收件人姓名：{{ $receiver_name }}</span>@endif
                                        @if(!empty($receiver_tel))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('receiver_tel')">X</span> 收件人電話：{{ $receiver_tel }}</span>@endif
                                        @if(!empty($receiver_address))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('receiver_address')">X</span> 收件人地址：{{ $receiver_address }}</span>@endif
                                        @if(!empty($vendor_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_name')">X</span> 商家名稱：{{ $vendor_name }}</span>@endif
                                        @if(!empty($product_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('product_name')">X</span> 商品名稱：{{ $product_name }}</span>@endif
                                        @if(!empty($sku))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('sku')">X</span> 商品貨號：{{ $sku }}</span>@endif
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
                                @if(!empty($NGOrder))
                                <div class="col-12">
                                    <div class="col-7 float-left">
                                        <span clas="d-flex align-items-center">異常訂單：</span>
                                        @for($i=0;$i<count($NGOrder);$i++)
                                        <a href="{{ 'orders?order_number='.$NGOrder[$i] }}" target="_blank"><span class="badge badge-sm badge-danger mr-2">{{ $NGOrder[$i] }}</span></a>
                                        @endfor
                                    </div>
                                </div>
                                @endif
                                @if(!empty($NGProduct))
                                <div class="col-12">
                                    <div class="col-7 float-left">
                                        <span clas="d-flex align-items-center">異常商品：</span>
                                        @for($i=0;$i<count($NGProduct);$i++)
                                        <span class="badge badge-sm badge-danger mr-2" >{{ $NGProduct[$i] }}</span>
                                        @endfor
                                    </div>
                                </div>
                                @endif
                                @if(!empty($NGBookShippingDate))
                                <div class="col-12">
                                    <div class="col-7 float-left">
                                        <span clas="d-flex align-items-center">預定出貨日異常訂單：</span>
                                        @for($i=0;$i<count($NGBookShippingDate);$i++)
                                        <span class="badge badge-sm badge-danger mr-2" >{{ $NGBookShippingDate[$i] }}</span>
                                        @endfor
                                    </div>
                                </div>
                                @endif
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
                                <form id="searchForm" role="form" action="{{ url('orders') }}" method="get">
                                    <input type="hidden" id="receiver_key_time" name="receiver_key_time" value="{{ !empty($receiver_key_time) ?? null }}">
                                    <input type="hidden" id="receiver_key_time_end" name="receiver_key_time_end" value="{{ !empty($receiver_key_time_end) ?? null }}">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6 mt-2">
                                                <label for="synced_date">同步鼎新日期區間:(有輸入日期則無法勾選未預定)</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="synced_date" name="synced_date" placeholder="格式：2016-06-06" value="{{ isset($synced_date) ? $synced_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="synced_date_end" name="synced_date_end" placeholder="格式：2016-06-06" value="{{ isset($synced_date_end) ? $synced_date_end ?? '' : '' }}" autocomplete="off" />
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">未同步</span>
                                                    </div>
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <input type="checkbox" id="synced_date_not_fill" name="synced_date_not_fill" value="1" {{ isset($synced_date_not_fill) && ($synced_date_not_fill == 1 || $synced_date_not_fill == 'on') ? 'checked' : '' }}>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 row">
                                                <div class="col-6 mt-2">
                                                    <label for="order_number">採購狀態: <span class="text-primary "><i class="fas fa-store-alt"></i></span></label>
                                                    <select class="form-control" id="is_purchase" name="is_purchase">
                                                        <option value="" {{ isset($is_purchase) ? $is_purchase == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                        <option value="有採購"{{ isset($is_purchase) && $is_purchase == '有採購' ? 'selected' : '' }}>已同步有採購</option>
                                                        <option value="無採購"{{ isset($is_purchase) && $is_purchase == '無採購' ? 'selected' : '' }}>已同步未採購</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <label class="control-label" for="direct_shipment">是否有直寄: <span class="text-primary "><i class="fas fa-truck"></i></span></label>
                                                    <select class="form-control" id="direct_shipment" name="direct_shipment">
                                                        <option value="" {{ isset($direct_shipment) ? $direct_shipment == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                        <option value="1" {{ isset($direct_shipment) ? $direct_shipment == 1 ? 'selected' : '' : '' }}>是</option>
                                                        <option value="x" {{ isset($direct_shipment) ? $direct_shipment == 'x' ? 'selected' : '' : '' }}>否</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                <div class="col-6 mt-2">
                                                    <label for="order_number">訂單編號:</label>
                                                    <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="訂單編號，舊系統編號為16碼" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <label for="order_number">採購單編號:</label>
                                                    <input type="number" inputmode="numeric" class="form-control" id="purchase_no" name="purchase_no" placeholder="採購單編號" value="{{ isset($purchase_no) && $purchase_no ? $purchase_no : '' }}" autocomplete="off" />
                                                </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label for="partner_order_number">合作廠商訂單號:</label>
                                                        <input type="text" class="form-control" id="partner_order_number" name="partner_order_number" placeholder="可輸入客路或是蝦皮訂單號" value="{{ isset($partner_order_number) && $partner_order_number ? $partner_order_number : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="shipping_number">物流單號:</label>
                                                        <input type="text" class="form-control" name="shipping_number" placeholder="任何物流單號" value="{{ isset($shipping_number) && $shipping_number ? $shipping_number : '' }}" autocomplete="off" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="pay_time">付款時間區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="pay_time" name="pay_time" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($pay_time) && $pay_time ? $pay_time : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="pay_time_end" name="pay_time_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($pay_time_end) && $pay_time_end ? $pay_time_end : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="pay_time">採購時間區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="purchase_time" name="purchase_time" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($purchase_time) && $purchase_time ? $purchase_time : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="purchase_time_end" name="purchase_time_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($purchase_time_end) && $purchase_time_end ? $purchase_time_end : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="status">訂單狀態:</label>
                                                <select class="form-control" id="status" size="6" multiple>
                                                    <option value="-1" {{ isset($status) ? in_array(-1,explode(',',$status)) ? 'selected' : '' : 'selected' }}  class="text-danger">後台取消訂單</option>
                                                    <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">訂單成立，尚未付款</option>
                                                    <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">已付款，等待出貨</option>
                                                    <option value="2"  {{ isset($status) ? in_array(2,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">訂單集貨中</option>
                                                    <option value="3"  {{ isset($status) ? in_array(3,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">訂單已出貨</option>
                                                    <option value="4"  {{ isset($status) ? in_array(4,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">訂單已完成</option>
                                                    <option value="5"  {{ isset($status) ? in_array(5,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-danger">開立票券失敗</option>
                                                </select><input type="hidden" value="-1,0,1,2,3,4,5" name="status" id="status_hidden" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="shipping_method">物流方式: (ctrl+點選可多選)</label>
                                                <select class="form-control" id="shipping_method" size="6" multiple>
                                                    <option value="1" {{ isset($shipping_method) ? in_array(1,explode(',',$shipping_method)) ? 'selected' : '' : 'selected' }}>機場提貨</option>
                                                    <option value="2" {{ isset($shipping_method) ? in_array(2,explode(',',$shipping_method)) ? 'selected' : '' : 'selected' }}>旅店提貨</option>
                                                    <option value="3" {{ isset($shipping_method) ? in_array(3,explode(',',$shipping_method)) ? 'selected' : '' : 'selected' }}>現場提貨</option>
                                                    <option value="4" {{ isset($shipping_method) ? in_array(4,explode(',',$shipping_method)) ? 'selected' : '' : 'selected' }}>寄送海外</option>
                                                    <option value="5" {{ isset($shipping_method) ? in_array(5,explode(',',$shipping_method)) ? 'selected' : '' : 'selected' }}>寄送台灣</option>
                                                    <option value="6" {{ isset($shipping_method) ? in_array(6,explode(',',$shipping_method)) ? 'selected' : '' : 'selected' }}>寄送當地</option>
                                                </select><input type="hidden" value="1,2,3,4,5,6" name="shipping_method" id="shipping_method_hidden" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="origin_country">發貨地: (ctrl+點選可多選)</label>
                                                <select class="form-control" id="origin_country" size="2" multiple>
                                                    <option value="台灣" {{ isset($origin_country) ? in_array('台灣',explode(',',$origin_country)) ? 'selected' : '' : '' }}>台灣</option>
                                                    <option value="日本" {{ isset($origin_country) ? in_array('日本',explode(',',$origin_country)) ? 'selected' : '' : '' }}>日本</option>
                                                </select><input type="hidden" value="" name="origin_country" id="origin_country_hidden" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="is_print">已列印註記: (有輸入內容則無法勾選已標記已印)</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="is_print" name="is_print" placeholder="請輸入20180709，或輸入X表示查詢尚無註記" value="{{ isset($is_print) ? $is_print ?? '' : '' }}" autocomplete="off" />
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">已標記已印</span>
                                                    </div>
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <input type="checkbox" id="all_is_print" name="all_is_print" value="ALL" {{ isset($all_is_print) ? $all_is_print == 'ALL' ? 'checked' : '' : ''}}>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row" id="MoreSearch" style="display:none">
                                            <div class="col-6 mt-2">
                                                <label for="user_id">購買者ID:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="user_id" name="user_id" placeholder="填寫購買者ID" value="{{ isset($user_id) ? $user_id ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="buyer_name">購買者姓名:</label>
                                                <input type="text" class="form-control" id="buyer_name" name="buyer_name" placeholder="填寫購買人姓名" value="{{ isset($buyer_name) ? $buyer_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            {{-- <div class="col-6 mt-2">
                                                <label for="buyer_phone">購買者電話:</label>
                                                <input type="text" class="form-control" id="buyer_phone" name="buyer_phone" placeholder="填寫購買人電話" value="{{ isset($buyer_phone) ? $buyer_phone ?? '' : '' }}" autocomplete="off" />
                                            </div> --}}
                                            <div class="col-6 mt-2">
                                                <label for="receiver_name">收件人姓名:</label>
                                                <input type="text" class="form-control" id="receiver_name" name="receiver_name" placeholder="填寫收件人姓名" value="{{ isset($receiver_name) ? $receiver_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            {{-- <div class="col-6 mt-2">
                                                <label for="receiver_tel">收件人電話:</label>
                                                <input type="text" class="form-control" id="receiver_tel" name="receiver_tel" placeholder="填寫收件人電話" value="{{ isset($receiver_tel) ? $receiver_tel ?? '' : '' }}" autocomplete="off" />
                                            </div> --}}
                                            <div class="col-6 mt-2">
                                                <label for="receiver_address">收件地址:</label>
                                                <input type="text" class="form-control" id="receiver_address" name="receiver_address" placeholder="填寫地址" value="{{ isset($receiver_address) ? $receiver_address ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="vendor_name">商家名稱:</label>
                                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱:海邊走走" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
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
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="created_at">建單時間區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="created_at" name="created_at" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($created_at) ? $created_at ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="created_at_end" name="created_at_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($created_at_end) ? $created_at_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
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
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="spend_point">是否使用購物金:</label>
                                                        <select class="form-control" id="spend_point" name="spend_point">
                                                            <option value="" {{ isset($spend_point) ? $spend_point == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($spend_point) ? $spend_point == 1 ? 'selected' : '' : '' }}>有</option>
                                                            <option value="x" {{ isset($spend_point) ? $spend_point == 'x' ? 'selected' : '' : '' }}>無</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="is_discount">是否有折扣:</label>
                                                        <select class="form-control" id="is_discount" name="is_discount">
                                                            <option value="" {{ isset($is_discount) ? $is_discount == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($is_discount) ? $is_discount == 1 ? 'selected' : '' : '' }}>有</option>
                                                            <option value="x" {{ isset($is_discount) ? $is_discount == 'x' ? 'selected' : '' : '' }}>無</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="zero_tax">零稅率訂單:</label>
                                                        <select class="form-control" id="zero_tax" name="zero_tax">
                                                            <option value="" {{ isset($zero_tax) ? $zero_tax == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($zero_tax) ? $zero_tax == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($zero_tax) ? $zero_tax == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="digiwin_payment_id">各渠道訂單:</label>
                                                        <select class="form-control" id="digiwin_payment_id" name="digiwin_payment_id">
                                                            <option value="" {{ isset($digiwin_payment_id) ? $digiwin_payment_id == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option class="text-danger" value="001,002,003,004,005,006,007,008,009,037,063,073" {{ isset($digiwin_payment_id) ? $digiwin_payment_id == '001,002,003,004,005,006,007,008,009,037,063' ? 'selected' : '' : '' }}>iCarry Web (包含多項金流)</option>
                                                            @foreach($digiwinCustomers as $customer)
                                                            <option value="{{ $customer->customer_no }}" {{ isset($digiwin_payment_id) ? $digiwin_payment_id == $customer->customer_no ? 'selected' : '' : '' }}>{{ $customer->customer_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="is_asiamiles">Asiamiles訂單:</label>
                                                        <select class="form-control" id="is_asiamiles" name="is_asiamiles">
                                                            <option value="" {{ isset($is_asiamiles) ? $is_asiamiles == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($is_asiamiles) ? $is_asiamiles == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($is_asiamiles) ? $is_asiamiles == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="is_asiamiles">美安訂單:</label>
                                                        <select class="form-control" id="is_asiamiles" name="is_shopcom">
                                                            <option value="" {{ isset($is_shopcom) ? $is_shopcom == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($is_shopcom) ? $is_shopcom == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($is_shopcom) ? $is_shopcom == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="line_ecid">LINE導購:</label>
                                                        <select class="form-control" id="line_ecid" name="line_ecid">
                                                            <option value="" {{ isset($line_ecid) ? $line_ecid == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($line_ecid) ? $line_ecid == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($line_ecid) ? $line_ecid == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="greeting_card">賀卡留言:</label>
                                                        <select class="form-control" id="greeting_card" name="greeting_card">
                                                            <option value="" {{ isset($greeting_card) ? $greeting_card == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                            <option value="1" {{ isset($greeting_card) ? $greeting_card == 1 ? 'selected' : '' : '' }}>是</option>
                                                            <option value="x" {{ isset($greeting_card) ? $greeting_card == 'x' ? 'selected' : '' : '' }}>否</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="domain">購買網址:</label>
                                                <select class="form-control" id="domain" name="domain">
                                                    <option value="" {{ isset($domain) ? $domain == '' ? 'selected' : '' : 'selected' }}>不拘</option>
                                                    <option value="icarry.me" {{ isset($domain) ? $domain == 'icarry.me' ? 'selected' : '' : '' }}>icarry.me</option>
                                                    <option value="m.icarry.me" {{ isset($domain) ? $domain == 'm.icarry.me' ? 'selected' : '' : '' }}>m.icarry.me</option>
                                                    <option value="banman" {{ isset($domain) ? $domain == 'banman' ? 'selected' : '' : '' }}>banman.icarry.me</option>
                                                </select>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="user_memo">備註搜尋: (有輸入內容則無法勾選有備註)</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="memo" name="memo" placeholder="使用者或管理員備註" value="{{ isset($memo) ? $memo ?? '' : '' }}" autocomplete="off" />
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">有備註</span>
                                                    </div>
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <input type="checkbox" id="is_memo" name="is_memo" value="1" {{ isset($is_memo) ? $is_memo == 1 ? 'checked' : '' : '' }}>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="promotion_code">折扣代碼查詢:</label>
                                                <input type="text" class="form-control" id="promotion_code" name="promotion_code" placeholder="輸入折扣代碼如MC" value="{{ isset($promotion_code) ? $promotion_code ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="shipping_vendor_name">物流商查詢:</label>
                                                <select class="form-control" id="shipping_vendor_name" name="shipping_vendor_name" id="shipping_vendor_name">
                                                    <option value="" {{ isset($shipping_vendor_name) ? $shipping_vendor_name == '' ? 'selected' : '' : '' }}>全部</option>
                                                    @foreach($shippingVendors as $shippingVendor)
                                                    <option value="{{ $shippingVendor->name }}" {{ isset($shipping_vendor_name) ? $shipping_vendor_name == $shippingVendor->name ? 'selected' : '' : '' }}>{{ $shippingVendor->name }}</option>
                                                    @endforeach
                                                    <option value="未分類" {{ isset($shipping_vendor_name) ? $shipping_vendor_name == '未分類' ? 'selected' : '' : '' }}>未分類</option>
                                                    <option value="含多筆運單之訂單" {{ isset($shipping_vendor_name) ? $shipping_vendor_name == '含多筆運單之訂單' ? 'selected' : '' : '' }}>含多筆運單之訂單</option>
                                                </select>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="pay_method">金流方式: (ctrl+點選可多選)(再次ctrl+點擊可取消)</label>
                                                <select class="form-control" id="source" size="8" multiple>
                                                    <option class="text-danger" value="iCarry" {{ !empty($source) && in_array('iCarry',explode(',',$source)) ? 'selected' : '' }}>iCarry Web (包含多項金流)</option>
                                                    <option class="text-danger" value="skm" {{ !empty($source) && in_array('skm',explode(',',$source)) ? 'selected' : '' }}>新光三越 (全部店家,不含總店)</option>
                                                    @foreach($sources as $s)
                                                    <option value="{{ $s->source }}" {{ !empty($source) && in_array($s->source,explode(',',$source)) ? 'selected' : '' }}>{{ $s->name }}</option>
                                                    @endforeach
                                                </select><input type="hidden" value="" name="source" id="source_hidden" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <label for="book_shipping_date">預定出貨日:(有輸入日期則無法勾選未預定)</label>
                                                        <div class="input-group">
                                                            <input type="datetime" class="form-control datepicker" id="book_shipping_date" name="book_shipping_date" placeholder="格式：2016-06-06" value="{{ isset($book_shipping_date) ? $book_shipping_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="datetime" class="form-control datepicker" id="book_shipping_date_end" name="book_shipping_date_end" placeholder="格式：2016-06-06" value="{{ isset($book_shipping_date_end) ? $book_shipping_date_end ?? '' : '' }}" autocomplete="off" />
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">未預定</span>
                                                            </div>
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">
                                                                    <input type="checkbox" id="book_shipping_date_not_fill" name="book_shipping_date_not_fill" value="1" {{ isset($book_shipping_date_not_fill) ? $book_shipping_date_not_fill == 1 ? 'checked' : '' : '' }}>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mt-2">
                                                        <label for="vendor_arrival_date">廠商到貨日:(有輸入日期則無法勾選未預定)</label>
                                                        <div class="input-group">
                                                            <input type="datetime" class="form-control datepicker" id="vendor_arrival_date" name="vendor_arrival_date" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date) ? $vendor_arrival_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="datetime" class="form-control datepicker" id="vendor_arrival_date_end" name="vendor_arrival_date_end" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date_end) ? $vendor_arrival_date_end ?? '' : '' }}" autocomplete="off" />
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">未預定</span>
                                                            </div>
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">
                                                                    <input type="checkbox" id="vendor_arrival_date_not_fill" name="vendor_arrival_date_not_fill" value="1" {{ isset($vendor_arrival_date_not_fill) ? $vendor_arrival_date_not_fill == 1 ? 'checked' : '' : '' }}>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mt-2">
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
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                        <button type="button" id="search" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                        <button type="button" class="btn btn-success moreOption">更多選項</button>
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                        @if(count($orders) > 0)
                            <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="25%">訂單資訊 / 訂單狀態 / 物流及金流</th>
                                            <th class="text-left" width="75%">購買品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                        <tr>
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    <input type="checkbox" class="chk_box_{{ $order->id }}" name="chk_box" value="{{ $order->id }}">
                                                    <a href="{{ route('gate.orders.show', $order->id) }}">
                                                        <span class="text-lg text-bold order_number_{{ $order->id }}">{{ $order->order_number }}</span>
                                                    </a>
                                                    <button class="mr-1 badge badge-sm btn-primary userInfo" value="{{ $order->id }}">訂單資訊</button>
                                                    @if(!empty($order->acOrder) && ($order->acOrderChk > 0))
                                                    <button class="badge badge-sm btn-warning acOrder" value="{{ $order->order_number }}">處理</button>
                                                    @endif
                                                    {{-- @if(count($order->modifyLogs) > 0)
                                                    <button class="badge badge-sm btn-purple modifyInfo" value="{{ $order->id }}">修改紀錄</button>
                                                    @endif --}}
                                                    @if(!empty($order->merged_order))
                                                        <a href="javascript:" class="text-danger" data-toggle="popover" data-content="主:{{ $order->merged_order }}"><i class="fas fa-object-group"></i></a>
                                                    @endif
                                                    @if(count($order->syncedErrors) > 0)
                                                    <span>
                                                        <a href="javascript:" class="badge badge-danger" data-toggle="popover" title="訂單同步異常" data-content="
                                                        <small>
                                                            @foreach($order->syncedErrors as $error)
                                                            @if(strstr($error->error,'商家'))
                                                            {{ $error->error.'('.$error->digiwin_no.')' }}<br>
                                                            @else
                                                            {{ $error->error }}<br>
                                                            @endif
                                                            @endforeach
                                                        </small>
                                                        ">(訂單同步異常)</a>
                                                    </span>
                                                    @endif
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-6 text-warp">
                                                        @if($order->create_time)
                                                        <span class="text-sm">建單：{{ $order->create_time }}</span><br>
                                                        @endif
                                                        @if($order->pay_time)
                                                        <span class="text-sm">付款：{{ $order->pay_time }}</span><br>
                                                        @endif
                                                        @if(empty($order->acOrder) && empty($order->nidinOrder))
                                                            @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                                                            <a href="javascript:" class="text-sm merge_order_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'merge_order','{{ $order->merge_order ?? '' }}',this)">合併：{{ $order->merge_order ?? '' }}</span></a><br>
                                                            @else
                                                            @if($order->merge_order)
                                                            <span class="text-sm merge_order_{{ $order->id }} text-primary">合併：</span><span>{{ $order->merge_order ?? '' }}</span></a><br>
                                                            @endif
                                                        @endif
                                                        @endif
                                                        @if($order->status >= 3 || ($order->status >= 2 && !empty($order->nidinOrder)))
                                                        @if(!empty($order->is_invoice_no))
                                                        <span class="text-sm">發票日期：{{ explode(' ',$order->invoice_time)[0] ?? '' }}</span>
                                                        @if(!strstr($order->is_invoice_no,'廢') && (empty($order->invoiceAllowance) || (!empty($order->invoiceAllowance) && $order->invoiceAllowance->remain_amt != 0)))
                                                        @if(in_array($menuCode.'CI', explode(',',Auth::user()->power)))
                                                        <a href="javascript:allowanceInvoice({{ $order->order_number }},{{ $order->id }})" class="forhide badge badge-warning">折讓</a>
                                                        @endif
                                                        @elseif(strstr($order->is_invoice_no,'廢'))
                                                        <a href="javascript:reopenInvoice({{ $order->id }})" class="forhide badge badge-info">重開</a>
                                                        @endif
                                                        <br><span class="text-sm"><a href="javascript:" onclick="invoiceLog({{ $order->order_number }},{{ $order->id }})">發票號碼</a>：
                                                            @if(strstr($order->is_invoice_no,'廢'))
                                                            <span style="text-decoration:line-through">{{ $order->is_invoice_no ?? '' }}</span>
                                                            @else
                                                            {{ $order->is_invoice_no ?? '' }}
                                                            @endif
                                                        </span>
                                                        @if(!strstr($order->is_invoice_no,'廢'))
                                                        @if(in_array($menuCode.'CI', explode(',',Auth::user()->power)))
                                                        @if(empty($order->invoiceAllowance))
                                                        <a href="javascript:cancelInvoice({{ $order->id }})" class="forhide badge badge-danger">作廢</a>
                                                        @endif
                                                        @endif
                                                        @endif
                                                        @else
                                                        @if(in_array($menuCode.'NI', explode(',',Auth::user()->power)))
                                                        @if(in_array($order->digiwin_payment_id,$allowDigiwinPaymentIds))
                                                        <span class="text-sm">發票：</span>
                                                        <a href="javascript:openInvoice({{ $order->id }})" class="forhide badge badge-purple">開立發票</a>
                                                        @elseif(!empty($order->acOrder) || !empty($order->nidinOrder))
                                                        <span class="text-sm">發票：</span>
                                                        <a href="javascript:acOrderOpenInvoice({{ $order->id }})" class="forhide badge badge-purple">開立發票</a>
                                                        @endif
                                                        @endif
                                                        @endif
                                                        @else
                                                        @if($order->is_ticket_order == 1)
                                                        @if(!empty($order->is_invoice_no))
                                                        <span class="text-sm">發票日期：{{ explode(' ',$order->invoice_time)[0] ?? '' }}</span><br>
                                                        <br>
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
                                                        @if(!empty($order->invoiceAllowance))<br>
                                                        <span class="text-sm">發票折讓號碼：<br>{{ $order->invoiceAllowance->allowance_no }}</span><br>
                                                        <span class="text-sm">發票折讓金額：<span class="badge badge-primary">{{ number_format($order->invoiceAllowance->allowance_amt) }}</span></span>
                                                        @endif
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="status_{{ $order->id }} text-bold">
                                                        @if($order->is_del == 1)
                                                            前台使用者刪除訂單
                                                        @else
                                                            @if($order->status == -1)
                                                            後台取消訂單
                                                            @elseif($order->status == 0)
                                                            尚未付款
                                                            @elseif($order->status == 1)
                                                            已付款待出貨
                                                            @elseif($order->status == 2)
                                                            訂單集貨中
                                                            @elseif($order->status == 3)
                                                            訂單已出貨
                                                            @elseif($order->status == 4)
                                                            訂單已完成
                                                            @endif
                                                            <br>
                                                        @endif
                                                        </span>
                                                        @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                                                            @if(strlen($order->is_call)==8)
                                                            <a href="javascript:" class="forhide badge badge-success is_call_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'is_call','{{ $order->is_call ?? '' }}',this)">{{ $order->is_call ? '已叫貨註記：'.substr($order->is_call,0,4).'/'.substr($order->is_call,4,2).'/'.substr($order->is_call,6,2) : '已叫貨註記：無' }}</a>
                                                            @else
                                                            <a href="javascript:" class="forhide badge badge-success is_call_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'is_call','{{ $order->is_call ?? '' }}',this)">{{ $order->is_call ? '已叫貨註記：'.substr($order->is_call,0,2).'/'.substr($order->is_call,2,2).' 已叫貨' : '已叫貨註記：無'}}</a>
                                                            @endif
                                                            <br>
                                                            @if(strlen($order->is_print)==8)
                                                            <a href="javascript:" class="forhide mt-1 badge badge-info is_print_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'is_print','{{ $order->is_print ?? '' }}',this)">{{ $order->is_print ? '已列印註記：'.substr($order->is_print,0,4).'/'.substr($order->is_print,4,2).'/'.substr($order->is_print,6,2) : '已列印註記' }}</a>
                                                            @else
                                                            <a href="javascript:" class="forhide mt-1 badge badge-info is_print_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'is_print','{{ $order->is_print ?? '' }}',this)">{{ $order->is_print ? '已列印註記：'.substr($order->is_print,0,2).'/'.substr($order->is_print,2,2) : '已列印註記：無'}}</a>
                                                            @endif
                                                            <br>
                                                            <a href="javascript:" class="forhide badge mt-1 badge-success vendor_arrival_date_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'vendor_arrival_date','{{ $order->vendor_arrival_date ?? '' }}',this)">{{ $order->vendor_arrival_date ? '廠商到貨日：'.str_replace('-','/',$order->vendor_arrival_date) : '廠商到貨日：無' }}</a>
                                                            <br>
                                                            <a href="javascript:" class="forhide badge mt-1 badge-purple book_shipping_date_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'book_shipping_date','{{ $order->book_shipping_date ?? '' }}',this)">{{ $order->book_shipping_date ? '預定出貨日：'.str_replace('-','/',$order->book_shipping_date) : '預定出貨日：無' }}</a>
                                                            <hr class="forhide mt-1 mb-0">
                                                            <a href="javascript:" class="forhide badge mt-1 badge-danger sync_date_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'sync_date','{{ $order->syncDate ?? '' }}',this)">{{ !empty($order->syncDate) ? '已同步鼎新：'.$order->syncDate : '已同步鼎新：無' }}</a>
                                                        @else
                                                            @if($order->is_call)
                                                            @if(strlen($order->is_call)==8)
                                                            <span class="forhide badge badge-success is_call_{{ $order->id }}">{{ $order->is_call ? '已叫貨註記：'.substr($order->is_call,0,4).'/'.substr($order->is_call,4,2).'/'.substr($order->is_call,6,2) : '已叫貨註記：無' }}</span>
                                                            @elseif($order->is_call != '')
                                                            <span class="forhide badge badge-success is_call_{{ $order->id }}">{{ $order->is_call ? '已叫貨註記：'.substr($order->is_call,0,2).'/'.substr($order->is_call,2,2) : '已叫貨註記：無'}}</span>
                                                            <br>
                                                            @endif
                                                            @endif
                                                            @if($order->is_print)
                                                            @if(strlen($order->is_print)==8)
                                                            <span class="forhide mt-1 badge badge-info is_print_{{ $order->id }}">{{ $order->is_print ? '已列印註記：'.substr($order->is_print,0,4).'/'.substr($order->is_print,4,2).'/'.substr($order->is_print,6,2) : '已列印註記：無' }}</span>
                                                            @else
                                                            <span class="forhide mt-1 badge badge-info is_print_{{ $order->id }}">{{ $order->is_print ? '已列印註記：'.substr($order->is_print,0,2).'/'.substr($order->is_print,2,2) : '已列印註記：無'}}</span>
                                                            <br>
                                                            @endif
                                                            @endif
                                                            <span class="forhide mt-1 badge badge-success vendor_arrival_date{{ $order->id }}">{{ $order->vendor_arrival_date ? '廠商到貨日：'.str_replace('-','/',$order->vendor_arrival_date) : '廠商到貨日：無' }}</span>
                                                            <span class="forhide mt-1 badge badge-purple book_shipping_date_{{ $order->id }}">{{ $order->book_shipping_date ? '預定出貨日：'.str_replace('-','/',$order->book_shipping_date) : '預定出貨日：無' }}</span>
                                                            <hr class="forhide mt-1 mb-0">
                                                            <span class="forhide mt-1 badge badge-danger sync_date_{{ $order->id }}">{{ !empty($order->syncDate) ? '已同步鼎新：'.$order->syncDate : '已同步鼎新：無' }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row mb-1">
                                                    @if(empty($order->acOrder) && empty($order->nidinOrder))
                                                    <div class="col-6 text-sm">
                                                        物流：<span class="badge badge-primary">
                                                            @if($order->shipping_method == 1)
                                                                機場提貨
                                                            @elseif($order->shipping_method == 2)
                                                                旅店提貨
                                                            @elseif($order->shipping_method == 3)
                                                                現場提貨
                                                            @else
                                                            @if($order->ship_to)
                                                                @if($order->origin_country == $order->ship_to)
                                                                {{ $order->origin_country }}寄送當地
                                                                @else
                                                                {{ $order->origin_country }}寄送{{ $order->ship_to }}
                                                                @endif
                                                                @else
                                                                {{ $order->pay_method }}
                                                            @endif
                                                            @endif
                                                            </span><br>
                                                        @if(!empty($order->receiver_key_time))
                                                        提貨：{{ $order->receiver_key_time }}<br>
                                                        @endif
                                                        國家：<span class="badge badge-warning">{{ $order->ship_to }}</span>
                                                        @if($order->status == 1 || $order->status == 2)
                                                            @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                                                            <br><a href="javascript:" class="text-sm shipping_number_{{ $order->id }}" onclick="modify('{{ $order->shipping_number }}',{{ $order->id }},'shipping_number','{{ $order->shipping_number ?? '' }}',this)">物流單號：
                                                                @if(!empty($order->shipping_number))
                                                                @foreach(explode(',',$order->shipping_number) as $key => $value)
                                                                <br>{{ $value }}
                                                                @endforeach
                                                                @endif
                                                            </a>
                                                            @else
                                                                @if(!empty($order->shipping_number))
                                                                <br><span class="text-sm shipping_number_{{ $order->id }} text-primary">物流單號：</span>
                                                                    @foreach(explode(',',$order->shipping_number) as $key => $value)
                                                                    <br>{{ $value }}
                                                                    @endforeach
                                                                @endif
                                                            @endif
                                                        @else
                                                            @if(!empty($order->shipping_number))
                                                                <br>物流單號：
                                                                @foreach(explode(',',$order->shipping_number) as $key => $value)
                                                                <br>{{ $value }}
                                                                @endforeach
                                                            @endif
                                                        @endif
                                                    </div>
                                                    @else
                                                    @if(!empty($order->acOrder))
                                                    <div class="col-6 text-sm">
                                                    <span class="text-sm">串接資料：<a href="javascript:" class="badge badge-danger" data-toggle="popover" title="{{ $order->acOrder->serial_no }}" data-content="
                                                    <small>
                                                        總金額：{{ number_format($order->acOrder->amount) ?? 0 }}<br>
                                                        已同步：{{ $order->acOrder->is_sync == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已出貨：{{ $order->acOrder->is_sell == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已採購：{{ !empty($order->acOrder->purchase_id) ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        採購同步：{{ $order->acOrder->purchase_sync == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已入庫：{{ $order->acOrder->is_stockin == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已開發票：{{ $order->acOrder->is_invoice == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        @if(!empty($order->acOrder->return_date))退貨日期：{{ $order->acOrder->return_date }}@endif<br>
                                                    </small>
                                                    ">{{ $order->acOrder->serial_no }}</a></span>
                                                    </div>
                                                    @elseif(!empty($order->nidinOrder))
                                                    <div class="col-6 text-sm">
                                                    <span class="text-sm">串接資料：<a href="javascript:" class="badge badge-danger" data-toggle="popover" title="{{ $order->nidinOrder->serial_no }}" data-content="
                                                    <small>
                                                        總金額：{{ number_format($order->nidinOrder->amount) ?? 0 }}<br>
                                                        已同步：{{ $order->nidinOrder->is_sync == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已出貨：{{ $order->nidinOrder->is_sell == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已採購：{{ !empty($order->nidinOrder->purchase_id) ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        採購同步：{{ $order->nidinOrder->purchase_sync == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已入庫：{{ $order->nidinOrder->is_stockin == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        已開發票：{{ $order->nidinOrder->is_invoice == 1 ? '是' : '<span class="text-danger">否</span>' }}<br>
                                                        @if(!empty($order->nidinOrder->return_date))退貨日期：{{ $order->nidinOrder->return_date }}@endif<br>
                                                    </small>
                                                    ">{{ $order->nidinOrder->serial_no }}</a></span>
                                                    </div>
                                                    @endif
                                                    @endif
                                                    <div class="col-6 text-sm">
                                                        @if($order->pay_method)
                                                        <span>金流：<a href="javascript:" class="badge badge-danger" data-toggle="popover" title="訂單 {{ $order->order_number }}" data-content="
                                                            <small>
                                                                總價：{{ number_format($order->amount) ?? 0 }}<br>
                                                                運費：{{ number_format($order->shipping_fee) ?? 0 }}<br>
                                                                跨境稅：{{ number_format($order->parcel_tax) ?? 0 }}<br>
                                                                使用購物金：<span class='text-danger'>{{ $order->spend_point != 0 ? '-' : ''}}{{ number_format($order->spend_point) ?? 0 }}</span><br>
                                                                折扣：<span class='text-danger'>{{ $order->discount != 0 ? '-' : ''}}{{ number_format($order->discount) ?? 0 }}</span><hr>
                                                                貨幣匯率：{{ $order->exchange_rate ?? 1 }}<br>
                                                                回饋購物金：{{ number_format($order->get_point) ?? 0 }}<hr>
                                                                金流支付：{{ number_format($order->total_pay) ?? 0 }}
                                                            </small>
                                                            ">{{ $order->pay_method }}</a></span>
                                                        @endif
                                                        <span><span class="badge badge-purple">{{ number_format($order->total_pay) }} 元</span></span>
                                                        @if(count($order->allowances) > 0)
                                                        <br>鼎新折讓單單號：<br>
                                                        @foreach($order->allowances as $allowance)
                                                        <span class="badge badge-primary">{{ $allowance->erp_return_no }}</span>
                                                        @endforeach
                                                        <br>
                                                        @endif
                                                        @if(count($order->sellReturns) > 0)
                                                        <br>鼎新銷退單單號：<br>
                                                        @foreach($order->sellReturns as $return)
                                                        <span class="badge badge-primary">{{ $return->erp_return_no }}</span>
                                                        @endforeach
                                                        @endif
                                                    </div>
                                                </div>
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
                                                        <th width="5%" class="text-right align-middle text-sm">出貨</th>
                                                        <th width="5%" class="text-right align-middle text-sm">退貨</th>
                                                        <th width="8%" class="text-right align-middle text-sm">物流</th>
                                                        <th width="5%" class="text-right align-middle text-sm">總價</th>
                                                    </thead>
                                                    <tbody>
                                                        <form id="itemsform_order_{{ $order->id }}" method="POST">
                                                            @foreach($order->items as $item)
                                                            <tr>
                                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }} {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ $item->vendor_name }}
                                                                    @if(!empty($item->syncedOrderItem['purchase_no']))
                                                                    <span data-toggle="popover" class="text-primary syncedOrderItem_{{ $item->syncedOrderItem['id'] }}" data-content="
                                                                        <small>
                                                                            採購單號：{{ $item->syncedOrderItem['purchase_no'] }}<br>
                                                                            採購日期：{{ $item->syncedOrderItem['purchase_date'] }}<br>
                                                                            到貨日期：{{ $item->syncedOrderItem['vendor_arrival_date'] }}<br>
                                                                            @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                                            <button class='btn btn-outline-secondary btn-xs' onclick='purchaseCancel({{ $item->syncedOrderItem['id'] }})'>移除</button>
                                                                            @endif
                                                                        </small>
                                                                        "><i class="fas fa-store-alt" title="採購資料"></i></span>
                                                                    @else
                                                                    <i class="fas fa-store-alt-slash {{ $item->not_purchase == 1 ? 'active' : '' }}"    {{ in_array($menuCode.'MK', explode(',',Auth::user()->power)) ? 'onclick=notPurchase('.$item->id.',this)' : '' }}></i>
                                                                    @endif
                                                                    @if($item->direct_shipment == 1)
                                                                    <span class="text-primary "><i class="fas fa-truck" title="廠商直寄"></i></span>
                                                                    @endif
                                                                    @if(!empty($item->vendor_shipping_no))
                                                                    @if($item->is_del == 0)
                                                                    <span data-toggle="popover" class="text-danger" data-content="商家出貨單號：{{ $item->vendor_shipping_no }}"><i class="fas fa-tags" title="商家出貨單"></i></span>
                                                                    @endif
                                                                    @endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ $item->digiwin_no }}
                                                                    @if(count($item->tickets) > 0)
                                                                    <span data-toggle="popover" class="text-primary" data-content="
                                                                        <small>
                                                                            @foreach($item->tickets as $ticket)
                                                                            票券號碼：{{ $ticket->ticket_no_mask }}　狀態：
                                                                            @if($ticket->status == -1)
                                                                            已作廢
                                                                            @elseif($ticket->status == 0)
                                                                            未銷售
                                                                            @elseif($ticket->status == 1)
                                                                            <span class='text-info'>已銷售</span>
                                                                            @elseif($ticket->status == 2)
                                                                            <span class='text-success'>已結帳</span>
                                                                            @elseif($ticket->status == 9)
                                                                            <span class='text-primary'>已核銷</span>
                                                                            @endif
                                                                            <br>
                                                                            @endforeach
                                                                        </small>
                                                                        "><i class="fas fa-ticket-alt" title="票券資料"></i></span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ $item->product_name }}
                                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已下架)</span>@endif
                                                                </td>
                                                                <td class="text-center align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ $item->unit_name }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->gross_weight) }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->gross_weight * $item->quantity) }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ round($item->price,2) }}</td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    {{ number_format($item->quantity) }}
                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    @if(count($item->sells) > 0)
                                                                    <span class="text-primary" data-toggle="popover" title="銷貨資訊" data-placement="top" id="item_sell_{{ $item->id }}" data-content="
                                                                        @foreach($item->sells as $sell)
                                                                            銷貨單號：{{ $sell->sell_no }} 數量：{{ $sell->sell_quantity }}<br>
                                                                        @endforeach
                                                                        ">{{ number_format($item->sell_quantity) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    @if(count($item->returns) > 0)
                                                                    <span class="text-danger" data-toggle="popover" title="銷退資訊" data-placement="top" id="item_sell_{{ $item->id }}" data-content="
                                                                        @foreach($item->returns as $return)
                                                                            銷退單號：{{ $return->return_no }}<br>
                                                                        @endforeach
                                                                        ">-{{ number_format($item->return_quantity) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                                        @if($item->shipping_memo == null)
                                                                        <a href="javascript:" onclick="pickupShipping({{ $item->id }})">挑選</a>
                                                                        @else
                                                                        <a href="javascript:" onclick="pickupShipping({{ $item->id }})">{{ $item->shipping_memo }}</a>
                                                                        @endif
                                                                    @else
                                                                        @if($item->shipping_memo == null)
                                                                        尚未挑選
                                                                        @else
                                                                        {{ $item->shipping_memo }}
                                                                        @endif
                                                                    @endif
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
                                                                                    <th colspan="2" width="12%" class="text-right align-middle text-sm" style="border: none; outline: none">iCarry單價</th>
                                                                                    <th width="6%" class="text-right align-middle text-sm" style="border: none; outline: none">拆分<br>單價</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">出貨</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="8%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">總價</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($item->split as $packageItem)
                                                                                <tr>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</td>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</td>
                                                                                    <td class="text-center align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</td>
                                                                                    <td colspan="2" class="text-right align-middle text-sm">{{ $packageItem['origin_price'] }}</td>
                                                                                    <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['price'] }}</td>
                                                                                    <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">
                                                                                        {{ number_format($packageItem['quantity']) }}
                                                                                    </td>
                                                                                    <td width="5%" class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">
                                                                                        @if(!empty($packageItem['sell_no']))
                                                                                        <span class="text-primary" data-toggle="popover" title="銷貨資訊" data-placement="top" id="item_sell_{{ $item->id }}" data-content="
                                                                                                銷貨單號：{{ $packageItem['sell_no'] }}
                                                                                            ">{{ number_format($packageItem['sell_quantity']) }}</span>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                                    <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                                    <td width="5%" class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['total'] }}</td>
                                                                                </tr>
                                                                                @endforeach
                                                                                <tr>
                                                                                    <td colspan="5" class="text-right align-middle text-sm"></td>
                                                                                    <td class="text-right align-middle text-sm"></td>
                                                                                    <td class="text-right align-middle text-sm">數量小計</td>
                                                                                    <td class="text-right align-middle text-sm">{{ $item->splitCount }}</td>
                                                                                    <td colspan="3" class="text-right align-middle text-sm"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                @endif
                                                            @endif
                                                            @endforeach
                                                            <tr>
                                                                <td colspan="1" class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">運費 {{ number_format($order->shipping_fee) }}</td>
                                                                <td colspan="2" class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">使用購物金 {{ number_format($order->spend_point) }}　　跨境稅 {{ $order->parcel_tax ?? 0 }}　　折扣 {{ number_format($order->discount) }}</td>
                                                                <td colspan="1" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">總重</td>
                                                                <td colspan="1" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalWeight) }}</td>
                                                                <td colspan="{{ $order->packageCount > 0 ? 3 : 2 }}" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">商品總計</td>
                                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalQty) }}</td>
                                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ $order->totalSellQty != 0 ? number_format($order->totalSellQty) : null }}</td>
                                                                <td colspan="2" class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;"></td>
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
                                                <a href="javascript:" class="text-sm admin_memo_{{ $order->id }}" onclick="modify({{ $order->order_number }},{{ $order->id }},'admin_memo','{{ $order->admin_memo ?? '' }}',this)">管理者：{{ $order->admin_memo ?? '' }}</span></a>
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
    <form id="multiProcessForm" action="{{ url('orders/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="mark" action="{{ url('orders/modify') }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('modal')
{{-- 註記 Modal --}}
<div id="modifyModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyModalLabel">訂單修改紀錄</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
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
                            <tbody id="modifyRecord"></tbody>
                        </table>
                    </div>
                </div>
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

{{-- 同步紀錄 Modal --}}
<div id="syncModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="10%" class="text-left">同步時間</th>
                            <th width="10%" class="text-left">管理者</th>
                            <th width="10%" class="text-left">預定出貨日</th>
                            <th width="10%" class="text-left">廠商到貨日</th>
                            <th width="8%" class="text-left">狀態</th>
                            <th width="8%" class="text-right">商品金額</th>
                            <th width="8%" class="text-right">商品數量</th>
                            <th width="8%" class="text-right">運費</th>
                            <th width="8%" class="text-right">行郵稅</th>
                            <th width="8%" class="text-right">活動折扣</th>
                            <th width="8%" class="text-right">使用購物金</th>
                        </tr>
                    </thead>
                    <tbody id="syncRecord"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 發票紀錄 Modal --}}
<div id="invoiceModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="15%" class="text-left">時間</th>
                            <th width="10%" class="text-left">類別</th>
                            <th width="40%" class="text-left">說明</th>
                            <th width="15%" class="text-left">發票號碼</th>
                            <th width="15%" class="text-left">舊訂單號碼</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceRecord"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            @if(in_array($menuCode.'SY', explode(',',Auth::user()->power)))
                            <li class="nav-item"><a class="nav-link active" href="#tab-sync" data-toggle="tab">訂單處理</a></li>
                            @endif
                            @if(in_array($menuCode.'EX', explode(',',Auth::user()->power)))
                            <li class="nav-item"><a class="nav-link" href="#tab-export" data-toggle="tab">訂單匯出</a></li>
                            @endif
                            @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                            <li class="nav-item"><a class="nav-link" href="#tab-note" data-toggle="tab">訂單註記</a></li>
                            @endif
                            @if(in_array($menuCode.'PR', explode(',',Auth::user()->power)))
                            <li class="nav-item"><a class="nav-link" href="#tab-print" data-toggle="tab">訂單列印</a></li>
                            @endif
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="tab-sync">
                                <div>
                                    @if(in_array($menuCode.'SY', explode(',',Auth::user()->power)))
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="Synchronize">同步至鼎新</button>
                                    @endif
                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Purchase">建立採購單</button>
                                    {{-- <button class="btn btn-sm btn-danger multiProcess mr-2" value="Shipping">挑選物流</button> --}}
                                    <a href="javascript:" class="btn btn-sm btn-danger mr-2" onclick="pickupShipping()">挑選物流</a>
                                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="CheckOrder">檢查出貨並更新狀態</button>
                                    @endif
                                    @if(in_array($menuCode.'NI', explode(',',Auth::user()->power)))
                                    <button class="btn btn-sm btn-purple multiProcess mr-2" value="invoice_create">開立發票</button>
                                    @endif
                                </div>
                            </div>
                            <div class="tab-pane" id="tab-export">
                                <div>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="excel_OrderDetail">訂單明細</button>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="excel_OrderNoprice">訂單明細(無金額)</button>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="excel_InvoiceCN">中文發票</button>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="excel_InvoiceEN">英文發票</button>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="excel_InvoiceLowprice">低報發票</button>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="excel_Return">退貨明細</button>
                                    <button class="btn btn-sm btn-success multiProcess mr-2" value="excel_OrderInvoice">訂單發票明細</button>
                                </div>
                                <hr>
                                <div>
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_Ecan">宅配通物流(機場)</button>
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_Blackcat">黑貓物流(台灣)</button>
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_SFTaiwan">順豐物流(台灣)</button> --}}
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_SFSpeedType">順豐速打單</button> --}}
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_Linex">Linex物流</button> --}}
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_GoodMaji">好馬吉物流</button> --}}
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_DHL">DHL物流</button> --}}
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_DHLnew">DHL物流(新)</button>
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_SFXinZhuang">順豐新莊物流</button>
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_Ubonex">優邦(中國)物流</button> --}}
                                    {{-- <button class="btn btn-sm btn-danger multiProcess mr-2 mt-1 mb-1" value="pdf_Screenshot">訂單截圖(PDF)</button> --}}
                                </div>
                                <hr>
                                <div>
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_SFOld">[國際條碼]順豐出貨單(OLD)</button>
                                    {{-- <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_SFHandwrite">[國際條碼]順豐出貨單</button> --}}
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_Warehousing">庫存管理表(合併數量)</button>
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_WarehousingPreDelivery">庫存管理表(不合併數量)</button>
                                    <button class="btn btn-sm btn-info multiProcess mr-2 mt-1 mb-1" value="shipping_WarehousingShipment">庫存管理表(合併數量)(物流)</button>
                                </div>
                                <hr>
                                <div>
                                    <button class="btn btn-sm btn-primary multiProcess mr-2 mt-1 mb-1" value="getOrderDate">判斷出貨日</button>
                                    <button class="btn btn-sm btn-warning multiProcess mr-2 mt-1 mb-1" value="excel_GreetingCard">賀卡留言匯出</button>
                                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="excel_Asiamiles">Asiamiles匯出</button>
                                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="excel_Shopcom">美安匯出</button>
                                    <button class="btn btn-sm btn-warning multiProcess mr-2 mt-1 mb-1" value="excel_Digiwin">鼎新匯出(檔案)</button>
                                    <button class="btn btn-sm btn-success multiProcess mr-2 mt-1 mb-1" value="shipping_Express">訂單物流匯出</button>
                                </div>
                            </div>
                            <div class="tab-pane" id="tab-note">
                                <button class="mt-1 mb-1 btn btn-sm btn-purple orderMark" value="book_shipping_date"><span>預定出貨日</span></button>
                                <button class="mt-1 mb-1 btn btn-sm btn-success orderMark" value="is_call"><span>已叫貨註記</span></button>
                                <button class="mt-1 mb-1 btn btn-sm btn-info orderMark" value="is_print"><span>已列印註記</span></button>
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-secondary orderMark" value="shipping_time"><span>已出貨註記</span></button> --}}
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-secondary orderMark" value="receiver_key_time"><span>提貨日註記</span></button> --}}
                                <button class="mt-1 mb-1 btn btn-sm btn-primary orderMark" value="admin_memo"><span>管理員註記</span></button>
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-info orderMark" value="shipping_memo_vendor"><span>選擇物流商</span></button> --}}
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-info orderMark" value="buy_memo"><span>採購日註記</span></button> --}}
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-info orderMark" value="billOfLoading_memo"><span>提單日註記</span></button> --}}
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-info orderMark" value="new_shipping_memo"><span>物流日註記</span></button> --}}
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-info orderMark" value="special_memo"><span>特殊註記</span></button> --}}
                                {{-- <button class="mt-1 mb-1 btn btn-sm btn-warning orderMark" value="order_item_modify" id="oit"><span>商品叫貨註記</span></button> --}}
                                <button class="btn btn-sm btn-danger multiProcess mr-2" value="RemovePurchase">移除採購註記</button>
                                <button class="btn btn-sm btn-warning multiProcess mr-2" value="addNotPurchase">不採購註記</button>
                            </div>
                            <div class="tab-pane" id="tab-print">
                                <div>
                                    <button class="btn btn-sm btn-info multiProcess" value="excel_Pickup">列印撿貨單</button>
                                    <button class="btn btn-sm btn-danger multiProcess" value="pdf_Pickup">列印撿貨單(PDF)</button>
                                    <button class="btn btn-sm btn-primary multiProcess" value="excel_PurchaseCall">採購叫貨單</button>
                                </div>
                                <hr>
                                <span>物流單</span>
                                <div class="mt-1">
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_BlackcatShipping">黑貓宅急便</button>
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_EcanShipping">台灣宅配通</button>
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_Scooter">巨邦機車快遞</button>
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_Luggage">行李特工</button>
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_SF2">順豐速運V2</button>
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_PSE">沛羽國際通運</button>
                                    <button class="btn btn-sm btn-success multiProcess" value="shipping_Shopee">蝦皮便利商店</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 採購 Modal --}}
<div id="purchaseModel" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;height: 95%;">
        <div class="modal-content" style="max-height: 95%;">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseModalLabel">選擇要採購的商品</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body table-responsive" style="max-height: calc(100% - 60px);overflow-y: auto;">
                <span class="text-danger mb-1">注意! 建立採購單前請確認已經執行同步與鼎新匯出功能。</span>
                <table width="100%" id="purchaseTable" class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th width="10%" class="text-left">廠商到貨日</th>
                            <th width="10%" class="text-left">採購品號</th>
                            <th width="15%" class="text-left">廠商名稱</th>
                            <th width="20%" class="text-left">品名</th>
                            <th width="5%" class="text-center">廠商直寄</th>
                            <th width="8%" class="text-right">採購價</th>
                            <th width="7%" class="text-right">數量</th>
                            <th width="5%" class="text-center"></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div>
                <div class="m-3 float-left">
                    <button class="btn btn-sm btn-primary purchaseProcess" value="all">全部採購</button>
                    <h6><span class="text-danger">全部採購將不會選擇 </span><a href="{{ url('excludeProducts') }}" target="_blank">採購排除商品管理</a><span class="text-danger"> 內的指定商品。</span></h6>
                </div>
                <div class="m-3 float-right">
                    <button class="btn btn-sm btn-secondary" id="cancelAll">取消全部</button>
                    <button class="btn btn-sm btn-success" id="selectAll">全選</button>
                    <button class="btn btn-sm btn-primary purchaseProcess" value="selected">送出勾選</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 物流 Modal --}}
<div id="shippingModel" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 80%;height: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shippingModalLabel">選擇物流</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body table-responsive">
                <form id="pickupShippingForm" action="{{ url('orders/multiProcess') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group clearfix">
                                <div class="icheck-primary">
                                    <input type="radio" id="type1" name="type" value="依系統設定">
                                    <label for="type1">依系統設定</label><a href="javascript:" id="showShippingNote">(註)</a>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type2" name="type" value="自行挑選">
                                    <label for="type2">自行挑選</label>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type3" name="type" value="廠商發貨">
                                    <label for="type3">廠商直送</label>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type5" name="type" value="電子郵件">
                                    <label for="type5">電子郵件</label>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type4" name="type" value="移除物流">
                                    <label for="type4">移除物流</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-9">
                            <div id="shippingNote" style="border:1px solid red;display:none">
                                M欄 貨運別 判斷規則如下：<br />
                                <ol>
                                    <li>01 台灣宅配通：<br />
                                    iCarry 官網訂單 AND 機場提貨</li>

                                    <li>02 順豐速運：<br />
                                    任何訂單 AND ( 旅店提貨 OR 寄送當地 OR 寄送台灣 ) AND 順豐速打單非紅色標示<br />
                                    蝦皮訂單 AND 寄送台灣 AND 備註包括 (台灣) AND 地址不包括 “全家” OR “7-11” AND 順豐速打單非紅色標示<br />
                                    Asiamiles 訂單 AND 寄送海外 AND 地址出現（香港 OR 澳門）<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（香港 OR 澳門  ）</li>
                                    <li>02 順豐-中國：<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（中國 ）</li>
                                    <li>02 順豐-日本：<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（日本） </li>

                                    <li>03 DHL：<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（美國 OR 加拿大 OR 澳洲 OR 紐西蘭 OR 南韓）</li>

                                    <li>04 LINEX-新加坡：<br />
                                    所有訂單 AND 寄送海外 AND 地址出現 新加坡 <br />
                                    蝦皮訂單 AND 寄送海外 AND 備註包括 (新加坡)</li>
                                    <li>04 LINEX-馬來西亞：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 馬來西亞</li>
                                    <li>04 LINEX-法國：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 法國</li>
                                    <li>04 LINEX-越南：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 越南</li>
                                    <li>04 LINEX-泰國：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 泰國</li>

                                    <li>06 黑貓宅急便：<br />
                                    iCarry 官網訂單 AND ( 旅店提貨 OR 寄送當地 ) AND 順豐速打單為紅色標示
                                    蝦皮訂單 AND 寄送台灣 AND 備註包括 (台灣) AND 地址不包括 “全家” OR “7-11” AND 順豐速打單為紅色標示</li>

                                    <li>08 MOMO-宅配通：<br />
                                    momo 匯入訂單</li>

                                    <li>09 7-11 大智通：<br />
                                    蝦皮訂單 AND (寄送台灣 OR 寄送當地) AND 備註包括 (台灣) AND 地址包括 “7-”<br />
                                    松果訂單 AND 寄送當地 AND 地址包括 “台灣 7-11”</li>

                                    <li>10 全家 日翊：<br />
                                    蝦皮訂單 AND (寄送台灣 OR 寄送當地) AND 備註包括 (台灣) AND 地址包括 “全家”<br />
                                    松果訂單 AND 寄送當地 AND 地址包括 “全家”</li>
                                    <li>11 萊爾富：<br />
                                    蝦皮訂單 AND 寄送台灣 AND 備註包括 (台灣) AND 地址包括 “萊爾富”</li>
                                    <li>12 票券：<br />
                                    電子郵件</li>
                                </ol>
                            </div>
                            <div id="shippingVendor" style="border:1px solid blue;display:none">
                                <div class="row">
                                    @foreach($shippingVendors as $shippingVendor)
                                    @if($shippingVendor->name != '廠商發貨')
                                    <div class="icheck-success col-3">
                                        <input type="radio" id="shippingMemo{{ $shippingVendor->id }}" name="shippingMemo" value="{{ $shippingVendor->name }}">
                                        <label for="shippingMemo{{ $shippingVendor->id }}">{{ $shippingVendor->name }}</label>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">確定</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">取消</span>
                            </button>
                        </div>
                    </div>
                </form>
                <div class="form-group form-group-sm d-none" id="myShippingRecord">
                    <hr>
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
                                <tbody id="shippingRecord"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 匯入訂單 Modal --}}
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
                <form  id="importForm" action="{{ url('orders/import') }}" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="cate" value="orders">
                    @csrf
                    <div class="form-group">
                        <div class="mb-3">
                            @foreach($imports as $key => $value)
                            @if($value == '宜睿匯入' || $value == 'MOMO匯入' || $value == '鼎新訂單匯入')
                            <div class="icheck-primary d-inline mb-3">
                                <input type="radio" class="mb-3" id="radio_{{ $key }}" name="type" value="{{ $value }}" required>
                                <label for="radio_{{ $key }}" class="mb-3 mr-3">{{ $value }}</label>
                            </div>
                            @elseif($value == '物流單號匯入')
                            @if(in_array($menuCode.'IMS', explode(',',Auth::user()->power)))
                            <div class="icheck-primary d-inline mb-3">
                                <input type="radio" class="mb-3" id="radio_{{ $key }}" name="type" value="{{ $value }}" required>
                                <label for="radio_{{ $key }}" class="mb-3 mr-3">{{ $value }}</label>
                            </div>
                            @endif
                            @elseif($value == '批次修改管理員備註')
                            @if(in_array($menuCode.'IMP', explode(',',Auth::user()->power)))
                            <div class="icheck-primary d-inline mb-3">
                                <input type="radio" class="mb-3" id="radio_{{ $key }}" name="type" value="{{ $value }}" required>
                                <label for="radio_{{ $key }}" class="mb-3 mr-3">{{ $value }}</label>
                            </div>
                            @endif
                            @elseif($value == '訂單在途存貨')
                            @if(in_array($menuCode.'IMT', explode(',',Auth::user()->power)))
                            <div class="icheck-primary d-inline mb-3">
                                <input type="radio" class="mb-3" id="radio_{{ $key }}" name="type" value="{{ $value }}" required>
                                <label for="radio_{{ $key }}" class="mb-3 mr-3">{{ $value }}</label>
                            </div>
                            @endif
                            @endif
                            @endforeach
                        </div>
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
                    <span class="text-danger">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考下面範例，製作正確的檔案。</span>
                    <div>
                        @foreach($imports as $key => $value)
                        @if($value == '宜睿匯入')
                        <a href="{{ 'https://'.env('GATE_DOMAIN').'/sample/'.$value.'.html' }}" class="mb-3 mr-3" target="_blank">{{ $value }}</a>
                        @elseif($value == '物流單號匯入')
                        @if(in_array($menuCode.'IMS', explode(',',Auth::user()->power)))
                        <a href="{{ 'https://'.env('GATE_DOMAIN').'/sample/物流單號匯入範例.xlsx' }}" class="mb-3 mr-3" target="_blank">物流單號匯入範例</a>
                        @endif
                        @elseif($value == '批次修改管理員備註')
                        @if(in_array($menuCode.'IMP', explode(',',Auth::user()->power)))
                        <a href="{{ 'https://'.env('GATE_DOMAIN').'/sample/批次修改管理員備註.xlsx' }}" class="mb-3 mr-3" target="_blank">批次修改管理員備註範例</a>
                        @endif
                        @elseif($value == '訂單在途存貨')
                        @if(in_array($menuCode.'IMT', explode(',',Auth::user()->power)))
                        <a href="{{ 'https://'.env('GATE_DOMAIN').'/sample/訂單在途存貨範例.xlsx' }}" class="mb-3 mr-3" target="_blank">訂單在途存貨範例</a>
                        @endif
                        @else
                        <a href="{{ 'https://'.env('GATE_DOMAIN').'/sample/'.$value.'.xlsx' }}" class="mb-3 mr-3" target="_blank">{{ $value }}</a>
                        @endif
                        @if($loop->iteration == 6)
                        <br>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 移除註記 Modal --}}
<div id="removeModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;height: 95%;">
        <div class="modal-content" style="max-height: 95%;">
            <div class="modal-header">
                <h5 class="modal-title" id="removeModalLabel">選擇要移除註記的商品</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body table-responsive" style="max-height: calc(100% - 60px);overflow-y: auto;">
                <table width="100%" id="removeTable" class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th width="10%" class="text-left">預定交貨日</th>
                            <th width="10%" class="text-left">訂單號碼</th>
                            <th width="10%" class="text-left">採購單號</th>
                            <th width="15%" class="text-left">廠商名稱</th>
                            <th width="25%" class="text-left">品名</th>
                            <th width="10%" class="text-right">數量</th>
                            <th width="10%" class="text-right">採購價</th>
                            <th width="5%" class="text-center"></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div>
                <div class="m-3 float-left">
                </div>
                <div class="m-3 float-right">
                    <button class="btn btn-sm btn-secondary" id="cancelAll2">取消全部</button>
                    <button class="btn btn-sm btn-success" id="selectAll2">全選</button>
                    <button class="btn btn-sm btn-primary removeProcess" value="selected">送出勾選</button>
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


{{-- 折讓訂單 Modal --}}
<div id="allwanceModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="allwanceModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="allowanceForm" action="{{ url('orders/allowance') }}" method="POST">
                    @csrf
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="text-left" width="45%">品項</th>
                                <th class="text-right" width="10%">數量</th>
                                <th class="text-right" width="10%">單價</th>
                                <th class="text-right" width="10%">小計(未稅)</th>
                                <th class="text-right" width="10%">稅額</th>
                                <th class="text-right" width="10%">操作</th>
                            </tr>
                        </thead>
                        <tbody id="allowanceData"></tbody>
                    </table>
                    <button type="submit" class="btn btn-sm btn-primary" id="allowanceSubmit">送出</button>
                </form>
                <div>
                    <span class="text-danger">注意! 開立發票折讓填入之金額與實際訂單金額無關。</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 好友推薦匯出 Modal --}}
<div id="friendModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="friendModalLabel">好友推薦資料匯出</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="friendForm" action="{{ url('orders/multiProcess') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cate" value="referFriend">
                    <input type="hidden" name="model" value="referFriend">
                    <input type="hidden" name="method" value="byQuery">
                    <input type="hidden" name="name" value="好友推薦匯出">
                    <input type="hidden" name="filename" value="好友推薦匯出">
                    <div class="col-6 offset-2"><label for="refer">請輸入年月</label></div>
                    <div  class="input-group col-6 offset-2">
                        <input type="number" class="form-control " id="refer_year" name="year" placeholder="格式：2024" value="{{ date('Y') }}" autocomplete="off" required>
                        <span class="input-group-addon bg-primary">~</span>
                        <input type="number" class="form-control " id="refer_month" name="month" placeholder="格式：01~12" value="{{ date('m') }}" min="1" max="12" autocomplete="off" required>
                        <div class="input-group-prepend">
                            <button type="submit" class="btn btn-sm btn-primary">送出</button>
                        </div>
                    </div>
                </form>
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

        $('.removeAllowance').click(function(){
            $(this).parents('tr').remove();
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
                $("#source>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#source_hidden").val(sel.substring(1));
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
            if(cate == 'invoice'){
                form.append( $('<input type="hidden" class="formappend" name="model" value="OrderOpenInvoice">') );
            }else{
                form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
            }
            if(cate == 'Purchase'){
                $('#purchaseData').html('');
                $("#purchaseTable").DataTable().destroy();
                let token = '{{ csrf_token() }}';
                $.ajax({
                    type: "post",
                    url: 'orders/getUnPurchase',
                    data: { id: ids, condition: condition, cate: cate, filename: filename, method: multiProcess, model: 'orders', _token: token },
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
                    url: 'orders/getPurchasedItems',
                    data: { id: ids, condition: condition, cate: cate, filename: filename, method: multiProcess, model: 'orders', _token: token },
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
                    if(param == 'all'){
                        alert('資料量太多，請先選擇條件篩選。');
                        sessionStorage.setItem('shippingData',null);
                        $('#showExpress').html('顯示各物流總數');
                        $('#expressData').html(null);
                        $('#showExpressTable').toggle();
                        return;
                    }else{
                        $.ajax({
                            type: "get",
                            url: 'orders/getExpressData',
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

                    }
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
            }else{
                sessionStorage.setItem('shippingData',null);
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

        $('#friendExport').click(function(){
            $('#friendModal').modal('show');
        });

        $('.acOrder').click(function(){
            if(confirm("請確認是否要重新處理此acOrder串接訂單？")){
                let orderNumber = $(this).val();
                let form = $('#multiProcessForm');
                form.append($('<input type="hidden" class="formappend" name="orderNumber" value="'+orderNumber+'">'));
                form.append($('<input type="hidden" class="formappend" name="method" value="OneOrder">'));
                form.append($('<input type="hidden" class="formappend" name="cate" value="acOrder">'));
                form.append($('<input type="hidden" class="formappend" name="type" value="process">'));
                form.append( $('<input type="hidden" class="formappend" name="filename" value="重新處理">') );
                form.append( $('<input type="hidden" class="formappend" name="model" value="acOrderProcess">') );
                form.submit();
            }
        });

        $('.modifyInfo').click(function(){
            $('#modifyRecord').html('');
            let token = '{{ csrf_token() }}';
            let id = $(this).val();
            $.ajax({
                type: "post",
                url: 'orders/getlog',
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
                    url: 'orders/getInfo',
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
                            order['asiamiles_account'] == '' || order['asiamiles_account'] == null ? order['asiamiles_account'] = '' : '';
                            order['asiamiles_name'] == '' || order['asiamiles_name'] == null ? order['asiamiles_name'] = '' : '';
                            order['asiamiles_lastname'] == '' || order['asiamiles_lastname'] == null ? order['asiamiles_lastname'] = '' : '';
                            order['receiver_name'] == '' || order['receiver_name'] == null ? order['receiver_name'] = '' : '';
                            order['receiver_email'] == '' || order['receiver_email'] == null ? order['receiver_email'] = '' : '';
                            order['user_memo'] == '' || order['user_memo'] == null ? order['user_memo'] = '' : '';
                            order['receiver_tel'] == '' || order['receiver_tel'] == null ? order['receiver_tel'] = '' : '';
                            order['greeting_card'] == '' || order['greeting_card'] == null ? order['greeting_card'] = '' : '';
                            order['receiver_keyword'] == '' || order['receiver_keyword'] == null ? order['receiver_keyword'] = '' : '';
                            order['receiver_address'] == '' || order['receiver_address'] == null ? order['receiver_address'] = '' : '';
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
                            let html = '<div class="row align-items-center"><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">訂購人ID :</span></div><input type="text" class="form-control" value="'+order['user_id']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">收件人資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收件人</span></div><input type="text" class=" form-control" value="'+order['receiver_name']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">電話</span></div><input type="text" class=" form-control" value="'+order['receiver_tel']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">E-Mail</span></div><input type="text" class=" form-control" value="'+order['receiver_email']+'" disabled></div></div><div class="col-5 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">地址</span></div><input type="text" class=" form-control" value="'+order['receiver_address']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-plane"></i>班機號碼／<i class="fas fa-hotel"></i>旅店名稱</span></div><input type="text" class="form-control" value="'+order['receiver_keyword']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i>提貨時間</span></div><input type="text" class="form-control" value="'+order['receiver_key_time']+'" disabled></div></div><div class="col-6 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">賀卡留言</span></div><input type="text" class=" form-control" value="'+order['greeting_card']+'" disabled></div></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">訂單備註</span></div><input type="text" class=" form-control" value="'+order['user_memo']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">亞洲萬里通資訊</span></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Account</span></div><input type="text" class=" form-control mr-2" value="'+order['asiamiles_account']+'" disabled><div class="input-group-prepend"><span class="input-group-text">Name</span></div><input type="text" class="form-control mr-2" value="'+order['asiamiles_name']+'" disabled><div class="input-group-prepend"><span class="input-group-text">Last Name</span></div><input type="text" class="form-control mr-2" value="'+order['asiamiles_lastname']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">發票資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票號碼</span></div><input type="text" class=" form-control" value="'+order['is_invoice_no']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">類別</span></div><input type="text" class=" form-control" value="'+order['invoice_sub_type']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">愛心碼</span></div><input type="text" class=" form-control" value="'+order['love_code']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">載具</span></div><input type="text" class=" form-control" value="'+order['carrier_type']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">手機條碼/自然人憑證條碼</span></div><input type="text" class=" form-control" value="'+order['carrier_num']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票聯式</span></div><input type="text" class=" form-control" value="'+order['invoice_type']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">統編</span></div><input type="text" class=" form-control" value="'+order['invoice_number']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">抬頭</span></div><input type="text" class=" form-control" value="'+order['invoice_title']+'" disabled></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收受人真實姓名</span></div><input type="text" class=" form-control" value="'+order['buyer_name']+'" disabled></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票收受人E-Mail</span></div><input type="text" class=" form-control" value="'+order['buyer_email']+'" disabled></div></div>';
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
        $("#search").attr('disabled',true);
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
        $("#source>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#source_hidden").val(sel.substring(1));
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

    function itemmemo (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let admin_memo=$(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'orders/itemmemo',
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
                url: 'orders/getshippingvendors',
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
        $("#source>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#source_hidden").val(sel.substring(1));
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
        }else if(column_name == 'merge_order'){
            title = '合併訂單';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入被'+title+'單號';
            placeholder = '請輸入被'+title+'單號，多筆訂單請以,逗號區隔。';
            note = '<div><span class="text-primary">清空內容為取消合併訂單資料</span></div>';
        }else if(column_name == 'shipping_number'){
            title = '物流單號';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，請輸入'+title;
            placeholder = '請輸入'+title+'內容';
            note = '<div><span class="text-primary">清空內容為取消物流單號</span></div>';
        }else if(column_name == 'cancel'){
            title = '取消訂單';
            id.length >=2 ? label = title : label = '訂單編號：'+order_number+'，'+title;
            placeholder = '請輸入取消訂單原因，例如：客戶要求取消';
            note = '<div><span class="text-danger">訂單狀態為【已付款待出貨 或 集貨中】才會被變更喔(防呆機制)</span></div>';
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
                    url: 'orders/getlog',
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
                                let bookDate = data[i]['book_shipping_date'];
                                let vendorDate = data[i]['vendor_arrival_date'];
                                let record = '<tr><td class="text-center">'+(i+1)+'</td><td class="text-left">'+dateTime+'</td><td class="text-left">'+name+'</td><td class="text-left">'+bookDate+'</td><td class="text-left">'+vendorDate+'</td><td class="text-left">'+status+'</td><td class="text-right">'+amount+'</td><td class="text-right">'+itemQty+'</td><td class="text-right">'+shippingFee+'</td><td class="text-right">'+parcelTax+'</td><td class="text-right">'+discount+'</td><td class="text-right">'+spendPoint+'</td></tr>';
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
                    url: 'orders/getlog',
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
                url: 'orders/getshippingvendors',
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
                if(confirm('請確認是否真的要取消該訂單？')){
                    modifysend(id,column_name,column_data,itemIds)
                }else{
                    $('#myModal').modal('hide');
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
            url: 'orders/modify',
            data: { id: id, column_name: column_name, column_data: column_data, item_ids: itemIds, _token: token },
            success: function(orders) {
                let column_name2 = 'vendor_arrival_date';
                if(orders){
                    for(i=0;i<orders.length;i++){
                        target = '.'+column_name+'_'+orders[i]['id'];
                        value = orders[i][column_name];
                        value == null ? value = '' : '';
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
                        }else if(column_name == 'merge_order'){
                            nullText = '<span>合併：</span>';
                            text = '<span>合併：'+value+'</span>';
                        }else if(column_name == 'shipping_number'){
                            nullText = '<span>物流單號：</span>';
                            text = '<span>物流單號：'+value+'</span>';
                        }
                        if(column_name == 'cancel'){
                            target = '.admin_memo_'+orders[i]['id'];
                            value = orders[i]['admin_memo'];
                            nullText = '<span><i class="fas fa-info-circle"></i></span>';
                            text = '<span><i class="fas fa-info-circle"></i>('+value+')</span>';
                            target2 = '.status_'+orders[i]['id'];
                            text2 = '後台取消訂單';
                            $(target).attr('onclick','modify('+orders[i]['order_number']+','+orders[i]['id']+',\''+target+'\',\''+value+'\',this)');
                            value ? $(target).html(text) : $(target).html(nullText);
                            $(target2).html(text2);
                        }else if(column_name == 'order_item_modify' || column_name == 'item_is_call_clear'){
                            items = orders[i]['items'];
                            for(j=0; j<items.length; j++){
                                value = items[j]['is_call'];
                                target = '.order_item_modify_'+items[j]['id'];
                                if(value){
                                    text = value.substring(0,4)+'/'+value.substring(4,6)+'/'+value.substring(6,8)+'叫貨';
                                    html = '<a href="javascript:" class="forhide badge badge-danger item_is_call_'+items[j]['id']+'" onclick="modify('+orders[i]['order_number']+','+orders[i]['id']+',\'item_is_call_clear\',\''+value+'\',this,'+items[j]['id']+')"><span>'+text+'</span></a> '+items[j]['vendor_name'];
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
                            $(target).attr('onclick','modify('+orders[i]['order_number']+','+orders[i]['id']+',\''+column_name+'\',\''+value+'\',this)');
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
        $("#source>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#source_hidden").val(sel.substring(1));
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
        }else if(name == 'spend_point' || name == 'is_discount' || name == 'is_asiamiles' || name == 'is_shopcom' || name == 'digiwin_payment_id' || name == 'domain' || name == 'shipping_vendor_name' || name == 'pay_method' || name == 'invoice_type' || name == 'invoice_number' || name == 'invoice_no_empty' || name == 'invoice_address'){
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="oneOrderOpenInvoice">') );
            form.submit();
        }
    }

    function acOrderOpenInvoice(orderId)
    {
        if(confirm("注意！訂單狀態已出貨才能開立發票，請確認是否要開立發票？")){
            let form = $('#multiProcessForm');
            form.append($('<input type="hidden" class="formappend" name="id[]" value="'+orderId+'">'));
            form.append($('<input type="hidden" class="formappend" name="method" value="OneOrder">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="invoice">'));
            form.append($('<input type="hidden" class="formappend" name="type" value="create">'));
            form.append( $('<input type="hidden" class="formappend" name="filename" value="開立發票">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="acOrderOpenInvoice">') );
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

    function reopenInvoice(orderId)
    {
        if(confirm("注意！重開發票並不會自動更新鼎新資料，請手動更新鼎新結帳單內的發票資料。")){
            let form = $('#multiProcessForm');
            form.append($('<input type="hidden" class="formappend" name="id[]" value="'+orderId+'">'));
            form.append($('<input type="hidden" class="formappend" name="method" value="OneOrder">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="invoice">'));
            form.append($('<input type="hidden" class="formappend" name="type" value="reopen">'));
            form.append( $('<input type="hidden" class="formappend" name="filename" value="重開發票">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="oneOrderOpenInvoice">') );
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
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
        form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
        form.submit();
        $('.formappend').remove();
    }

    function purchaseCancel(id)
    {
        if(confirm('注意!! 移除採購註記，並不會移除採購單內的商品資料，請自行手動修改採購單內商品資料。請確認是否移除採購註記??')){
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'orders/purchaseCancel',
                data: { id: id, _token: token },
                success: function(data) {
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
                url: 'orders/getlog',
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
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
                $("#source>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#source_hidden").val(sel.substring(1));

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
            form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
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
            url: 'orders/markNotPurchase',
            data: { order_item_id: id, _token: token },
            success: function(data) {
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

    function invoiceLog(orderNumber,orderId){
        let record = '';
        let token = '{{ csrf_token() }}';
        let label =  "訂單號碼："+orderNumber+"，發票紀錄：";
        $('#invoiceModal').modal('show');
        $('#invoiceModalLabel').html(label);
        $('#invoiceRecord').html('');
        $.ajax({
            type: "post",
            url: 'orders/getInvoiceLogs',
            data: {orderNumber:orderNumber,_token: token},
            success: function(data) {
                if(data.length > 0){
                    for(let i=0; i<data.length; i++){
                        let dateTime = data[i]['createTime'];
                        let description = data[i]['get_json'];
                        let serialNo = i+1;
                        let type = data[i]['type'];
                        type == null ? type = '開立發票' : '';
                        type == 'create' ? type = '開立發票' : '';
                        type == 'cancel' ? type = '作廢發票' : '';
                        type == 'reopen' ? type = '重開發票' : '';
                        type == 'allowance' ? type = '開立折讓' : '';
                        let invoiceNo = data[i]['invoice_no'];
                        let oldInvoice = data[i]['canceled_order_number'] == null ? '' : data[i]['canceled_order_number'];
                        record += '<tr><td width="5%" class="text-center">'+serialNo+'</td><td width="15%" class="text-left">'+dateTime+'</td><td width="10%" class="text-left">'+type+'</td><td width="40%" class="text-left">'+description+'</td><td width="15%" class="text-left">'+invoiceNo+'</td><td width="15%" class="text-left">'+oldInvoice+'</td></tr>';
                    }
                    $('#invoiceRecord').html(record);
                }
            }
        });
    }

    function allowanceInvoice(orderNumber,orderId)
    {
        let token = '{{ csrf_token() }}';
        let form = $('#allowanceForm');
        let label = "訂單編號："+orderNumber+"，";
        $('#allowanceData').html('');
        $('#allwanceModal').modal('show');
        $.ajax({
            type: "post",
            url: 'orders/getAllowanceItem',
            data: { order_id: orderId, _token: token },
            success: function(data) {
                let html = '';
                if(data){
                    let orderId = data['id'];
                    html += '<input type="hidden" class="formappend" name="id" value="'+orderId+'">';
                    let taxType = data['taxType'];
                    let invoiceType = data['invoiceType'];
                    let invoiceNumber = data['invoiceNumber'];
                    let remainAmt = data['remainAmt'];
                    items = data['items'];
                    form.append($('<input type="hidden" class="formappend" name="taxType">').val(taxType));
                    if(items.length > 0){
                        for(i=0;i<items.length;i++){
                            let name = items[i]['name'];
                            let quantity = items[i]['quantity'];
                            let price = items[i]['price'];
                            let amount = items[i]['amount'];
                            let tax = items[i]['tax'];
                            let unitName = items[i]['unit_name'];
                            html += '<tr id="allowance_'+i+'"><input type="hidden" name="items['+i+'][unit_name]" value="'+unitName+'"><input type="hidden" name="items['+i+'][name]" value="'+name+'"><td class="text-left" width="50%">'+name+'</td><td class="text-right" width="10%"><input type="text" class="form-control form-control-sm text-right" name="items['+i+'][quantity]" value="'+quantity+'"></td><td class="text-right" width="10%"><input type="text" class="form-control form-control-sm text-right" name="items['+i+'][price]" value="'+price+'"></td><td class="text-right" width="10%"><input type="text" class="form-control form-control-sm text-right" name="items['+i+'][amount]" value="'+amount+'"></td><td class="text-right" width="10%"><input type="text" class="form-control form-control-sm text-right" name="items['+i+'][tax]" value="'+tax+'"></td><td class="text-right" width="10%"><button type="button" class="btn btn-sm btn-danger" onclick="removeAllowance(this)" value="'+i+'"><i class="far fa-trash-alt"></i></button></td></tr>';
                        }
                        $('#allowanceSubmit').attr('disabled',false);
                        label += invoiceType+"，發票號碼："+invoiceNumber+"，折讓餘額："+remainAmt;
                    }else{
                        label += "查無商品資料。";
                        $('#allowanceSubmit').attr('disabled',true);
                    }
                }else{
                    label += "查無發票資料。";
                    $('#allowanceSubmit').attr('disabled',true);
                }
                $('#allowanceData').html(html);
                $('#allwanceModalLabel').html(label);
            }
        });
    }

    function removeAllowance(e){
        let v = e.value;
        $('#allowance_'+v).remove();
    }
</script>
@endsection
