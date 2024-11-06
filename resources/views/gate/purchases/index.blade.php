@extends('gate.layouts.master')

@section('title', '採購單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>採購單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('purchases') }}">採購單管理</a></li>
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
                                    @if(in_array($menuCode.'SY', explode(',',Auth::user()->power)))
                                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="SyncToGate">鼎新今日採購單同步</button>
                                    @endif
                                    @if(in_array($menuCode.'IM', explode(',',Auth::user()->power)))
                                    <button class="btn btn-sm btn-primary mr-2" id="stockinImport">入庫單匯入</button>
                                    @endif
                                </div>
                                <div class="col-7">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($purchases) ? number_format($purchases->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left">
                                        <span clas="d-flex align-items-center">查詢條件：</span>
                                        <span class="badge badge-info mr-1">
                                            @if($status != '0')
                                            <span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('status')">X</span>
                                            @endif
                                            採購單狀態：
                                            @if($status == '-1,0,1,2,3')全部@else
                                            @if(in_array(-1,explode(',',$status)))已取消,@endif
                                            @if(in_array(0,explode(',',$status)))尚未採購,@endif
                                            @if(in_array(1,explode(',',$status)))已採購,@endif
                                            @if(in_array(2,explode(',',$status)))已入庫,@endif
                                            @if(in_array(3,explode(',',$status)))已結案@endif
                                            @endif
                                        </span>
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
                                        @if(!empty($order_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('order_number')">X</span> iCarry訂單單號：{{ $order_number }}</span>@endif
                                        @if(!empty($erp_stockin_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_stockin_no')">X</span> 鼎新入庫單號：{{ $erp_stockin_no }}</span>@endif
                                        @if(!empty($notice_vendor))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('notice_vendor')">X</span> 是否通知廠商：{{ $notice_vendor == 'noticed' ? '已通知' : '未通知' }}</span>@endif
                                        @if(!empty($product_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('product_name')">X</span> 商品名稱：{{ $product_name }}</span>@endif
                                        @if(!empty($erp_purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_purchase_no')">X</span> 鼎新採購單號：{{ $erp_purchase_no }}</span>@endif
                                        @if(!empty($purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('purchase_no')">X</span> 採購單號：{{ $purchase_no }}</span>@endif
                                        @if(!empty($vendor_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_name')">X</span> 商家名稱：{{ $vendor_name }}</span>@endif
                                        @if($list)<span class="badge badge-info mr-1">每頁：{{ $list }} 筆</span>@endif
                                    </div>
                                    <div class="col-4 float-right">
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
                                @if(!empty($NGStockin['poids']) || !empty($NGReturn['poids']))
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left">
                                        <div class="row">
                                            <div>
                                                <span class="badge badge-danger text-sm mr-2">入庫、退貨數量異常：</span>
                                            </div>
                                            @if(!empty($NGStockin))
                                            <form role="form" action="{{ url('purchases') }}" method="get">
                                                <input type="hidden" name="ng_stockin" value="{{ $NGStockin['poids'] }}">
                                                <button type="submit" class="badge {{ !empty($ng_stockin) ? 'badge-success' : 'badge-primary' }} mr-2" title="採購單號:{{ $NGStockin['purchaseNos'] }}">入庫異常：<span class="badge badge-secondary">{{ $NGStockin['count'] }}</span></span></button>
                                            </form>
                                            @endif
                                            @if(!empty($NGReturn))
                                            <form role="form" action="{{ url('purchases') }}" method="get">
                                                <input type="hidden" name="ng_return" value="{{ $NGReturn['poids'] }}">
                                                <button type="submit" class="badge  {{ !empty($ng_return) ? 'badge-success' : 'badge-primary' }}  mr-2" title="採購單號:{{ $NGReturn['purchaseNos'] }}">退貨異常：<span class="badge badge-secondary">{{ $NGReturn['count'] }}</span></button>
                                            </form>
                                            @endif
                                            @if(!empty($ng_return) || !empty($ng_stockin))
                                            <div>
                                                <a href="{{ url('purchases') }}" class="badge badge-secondary mr-2" title="採購單號:{{ $NGReturn['purchaseNos'] }}">取消選擇</a>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($orderCancelCount > 0)
                                <div class="col-12 mt-2">
                                    <a class="text-bold" href="{{ route('gate.orderCancel.index') }}"><span class="badge badge-danger">訂單取消庫存提示</span></a>
                                    @if(count($orderCancels) > 0)
                                    @foreach($orderCancels as $orderCancel)
                                    <span data-toggle="popover" data-placement="top" class="text-primary orderCancel_{{ $orderCancel->id }}" data-content="
                                        品名：{{ $orderCancel->product_name }}
                                        <a class='btn btn-xs btn-primary' href='{{ url('purchases?digiwin_no='.$orderCancel->purchase_digiwin_no.'&status=0') }}' target='_blank'>前往</a>
                                    "><span class="badge badge-primary mr-2">{{ $orderCancel->purchase_digiwin_no.':'.$orderCancel->vendor_arrival_date }}<span class="badge badge-secondary">{{ $orderCancel->quantity - $orderCancel->deduct_quantity }}</span></span></span>
                                    @endforeach
                                    @endif
                                </div>
                                @endif
                                @if($sellReturnItemCount > 0)
                                <div class="col-12 mt-2">
                                    <a class="text-bold" href="{{ route('gate.sellReturnInfo.index') }}"><span class="badge badge-danger">銷退單品庫存提示</span></a>
                                    @if(count($sellReturns) > 0)
                                    @foreach($sellReturns as $return)
                                    <span data-toggle="popover" data-placement="top" class="text-primary sellReturn_{{ $return->id }}" data-content="
                                        品名：{{ $return->product_name }}
                                        <a class='btn btn-xs btn-primary' href='{{ url('purchases?digiwin_no='.$return->origin_digiwin_no.'&status=0') }}' target='_blank'>前往</a>
                                    "><span class="badge badge-primary mr-2">{{ $return->origin_digiwin_no.':'.$return->expiry_date }}<span class="badge badge-secondary">{{ $return->quantity }}</span></span></span>
                                    @endforeach
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="orderSearchForm" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('purchases') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label for="status">採購單狀態:</label>
                                                        <select class="form-control" id="status" size="5" multiple>
                                                            <option value="-1" {{ isset($status) ? in_array(-1,explode(',',$status)) ? 'selected' : '' : 'selected' }}  class="text-danger">已取消</option>
                                                            <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">尚未採購</option>
                                                            <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">已採購</option>
                                                            <option value="2"  {{ isset($status) ? in_array(2,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">已全部入庫</option>
                                                            <option value="3"  {{ isset($status) ? in_array(3,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">已結案</option>
                                                        </select><input type="hidden" value="-1,0,1,2" name="status" id="status_hidden" />
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <label for="order_number">鼎新採購單編號:</label>
                                                                <input type="number" inputmode="numeric" class="form-control" id="erp_purchase_no" name="erp_purchase_no" placeholder="鼎新採購單單號" value="{{ isset($erp_purchase_no) && $erp_purchase_no ? $erp_purchase_no : '' }}" autocomplete="off" />
                                                            </div>
                                                            <div class="col-6">
                                                                <label for="order_number">鼎新入庫單編號:</label>
                                                                <input type="number" inputmode="numeric" class="form-control" id="erp_stockin_no" name="erp_stockin_no" placeholder="鼎新入庫單單號" value="{{ isset($erp_stockin_no) && $erp_stockin_no ? $erp_stockin_no : '' }}" autocomplete="off" />
                                                            </div>
                                                            <div class="col-6 mt-2">
                                                                <label for="order_number">iCarry訂單單號:</label>
                                                                <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單編號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                            </div>
                                                            <div class="col-6 mt-2">
                                                                <label for="order_number">iCarry採購單號:</label>
                                                                <input type="number" inputmode="numeric" class="form-control" id="purchase_no" name="purchase_no" placeholder="iCarry採購單編號" value="{{ isset($purchase_no) && $purchase_no ? $purchase_no : '' }}" autocomplete="off" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="vendor_name">商家名稱:</label>
                                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱:海邊走走" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="product_name">商品名稱:</label>
                                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="digiwin_no">商品貨號:</label>
                                                <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫EC/BOM或鼎新商品貨號" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="created_at">採購時間區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="created_at" name="created_at" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($created_at) ? $created_at ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="created_at_end" name="created_at_end" placeholder="格式：2016-06-06 05:55:00" value="{{ isset($created_at_end) ? $created_at_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="book_shipping_date">廠商入庫日區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="vendor_arrival_date" name="vendor_arrival_date" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date) ? $vendor_arrival_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="vendor_arrival_date_end" name="vendor_arrival_date_end" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date_end) ? $vendor_arrival_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="book_shipping_date">預定出貨日:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="book_shipping_date" name="book_shipping_date" placeholder="格式：2016-06-06" value="{{ isset($book_shipping_date) ? $book_shipping_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="book_shipping_date_end" name="book_shipping_date_end" placeholder="格式：2016-06-06" value="{{ isset($book_shipping_date_end) ? $book_shipping_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="book_shipping_date">採購單變更區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="change_date" name="change_date" placeholder="格式：2016-06-06" value="{{ isset($change_date) ? $change_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="change_date_end" name="change_date_end" placeholder="格式：2016-06-06" value="{{ isset($change_date_end) ? $change_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="notice_vendor">是否通知廠商?</label>
                                                        <select class="form-control" id="notice_vendor" name="notice_vendor">
                                                            <option value="" {{ isset($notice_vendor) && $notice_vendor == '' ? 'selected' : '' }}>不拘</option>
                                                            <option value="noticed" {{ isset($notice_vendor) && $notice_vendor == 'noticed' ? 'selected' : '' }}>已通知</option>
                                                            <option value="noNotice" {{ isset($notice_vendor) && $notice_vendor == 'noNotice' ? 'selected' : '' }}>未通知</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mt-2">
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
                                    <div class="col-12 text-center mb-2">
                                        <button type="button" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                        {{-- <button type="button" class="btn btn-success moreOption">更多選項</button> --}}
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                           @if(!empty($purchases))
                           <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="25%">採購單資訊</th>
                                            <th class="text-left" width="75%">採購品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($purchases as $purchase)
                                        <tr style="border-bottom:3px #000000 solid;border-bottom:3px #000000 solid;">
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    <input type="checkbox" class="chk_box_{{ $purchase->id }}" name="chk_box" value="{{ $purchase->id }}">
                                                    <a href="{{ route('gate.purchases.show', $purchase->id) }}" class="mr-2">
                                                        <span class="text-lg text-bold order_number_{{ $purchase->id }}">{{ $purchase->purchase_no }}</span>
                                                    </a>
                                                    @if($purchase->status != -1 && $purchase->status != 3)
                                                    @if(count($purchase->checkStockin) == 0)
                                                    @if(in_array($menuCode.'CO', explode(',',Auth::user()->power)))
                                                    <button type="button" value="{{ $purchase->id.'_'.$purchase->noticeVendor }}" class="badge btn-sm btn btn-danger btn-cancel">取消採購單</button>
                                                    @endif
                                                    @endif
                                                    @endif
                                                    @if(count($purchase->changeLogs) > 0)
                                                    <a href="javascript:" class="badge btn-sm btn badge-purple" onclick="getChange({{ $purchase->purchase_no }},{{ $purchase->id }},this)">修改紀錄</a>
                                                    @endif
                                                    @if(count($purchase->checkStockin) > 0)
                                                    <a href="{{ route('gate.purchases.returnForm', $purchase->id) }}" value="{{ $purchase->id }}" class="badge btn-sm btn btn-warning">退貨</a>
                                                    @endif
                                                    @if($purchase->ng == 1)
                                                    <br>
                                                    <span class="text-sm text-bold text-danger">注意!!此採購單內有商品數量為0</span>
                                                    @endif
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <span class="text-sm">鼎新單別：{{ $purchase->type }}</span><br>
                                                        <span class="text-sm">商　家：{{ $purchase->vendor_name }}</span><br>
                                                        <span class="text-sm">稅　別：
                                                            @if($purchase->tax_type == 1)
                                                            應稅內含
                                                            @elseif($purchase->tax_type == 2)
                                                            應稅外加
                                                            @elseif($purchase->tax_type == 3)
                                                            零稅率
                                                            @elseif($purchase->tax_type == 4)
                                                            免稅
                                                            @elseif($purchase->tax_type == 9)
                                                            不計稅
                                                            @endif
                                                        </span><br>
                                                        <span class="text-sm">金　額：{{ $purchase->amount }}</span><br>
                                                        <span class="text-sm">稅　金：{{ $purchase->tax }}</span><br>
                                                        <span class="text-sm">總金額：{{ $purchase->amount + $purchase->tax }}</span><br>
                                                        <span class="text-sm">單品數量：{{ $purchase->quantity }}</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="status_{{ $purchase->id }} text-bold">
                                                            @if($purchase->status == -1)
                                                            已取消
                                                            @elseif($purchase->status == 0)
                                                            尚未採購
                                                            @elseif($purchase->status == 1)
                                                            已採購
                                                            @elseif($purchase->status == 2)
                                                            已全部入庫
                                                            @elseif($purchase->status == 3)
                                                            已結案
                                                            @endif
                                                            <br>
                                                        </span>
                                                        <a href="javascript:" class="forhide badge mt-1 badge-primary sync_date_{{ $purchase->id }}" onclick="getLog({{ $purchase->purchase_no }},{{ $purchase->id }},'sync_date','{{ str_replace('-','/',substr($purchase->synced_time,0,10)) ?? '' }}',this)">{{ substr($purchase->synced_time,0,10) ? '已同步鼎新：'.str_replace('-','/',substr($purchase->synced_time,0,10)) : '已同步鼎新：無' }}</a><br>
                                                        <span class="forhide mt-1 badge badge-success stockin_date_{{ $purchase->id }}">已全部入庫：{{ $purchase->status == 2 ? str_replace('-','/',$purchase->stockin_finish_date) : '無' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-left align-top p-0">
                                                @if($purchase->ng == 1)
                                                <span class="text-sm text-bold text-danger">注意!!此採購單內有商品數量為0</span>
                                                @endif
                                                <table class="table table-sm">
                                                    <thead class="table-info">
                                                        <th width="10%" class="text-left align-middle text-sm">廠商入庫日</th>
                                                        <th width="15%" class="text-left align-middle text-sm">商家</th>
                                                        <th width="15%" class="text-left align-middle text-sm">貨號</th>
                                                        <th width="25%" class="text-left align-middle text-sm">品名</th>
                                                        <th width="5%" class="text-center align-middle text-sm">單位</th>
                                                        <th width="5%" class="text-right align-middle text-sm">採購量</th>
                                                        <th width="5%" class="text-right align-middle text-sm">入庫量</th>
                                                        <th width="5%" class="text-right align-middle text-sm">退貨量</th>
                                                        <th width="8%" class="text-right align-middle text-sm">採購價</th>
                                                        <th width="7%" class="text-right align-middle text-sm">總價</th>
                                                    </thead>
                                                    <tbody>
                                                        <form id="itemsform_order_{{ $purchase->id }}" method="POST">
                                                            @foreach($purchase->items as $item)
                                                            {{-- @if(strstr($item->sku,'BOM')) --}}
                                                            {{-- <tr style="background-color:rgb(254, 255, 223)"> --}}
                                                            {{-- @else --}}
                                                            <tr>
                                                            {{-- @endif --}}
                                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_arrival_date }}</span>
                                                                    @if(!empty($item->vendor_shipping_no))
                                                                    <span class="text-danger" data-toggle="popover" data-content="<small>廠商出貨單號：{{ $item->vendor_shipping_no }}</small>"><i class="fas fa-tags" title="廠商出貨單"></i></span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_name }}</span>
                                                                    @if($item->direct_shipment == 1)
                                                                    <span class="text-primary"><i class="fas fa-truck" title="廠商直寄"></i></span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->digiwin_no }}</span>
                                                                    @if(!empty($item->stockin_date))
                                                                    <span data-toggle="popover" class="text-primary stockin_item_{{ $item->id }}" data-content="
                                                                        <small>
                                                                            入庫單號：{{ $item->erp_stockin_no }}<br>
                                                                            入庫日期：{{ $item->stockin_date }}
                                                                            {{-- <button class='btn btn-outline-secondary btn-xs' onclick='purchaseCancel({{ $item->syncedOrderItem['id'] }})'>移除</button> --}}
                                                                        </small>
                                                                        "><i class="fas fa-store-alt"></i></span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->product_name }}</span>
                                                                    @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                                                                    @if(count($purchase->checkStockin) == 0)
                                                                    <span class="text-primary" data-toggle="popover" title="商品備註(按Enter更新)" data-placement="top" id="item_memo_{{ $item->id }}" data-content="<textarea class='text-danger' onkeydown='itemmemo(event,{{ $item->id }})'>{{ $item->memo }}</textarea>"><i class="fas fa-info-circle"></i></span>
                                                                    @else
                                                                    <span class="text-primary" data-toggle="popover" title="商品備註" data-placement="top" id="item_memo_{{ $item->id }}" data-content="{{ $item->memo ?? '無' }}"><i class="fas fa-info-circle"></i></span>
                                                                    @endif
                                                                    @else
                                                                    <span class="text-primary" data-toggle="popover" title="商品備註" data-placement="top" id="item_memo_{{ $item->id }}" data-content="{{ $item->memo ?? '無' }}"><i class="fas fa-info-circle"></i></span>
                                                                    @endif
                                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已取消)</span>@endif
                                                                </td>
                                                                <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->unit_name }}</span></td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ number_format($item->quantity) }}</span></td>
                                                                <td class="text-right align-middle text-sm item_qty_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    @if(!empty($item->stockinQty) && $item->stockinQty > 0)
                                                                        @if($item->is_lock == 0)
                                                                        <span class="text-primary" id="item_qty_{{ $item->id }}" onclick="stockinModify({{ !empty($item->single) ? $item->single['id'] : '' }})">
                                                                            <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }} new_item_qty_{{ $item->id }}"><u>{{ $item->stockinQty }}</u></span>
                                                                        </span>
                                                                        @else
                                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}" >{{ $item->stockinQty }}</span>
                                                                        @endif
                                                                    @endif
                                                                </td>
                                                                <td class="text-right align-middle text-sm item_qty_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    @if(count($item->returns) > 0)
                                                                    <span class="text-danger" data-toggle="popover" title="退貨資訊" data-placement="right" data-content="
                                                                        @foreach($item->returns as $return)
                                                                        退貨單號：{{ $return->return_discount_no }}
                                                                        退貨數量：{{ $return->quantity }}<br>
                                                                        @endforeach
                                                                        ">
                                                                    @if(!empty($item->returnQty))
                                                                    -{{ $item->returnQty }}
                                                                    @endif
                                                                    </span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span></td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price * $item->quantity }}</span></td>
                                                            </tr>
                                                            @if(strstr($item->sku,'BOM'))
                                                                @if(count($item->package)>0)
                                                                <tr class="m-0 p-0">
                                                                    <td colspan="11" class="text-sm p-0">
                                                                        <table width="100%" class="table-sm m-0 p-0">
                                                                            {{-- <thead style="background-color:rgb(221, 221, 221)"> --}}
                                                                            <thead>
                                                                                <tr>
                                                                                    <th width="10%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                                    <th width="25%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                                    <th width="5%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">採購量</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">入庫量</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">退貨量</th>
                                                                                    <th width="8%" class="text-right align-middle text-sm" style="border: none; outline: none">採購價</th>
                                                                                    <th width="7%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($item->package as $packageItem)
                                                                                <tr>
                                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                                    <td class="text-left align-middle text-sm" >
                                                                                        <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</span>
                                                                                        @if(!empty($packageItem->stockin_date))
                                                                                        <span data-toggle="popover" class="text-primary stockin_package_{{ $packageItem['id'] }}" data-content="
                                                                                            <small>
                                                                                                入庫單號：{{ $packageItem->erp_stockin_no }}<br>
                                                                                                入庫日期：{{ $packageItem->stockin_date }}
                                                                                                {{-- <button class='btn btn-outline-secondary btn-xs' onclick='purchaseCancel({{ $item->syncedOrderItem['id'] }})'>移除</button> --}}
                                                                                            </small>
                                                                                            "><i class="fas fa-store-alt"></i></span>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="text-left align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</span></td>
                                                                                    <td class="text-center align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['quantity'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm package_qty_{{ $packageItem['id'] }}" >
                                                                                        @if(!empty($packageItem['stockinQty']) && $packageItem['stockinQty'] > 0)
                                                                                        @if($item->is_lock == 0)
                                                                                        <span class="text-primary" id="package_qty_{{ $packageItem['id'] }}" onclick="stockinModify({{ $packageItem['single']['id'] }})">
                                                                                            <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }} new_package_qty_{{ $packageItem['id'] }}"><u>{{ $packageItem['stockinQty'] == 0 ? null : $packageItem['stockinQty'].' ' }}</u></span>
                                                                                        </span>
                                                                                        @else
                                                                                        <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['stockinQty'] == 0 ? null : $packageItem['stockinQty'].' ' }}</span>
                                                                                        @endif
                                                                                        {{-- <span class="text-primary" data-toggle="popover" title="修改數量(按Enter更新)" data-placement="right" id="package_qty_{{ $packageItem['id'] }}" data-content="<textarea class='text-danger' onkeydown='packageQty(event,{{ $packageItem['id'] }})'>{{ $packageItem['stockin_quantity'] }}</textarea>">
                                                                                            <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }} new_package_qty_{{ $packageItem['id'] }}"><u>{{ $packageItem['stockinQty'] == 0 ? null : $packageItem['stockinQty'].' ' }}</u></span>
                                                                                        </span> --}}
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="text-right align-middle text-sm package_qty_{{ $packageItem['id'] }}" >
                                                                                        @if(count($packageItem->returns) > 0)
                                                                                        <span class="text-danger" data-toggle="popover" title="退貨資訊" data-placement="right" data-content="
                                                                                            @foreach($packageItem->returns as $return)
                                                                                            退貨單號：{{ $return->return_discount_no }}
                                                                                            退貨數量：{{ $return->quantity }}<br>
                                                                                            @endforeach
                                                                                            ">
                                                                                        -{{ $packageItem['returnQty'] }}
                                                                                        </span>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['purchase_price'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ></td>
                                                                                </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                @endif
                                                            @endif
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($purchases) ? number_format($purchases->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $purchases->appends($appends)->render() }}
                                @else
                                {{ $purchases->render() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="multiProcessForm" action="{{ url('purchases/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="cancelForm" action="{{ url('purchases/cancel') }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('modal')
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
                <form action="{{ url('purchases/notice') }}" method="POST" class="float-right">
                    @csrf
                    <input type="hidden" id="purchaseOrderId" name="id" value="">
                    <button id="NoticeBtn" type="submit" class="btn btn-sm btn-primary" style="display: none">通知廠商</button>
                </form>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="10%" class="text-center">#</th>
                            <th width="15%" class="text-left">同步時間</th>
                            <th width="10%" class="text-left">單品數量</th>
                            <th width="10%" class="text-right">金額</th>
                            <th width="10%" class="text-right">稅金</th>
                            <th width="10%" class="text-right">總金額</th>
                            <th width="15%" class="text-center">通知時間</th>
                            <th width="15%" class="text-center">廠商確認時間</th>
                        </tr>
                    </thead>
                    <tbody id="syncRecord"></tbody>
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
                <div>
                    @if(in_array($menuCode.'SY', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="SyncToDigiwin">中繼同步至鼎新</button>
                    @endif
                    @if(in_array($menuCode.'CO', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-danger multiProcess mr-2" value="CancelOrder">取消採購單</button>
                    @endif
                    <hr>
                    @if(in_array($menuCode.'SEM', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="Notice_Email">通知廠商</button>
                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="Notice_Download">通知廠商(下載)</button>
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="NoticeVendor_Email">通知廠商(新)</button>
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="NoticeVendor_Download">通知廠商(新)(下載)</button>
                    <button class="btn btn-sm btn-info multiProcess mr-2" value="NoticeVendor_Modify">通知廠商(修改)</button>
                    @endif
                    <hr>
                    @if(in_array($menuCode.'EX', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Export_Stockin">匯出入庫單</button>
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Export_WithSingle">匯出採購單(組合+單品)</button>
                    <button class="btn btn-sm btn-info multiProcess mr-2" value="Export_OrderDetail">採購明細表</button>
                    <button class="btn btn-sm btn-info multiProcess mr-2" value="Export_OrderChange">採購變更明細表</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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
                <form  id="importForm" action="{{ url('purchases/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="cate" value="stockin">
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
                    <span class="text-danger">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/入庫報表範本.xls" target="_blank">入庫報表範本</a> ，製作正確的檔案。</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 修改紀錄 Modal --}}
<div id="modifyModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">狀態</th>
                            <th width="10%" class="text-left">時間</th>
                            <th width="10%" class="text-left">管理者</th>
                            <th width="15%" class="text-left">iCarry/鼎新品號</th>
                            <th width="25%" class="text-left">商品名稱</th>
                            <th width="5%" class="text-right">金額</th>
                            <th width="5%" class="text-right">數量</th>
                            <th width="10%" class="text-left">日期</th>
                            <th width="15%" class="text-left">備註</th>
                        </tr>
                    </thead>
                    <tbody id="modifyRecord"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 修改入庫 Modal --}}
<div id="stockinModifyModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockinModifyModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="stockinModifyForm" action="{{ url('purchases/stockinModify') }}" method="POST" class="float-right">
                @csrf
                <div class="modal-body">
                    <div id="purchaseQty"></div>
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="15%" class="text-left">鼎新入庫單號</th>
                                <th width="10%" class="text-left">入庫單序號</th>
                                <th width="30%" class="text-left">商品名稱</th>
                                <th width="10%" class="text-right">採購金額</th>
                                <th width="10%" class="text-right">入庫數量</th>
                                <th width="15%" class="text-right">入庫日期</th>
                            </tr>
                        </thead>
                        <tbody id="stockinModifyRecord"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary float-right btn-stockinModify">確定修改</button>
                </div>
            </form>
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
            form.append( $('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">') );
            form.append( $('<input type="hidden" class="formappend" name="cate" value="'+cate+'">') );
            form.append( $('<input type="hidden" class="formappend" name="type" value="'+type+'">') );
            form.append( $('<input type="hidden" class="formappend" name="filename" value="'+filename+'">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="purchase">') );
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
            }
            if(cate == 'Export' || cate == 'Notice'){
                if(type == 'Vendor'){
                    if(confirm('注意! 通知廠商(新) 功能不會帶入庫單給廠商，廠商必須於商家後台匯出\n尚未採購狀態的採購單，請先同步至鼎新才可使用此功能。')){
                        form.submit();
                    }
                }else{
                    if('注意! 匯出採購單與通知廠商僅會 匯出/通知 已採購、已完成入庫 狀態，\n尚未採購狀態的採購單，請先同步至鼎新才可使用此功能。'){
                        form.submit();
                    }
                }
            }else{
                form.submit();
            }
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
            $('#orderSearchForm').toggle();
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
            let id = $(this).val().split('_')[0];
            let chk = $(this).val().split('_')[1];
            let form = $('#cancelForm');
            form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
            if(chk == 'Y'){
                if(confirm('注意! 此筆採購單已通知廠商，請記得通知廠商取消這筆採購單，\n請確認是否要取消這筆採購單?')){
                    form.submit();
                }
            }else{
                if(confirm('請確認是否要取消這筆採購單?')){
                    form.submit();
                }
            }
            $('.formappend').remove();
            return;
        });

        $('#stockinImport').click(function(){
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
                            let confirmTime = data[i]['confirm_time'];
                            noticeTime == null ? noticeTime = '' : '';
                            confirmTime == null ? confirmTime = '' : '';
                            let total = parseFloat(amount) + parseFloat(tax);
                            let record = '<tr><td class="text-center">'+(i+1)+'</td><td class="text-left">'+dateTime+'</td><td class="text-left">'+quantity+'</td><td class="text-right">'+amount+'</td><td class="text-right">'+tax+'</td><td class="text-right">'+total+'</td><td class="text-center">'+noticeTime+'</td><td class="text-center">'+confirmTime+'</td></tr>';
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
        let sel="";
        $("#status>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#status_hidden").val(sel.substring(1));
        if(name == 'vendor_arrival_date' || name == 'created_at' || name == 'book_shipping_date'){
            $('input[name="'+name+'"]').val('');
            $('input[name="'+name+'_end"]').val('');
        }else if(name == 'notice_vendor'){
            $('select[name="'+name+'"]').val('');
        }else if(name == 'status'){
            $('input[name="'+name+'"]').val('0');
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
