@extends('gate.layouts.master')

@section('title', '折抵單/退貨單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>折抵單/退貨單管理</b><span class="badge badge-success text-sm">{{ $return->return_discount_no }}</span></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">折抵單/退貨單管理</a></li>
                        <li class="breadcrumb-item active">修改</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">折抵/退貨單資料</h3>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">類別</span>
                                        </div>
                                        <input type="text" class="form-control" value="{{ $return->type == 'A351' ? '退貨單' : '折抵單' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">中繼站單號</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $return->return_discount_no ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">鼎新單號</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $return->erp_return_discount_no ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">商家</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $return->vendor_name ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">採購單號</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $return->purchase_no }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">總數量</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $return->quantity ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">總金額</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $return->amount ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">稅金</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $return->tax ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">總計</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ ($return->amount + $return->tax) ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-8 mb-2">
                                    <form id="updateForm" action="{{ route('gate.returnDiscounts.update', $return->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="_method" value="PATCH">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">退貨日期</span>
                                            </div>
                                            <input type="text" class="col-2 form-control datepicker" name="return_date" value="{{ $return->return_date ?? date('Y-m-d') }}" required>
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">備註</span>
                                            </div>
                                            <input type="text" class=" form-control" name="memo" value="{{ $return->memo ?? null }}">
                                            <div class="input-group-prepend">
                                                @if($return->is_lock == 0)
                                                <button type="submit" class="btn btn-primary">修改</button>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                @if(!empty($return->orders))
                                <div class="col-12 mb-2">
                                    <span class="text-bold">iCarry 訂單：</span>
                                    @foreach($return->orders as $order)
                                    <a href="{{ route('gate.orders.show',$order->id) }}" class="mr-1" target="_blank">{{ $order->order_number }}</a>
                                    @if($loop->iteration != count($return->orders))
                                    ｜
                                    @endif
                                    @endforeach
                                </div>
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                @endif
                                <table class="table table-sm">
                                    <thead class="table-info">
                                        <th width="7%" class="text-left align-middle text-sm">
                                            @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                            @if($return->type == 'A351')
                                            @if($return->is_lock == 0)
                                            <a href="javascript:" class="badge badge-danger" onclick="itemQtyModify(this)">修改數量</a>
                                            @endif
                                            @endif
                                            @endif
                                        </th>
                                        <th width="7%" class="text-left align-middle text-sm">
                                            @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                            @if($return->type == 'A352')
                                            @if($return->is_lock == 0)
                                            <a href="javascript:" class="badge badge-primary" onclick="itemPriceModify(this)">修改金額</a>
                                            @endif
                                            @endif
                                            @endif
                                        </th>
                                        <th width="15%" class="text-left align-middle text-sm">商家</th>
                                        <th width="15%" class="text-left align-middle text-sm">貨號</th>
                                        <th width="20%" class="text-left align-middle text-sm">品名</th>
                                        <th width="5%" class="text-center align-middle text-sm">單位</th>
                                        <th width="5%" class="text-right align-middle text-sm">數量</th>
                                        <th width="8%" class="text-right align-middle text-sm">{{ $return->type == 'A351' ? '採購價' : '金額' }}</th>
                                        <th width="7%" class="text-right align-middle text-sm">總價</th>
                                    </thead>
                                    <tbody>
                                        {{-- <form id="itemsform_order_{{ $return->id }}" method="POST"> --}}
                                            @foreach($return->items as $item)
                                            <tr>
                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                    @if($return->status != -1)
                                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        {{-- <input type="number" class="form-control form-control-sm itemqtymodify" name="items[{{ $loop->iteration - 1 }}][quantity]" value="{{ $item->quantity }}" style="display:none"> --}}
                                                        @if($item->is_del == 0)
                                                        <input type="number" min="0" class="form-control form-control-sm itemqtymodify" name="{{ $item->id }}" value="{{ $item->quantity }}" style="display:none">
                                                        @endif
                                                    @endif
                                                    @endif
                                                </td>
                                                <td class="text-left align-middle text-sm item_price_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                    @if($return->status != -1)
                                                        <input type="hidden" name="price[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        {{-- <input type="number" class="form-control form-control-sm itemqtymodify" name="items[{{ $loop->iteration - 1 }}][quantity]" value="{{ $item->quantity }}" style="display:none"> --}}
                                                        @if($item->is_del == 0)
                                                        <input type="number" min="1" class="form-control form-control-sm itempricemodify" name="{{ $item->id }}" value="{{ $item->purchase_price }}" style="display:none" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
                                                        @endif
                                                    @endif
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
                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已取消)</span>@endif
                                                </td>
                                                <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->unit_name }}</span></td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $return->type == 'A351' ? number_format($item->quantity) : 0 }}</span></td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span></td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price * $item->quantity }}</span></td>
                                            </tr>
                                            @if(strstr($item->sku,'BOM'))
                                                @if(count($item->packages)>0)
                                                <tr class="item_package_{{ $item->id }} m-0 p-0">
                                                    <td colspan="11" class="text-sm p-0">
                                                        <table width="100%" class="table-sm m-0 p-0">
                                                            <thead>
                                                                <tr>
                                                                    <th width="7%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                    <th width="7%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                    <th width="20%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                    <th width="5%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                    <th width="8%" class="text-right align-middle text-sm" style="border: none; outline: none">單價</th>
                                                                    <th width="7%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($item->packages as $packageItem)
                                                                <tr>
                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                    <td class="text-left align-middle text-sm" ></td>
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
                                                                    <td class="text-left align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</span>@if($packageItem['is_del'] == 1)<span class="text-gray text-sm text-bold">(已取消)</span>@endif</td>
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
                                                <td class="text-left align-middle text-sm text-primary text-bold">
                                                    <a href="javascript:" onclick="itemQtyModifySend({{ $return->id }}, this)" class="itemqtymodify badge badge-danger mr-2" style="display:none">確認修改</a><br>
                                                    {{-- <span class="itemqtymodify text-sm" style="display:none">填0為取消</span> --}}
                                                </td>
                                                <td colspan="8" class="text-left align-middle text-sm text-primary text-bold">
                                                    <a href="javascript:" onclick="itemPriceModifySend({{ $return->id }}, this)" class="itempricemodify badge badge-primary mr-2" style="display:none">確認修改</a>
                                                </td>
                                                @endif
                                            </tr>
                                        {{-- </form> --}}
                                    </tbody>
                                </table>
                            </div>
                            <span class="text-danger">修改金額時，請輸入含稅金額。</span><br>
                            <span class="text-danger">折抵/退貨單若在鼎新已確認鎖定或已列入對帳單中，將無法修改數量或金額，<br>需要至鼎新將確認鎖定解除或將對帳單取消。</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="cancelForm" action="{{ url('returnDiscounts/cancel') }}" method="POST">
        @csrf
    </form>
    <form id="updateForm" action="{{ route('gate.returnDiscounts.update', $return->id) }}" method="POST">
        @csrf
        <input type="hidden" name="_method" value="PATCH">
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


        $('.btn-cancel').click(function (e) {
            let id = $(this).val();
            let form = $('#cancelForm');
            if(confirm('請確認是否要取消這筆採購單?')){
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
        });

    })(jQuery);

    function itemQtyModify(e){
        let html = '';
        let orderStatus = {{ $return->is_del }};
        if(orderStatus == 1){
                html = '此折抵/退貨單已被取消，無法修改數量';
        }
        $('#item_modify_info').html(html);
        $('#item_modify_info').toggle('display');
        orderStatus != 1 ? $('.itemqtymodify').toggle('display') : '';
        $(e).html() == '修改數量' ? $(e).html('取消修改') : $(e).html('修改數量');
    }

    function itemPriceModify(e){
        let html = '';
        let orderStatus = {{ $return->is_del }};
        if(orderStatus == 1){
                html = '此折抵/退貨單已被取消，無法修改數量';
        }
        $('#item_modify_info').html(html);
        $('#item_modify_info').toggle('display');
        orderStatus != -1 ? $('.itempricemodify').toggle('display') : '';
        $(e).html() == '修改金額' ? $(e).html('取消修改') : $(e).html('修改金額');
    }


    function itemQtyModifySend(id, e){
        if(confirm('請確認折抵/退貨單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            let itemIds = $('.itemqtymodify').serializeArray().map( item => item.name );
            let itemQty = $('.itemqtymodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][qty]" value="'+itemQty[i]+'">');
                form.append(tmp1);
                form.append(tmp2);
            }
            form.submit();
        }
    }

    function itemPriceModifySend(id, e){
        if(confirm('請確認折抵/退貨單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            let itemIds = $('.itempricemodify').serializeArray().map( item => item.name );
            let itemPrices = $('.itempricemodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                if(itemPrices[i] <= 0){
                    alert('新的金額不可小於等於0');
                    return;
                }else{
                    let tmp1 = $('<input type="hidden" class="formappend" name="price['+i+'][id]" value="'+itemIds[i]+'">');
                    let tmp2 = $('<input type="hidden" class="formappend" name="price['+i+'][price]" value="'+itemPrices[i]+'">');
                    form.append(tmp1);
                    form.append(tmp2);
                }
            }
            form.submit();
        }
    }

</script>
@endsection
