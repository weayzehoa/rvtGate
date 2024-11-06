@extends('gate.layouts.master')

@section('title', '採購單退貨處理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>採購單退貨處理</b><span class="badge badge-success text-sm">{{ $purchase->order_number }}</span></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">採購單退貨處理</a></li>
                        <li class="breadcrumb-item active">退貨</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        @if(isset($purchase))
        <form id="myform" action="{{ route('gate.purchases.update', $purchase->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
        @else
        <form id="myform" action="{{ route('gate.purchases.store') }}" method="POST">
        @endif
            @csrf
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">採購單資料</h3>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">鼎新單號</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->erp_purchase_no ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-4 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">商家</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->vendor_name ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">建立時間</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->created_at ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">同步時間</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->synced_time ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">單品數量</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->quantity ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">金額</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->amount ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">稅金</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->tax ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">總計</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ ($purchase->amount + $purchase->tax) ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">狀態</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->status_text ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">採購單備註</span>
                                            </div>
                                            <input type="text" class=" form-control" disabled value="{{ $purchase->memo ?? null }}">
                                        </div>
                                    </div>
                                    <div class="card-primary card-outline col-12 mb-2"></div>
                                    @if(!empty($purchase->orders))
                                    <div class="col-12 mb-2">
                                        <span class="text-bold">iCarry 訂單：</span>
                                        @foreach($purchase->orders as $order)
                                        <a href="{{ route('gate.orders.show',$order->id) }}" class="mr-1" target="_blank">{{ $order->order_number }}</a>
                                        @if($loop->iteration != count($purchase->orders))
                                        ｜
                                        @endif
                                        @endforeach
                                    </div>
                                    <div class="card-primary card-outline col-12 mb-2"></div>
                                    @endif
                                    <table class="table table-sm">
                                        <thead class="table-info">
                                            <th width="5%" class="text-center align-middle text-sm">指定結案</th>
                                            <th width="5%" class="text-left align-middle text-sm">退貨數量</th>
                                            <th width="10%" class="text-left align-middle text-sm">廠商到貨日</th>
                                            <th width="5%" class="text-right align-middle text-sm">入庫量</th>
                                            <th width="13%" class="text-left align-middle text-sm">商家</th>
                                            <th width="13%" class="text-left align-middle text-sm">貨號</th>
                                            <th width="25%" class="text-left align-middle text-sm">品名</th>
                                            <th width="4%" class="text-center align-middle text-sm">單位</th>
                                            <th width="5%" class="text-right align-middle text-sm">數量</th>
                                            <th width="8%" class="text-right align-middle text-sm">採購價</th>
                                            <th width="7%" class="text-right align-middle text-sm">總價</th>
                                        </thead>
                                        <tbody>
                                            <form id="itemsform_order_{{ $purchase->id }}" method="POST">
                                                @foreach($purchase->items as $item)
                                                <tr>
                                                    <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                        <select class="form-control form-control-sm itemcheck" name="{{ $item->id }}" {{ $item->is_close == 1 ? 'readonly' : '' }}>
                                                            @if($item->is_close == 1)
                                                            <option value="1" {{ $item->is_close == 1 ? 'selected' : '' }}>是</option>
                                                            @else
                                                            <option value="0">否</option>
                                                            <option value="1" {{ $item->is_close == 1 ? 'selected' : '' }}>是</option>
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                        @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                        @if($item->is_lock == 0)
                                                        @if($purchase->status != -1 && $item->stockinQty > 0)
                                                            @if($item->is_del == 0)
                                                            <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                            <input type="number" min="0" max="{{ $item->stockinQty }}" class="form-control form-control-sm itemqtymodify" name="{{ $item->id }}" placeholder="0">
                                                            @endif
                                                        @endif
                                                        @if(strstr($item->sku,'BOM'))
                                                        @if(count($item->package)>0)
                                                        @foreach($item->package as $package)
                                                        @if($package->stockinQty > 0)
                                                        <input type="number" min="0" max="{{ $item->quantity }}" class="form-control form-control-sm itemqtymodify" name="{{ $item->id }}" placeholder="0">
                                                        @break
                                                        @endif
                                                        @endforeach
                                                        @endif
                                                        @endif
                                                        @endif
                                                        @endif
                                                    </td>
                                                    <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_arrival_date }}</span></td>
                                                    <td class="text-right align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->stockinQty == 0 ? null : $item->stockinQty - $item->returnQty }}</span>
                                                        @if(count($item->returns) > 0 && strstr($item->sku,'EC'))
                                                        <span class="text-primary" data-toggle="popover" title="退貨資訊" data-placement="right" data-content="
                                                            @foreach($item->returns as $return)
                                                            退貨單號：{{ $return->return_discount_no }} | 鼎新退貨單號： {{ $return->erp_return_discount_no }} | 退貨數量：{{ $return->quantity }}<br>
                                                            @endforeach
                                                            ">
                                                        <span class="badge badge-danger badge-sm text-xs">退</span>
                                                        </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_name }}</span></td>
                                                    <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->sku }}</span>
                                                        @if(!empty($item->stockin_date))
                                                        <span data-toggle="popover" class="text-primary stockin_{{ $item->id }}" data-content="
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
                                                    <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span></td>
                                                    <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price * $item->quantity }}</span></td>
                                                </tr>
                                                @if(strstr($item->sku,'BOM'))
                                                    @if(count($item->package)>0)
                                                    <tr class="item_package_{{ $item->id }} m-0 p-0">
                                                        <td colspan="11" class="text-sm p-0">
                                                            <table width="100%" class="table-sm m-0 p-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="5%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                        <th width="5%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                        <th width="10%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                        <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">入庫量</th>
                                                                        <th width="13%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                        <th width="13%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                        <th width="25%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                        <th width="4%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                        <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                        <th width="8%" class="text-right align-middle text-sm" style="border: none; outline: none">採購價</th>
                                                                        <th width="7%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($item->package as $packageItem)
                                                                    <tr>
                                                                        <td class="text-left align-middle text-sm" ></td>
                                                                        <td class="text-left align-middle text-sm" ></td>
                                                                        <td class="text-left align-middle text-sm" ></td>
                                                                        <td class="text-right align-middle text-sm" >
                                                                            <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['stockinQty'] == 0 ? null : $packageItem['stockinQty'] - $packageItem['returnQty'] }}</span>
                                                                            @if(count($packageItem->returns) > 0)
                                                                            <span class="text-primary" data-toggle="popover" title="退貨資訊" data-placement="right" data-content="
                                                                                @foreach($packageItem->returns as $return)
                                                                                退貨單號：{{ $return->return_discount_no }}
                                                                                退貨數量：{{ $return->quantity }}<br>
                                                                                @endforeach
                                                                                ">
                                                                            <span class="badge badge-danger badge-sm text-xs">退</span>
                                                                            </span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-left align-middle text-sm" ></td>
                                                                        <td class="text-left align-middle text-sm" >
                                                                            <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['sku'] }}</span>
                                                                            @if(!empty($packageItem->stockin_date))
                                                                            <span data-toggle="popover" class="text-primary stockin_{{ $item->id }}" data-content="
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
                                                <tr>
                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                    <td class="text-left align-middle text-primary text-bold">
                                                        <a href="javascript:" onclick="itemQtyModifySend({{ $purchase->id }}, this)" class="itemqtymodify badge badge-danger mr-2">確認退貨</a>
                                                    </td>
                                                    <td colspan="10">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">退貨日期</span>
                                                            </div>
                                                            <input type="datetime" class="form-control form-control-sm datepicker col-2" id="return_date" value="{{ date('Y-m-d') }}" placeholder="填寫退貨日期，未填寫則為今日" required>
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">退貨備註</span>
                                                            </div>
                                                            <input type="text" class="form-control form-control-sm" id="memo" value="" placeholder="填寫退貨備註，將顯示於ERP備註欄中">
                                                        </div>
                                                    </td>
                                                    @endif
                                                </tr>
                                            </form>
                                        </tbody>
                                    </table>
                                    @if(count($purchase->checkStockin) != 0)
                                    <span class="text-danger">已有商品入庫，無法修改採購單，需要調整或退庫，請至入退庫管理功能。</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
    <form id="syncForm" action="{{ url('purchases/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="cancelForm" action="{{ url('purchases/cancel') }}" method="POST">
        @csrf
    </form>
    <form id="returnForm" action="{{ route('gate.purchases.productReturn', $purchase->id) }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css') }}">
{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">
@endsection

@section('script')
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
@endsection

@section('JsValidator')
{{-- {!! JsValidator::formRequest('App\Http\Requests\Admin\MainmenusRequest', '#myform'); !!} --}}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";

        $('[data-toggle="popover"]').popover({
            html: true,
            sanitize: false,
        });

        //Initialize Select2 Elements
        $('.select2').select2();

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
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

    })(jQuery);

    function itemQtyModify(e){
        let html = '';
        let orderStatus = {{ $purchase->status }};
        if(orderStatus == -1){
                html = '此採購單已被取消，無法修改數量';
        }
        $('#item_modify_info').html(html);
        $('#item_modify_info').toggle('display');
        orderStatus != -1 ? $('.itemqtymodify').toggle('display') : '';
        $(e).html() == '修改數量' ? $(e).html('取消修改') : $(e).html('修改數量');
    }

    function itemQtyModifySend(id, e){
        if(confirm('請確認是否要退貨??，因為影響的資料將非常多，不可回溯。')){
            let chkZero = 0;
            let form = $('#returnForm');
            let memo = $('#memo').val();
            let adminMemo = $('<input type="hidden" class="formappend" name="memo" value="'+memo+'">');
            form.append(adminMemo);
            let return_date = $('#return_date').val();
            if(return_date){
                let returnDate = $('<input type="hidden" class="formappend" name="return_date" value="'+return_date+'">');
                form.append(returnDate);
            }else{
                alert('退貨日期必需填寫。');
                return;
            }
            let type = $('<input type="hidden" class="formappend" name="type" value="return">');
            form.append(type);
            let itemIds = $('.itemqtymodify').serializeArray().map( item => item.name );
            let itemQtys = $('.itemqtymodify').serializeArray().map( item => item.value );
            let itemChecks = $('.itemcheck').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                itemQtys[i] == 0 || itemQtys[i] == null ? chkZero++ : '';
                    let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                    let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][qty]" value="'+itemQtys[i]+'">');
                    let tmp3 = $('<input type="hidden" class="formappend" name="items['+i+'][close]" value="'+itemChecks[i]+'">');
                    form.append(tmp1);
                    form.append(tmp2);
                    form.append(tmp3);
            }
            if(chkZero == itemIds.length){
                alert('請輸入數量。');
            }else{
                form.submit();
            }
        }
    }

</script>
@endsection
