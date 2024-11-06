@extends('gate.layouts.master')

@section('title', '訂單取消庫存提示')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>訂單取消庫存提示</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orderCancel') }}">訂單取消庫存提示</a></li>
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
                                <div class="col-6">
                                    <div class="float-left d-flex align-items-center">
                                        <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                        <form id="multiProcessForm" action="{{ route('gate.orderCancel.process') }}" method="POST">
                                            @csrf
                                        </form>
                                        <button class="btn btn-sm btn-warning multiProcess mr-2" value="process" disabled><span>多筆修改</span></button>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($orderCancels) ? number_format($orderCancels->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-body">
                            <div id="search" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('orderCancel') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-2 mt-2">
                                                <label for="order_number">iCarry訂單編號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單單號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-2 mt-2">
                                                <label class="control-label" for="purchase_no">iCarry採購單號:</label>
                                                <input type="text" class="form-control" id="purchase_no" name="purchase_no" placeholder="填寫iCarry採購單號" value="{{ isset($purchase_no) ? $purchase_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-2 mt-2">
                                                <label class="control-label" for="digiwin_no">鼎新貨號:</label>
                                                <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫鼎新品號: 5TWXXXXXXX" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-2 mt-2">
                                                <label class="control-label" for="digiwin_no">商品品名:</label>
                                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品品名: 鳳梨酥" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-2 mt-2">
                                                <label for="status">狀態:</label>
                                                <select class="form-control" id="is_chk" name="is_chk">
                                                    <option value=""  {{ isset($is_chk) ? $is_chk == "" ? 'selected' : '' : 'selected' }} class="text-secondary">不拘</option>
                                                    <option value="0"  {{ isset($is_chk) ? in_array(0,explode(',',$is_chk)) ? 'selected' : '' : '' }} class="text-danger">未處理</option>
                                                    <option value="1"  {{ isset($is_chk) ? in_array(1,explode(',',$is_chk)) ? 'selected' : '' : '' }} class="text-primary">已處理</option>
                                                </select>
                                            </div>
                                            <div class="col-2 mt-2">
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
                                    <div class="col-12 text-center mb-2">
                                        <button type="button" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                            @if(count($orderCancels) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="2%">
                                            <input type="checkbox" id="chkallbox" name="multiProcess" value="allOnPage">
                                        </th>
                                        <th class="text-left text-sm" width="8%">訂單號碼</th>
                                        <th class="text-left text-sm" width="10%">訂單鼎新貨號<br>採購單鼎新號</th>
                                        <th class="text-center text-sm" width="8%">廠商到貨日</th>
                                        <th class="text-center text-sm" width="4%">取消<br>數量</th>
                                        <th class="text-center text-sm" width="8%">取消日期<br>取消者</th>
                                        <th colspan="2" class="text-left text-sm" width="13%">採購單號<br>商家出貨單號</th>
                                        <th class="text-center text-sm" width="5%">應扣<br>數量</th>
                                        <th class="text-center text-sm" width="5%">已處理<br>數量</th>
                                        <th class="text-center text-sm" width="5%">尚未處理<br>數量</th>
                                        <th class="text-left text-sm" width="15%">備註</th>
                                        <th class="text-center text-sm" width="8%">處理日期</th>
                                        <th class="text-left text-sm" width="5%">處理者</th>
                                        <th class="text-left text-sm" width="4%">處理</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orderCancels as $orderCancel)
                                    <tr>
                                        <td class="text-left text-sm align-middle">
                                            @if($orderCancel->is_chk ==0 && !empty($orderCancel->purchase_no))
                                            <input type="checkbox" class="chk_box_{{ $orderCancel->id }}" name="chk_box" value="{{ $orderCancel->id }}">
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle"><a href="{{ route('gate.orders.show',$orderCancel->order_id) }}" target="_blank">{{ $orderCancel->order_number }}</a></td>
                                        <td class="text-left text-sm align-middle">{{ $orderCancel->order_digiwin_no }}<br>{{ $orderCancel->purchase_digiwin_no }}</td>
                                        <td class="text-center text-sm align-middle">{{ $orderCancel->vendor_arrival_date }}</td>
                                        <td class="text-center text-sm align-middle">{{ $orderCancel->quantity }}</td>
                                        <td class="text-center text-sm align-middle">{{ explode(' ',$orderCancel->cancel_time)[0] }}<br>{{ $orderCancel->cancel_person }}</td>
                                        <td class="text-left text-sm align-middle">
                                            @if(!empty($orderCancel->purchase_order_id))
                                            <a href="{{ route('gate.purchases.show',$orderCancel->purchase_order_id ) }}" target="_blank">{{ $orderCancel->purchase_no }}</a><br>
                                            @else
                                            {{ $orderCancel->purchase_no }}<br>
                                            @endif
                                            @if(!empty($orderCancel->vendor_shipping_no))
                                            {{ $orderCancel->vendor_shipping_no }}
                                            @endif
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            @if($orderCancel->is_chk ==0 && !empty($orderCancel->purchase_no))
                                            <form action="{{ url('orderCancel/process') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $orderCancel->id }}">
                                                <button type="button" class="btn btn-sm badge badge-warning modify-btn">修改</button>
                                            </form>
                                            @endif
                                        </td>
                                        <form action="{{ route('gate.orderCancel.update', $orderCancel->id) }}" method="POST">
                                            <input type="hidden" name="_method" value="PATCH">
                                            @csrf
                                        <td class="text-center text-sm align-middle">
                                            @if($orderCancel->is_chk != 1)
                                            {{ $orderCancel->quantity }}
                                            @else
                                            @if(!empty($orderCancel->purchase_order_id))
                                            {{ $orderCancel->quantity }}
                                            @endif
                                            @endif
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            {{-- @if($orderCancel->is_chk != 1) --}}
                                            <input type="number" class="form-control form-control-sm text-center" name="deduct_quantity" value="{{ !empty($orderCancel->deduct_quantity) ? $orderCancel->deduct_quantity : 0 }}">
                                            {{-- @else --}}
                                            {{-- {{ $orderCancel->deduct_quantity }} --}}
                                            {{-- @endif --}}
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            @if(!empty($orderCancel->purchase_order_id))
                                            {{ $orderCancel->quantity - $orderCancel->deduct_quantity }}
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($orderCancel->is_chk != 1)
                                            <input type="text" class="form-control form-control-sm " name="memo" value="{{ $orderCancel->memo }}">
                                            @else
                                            {{ $orderCancel->memo }}
                                            @endif
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            {{ $orderCancel->chk_date }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $orderCancel->admin_name }}
                                        </td>
                                        <td class="text-left align-middle">
                                            @if($orderCancel->is_chk ==1)
                                            已處理
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                            <button type="submit" class="btn btn-sm btn-primary">返回</button>
                                            @endif
                                            @else
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                            <button type="submit" class="btn btn-sm btn-primary">處理</button>
                                            @endif
                                            @endif
                                        </td>
                                        </form>
                                    </tr>
                                    @if(!empty($orderCancel->purchase_no))
                                    <tr>
                                        <td class="text-left text-sm"></td>
                                        <td colspan="12" class="text-left text-sm">
                                            @if($orderCancel->direct_shipment == 1)
                                            <span class="text-primary "><i class="fas fa-truck" title="廠商直寄"></i></span>
                                            @endif
                                            {{ $orderCancel->product_name }}
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <h3>尚無資料</h3>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($orderCancels) ? number_format($orderCancels->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $orderCancels->appends($appends)->render() }}
                                @else
                                {{ $orderCancels->render() }}
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

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">

{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">
@endsection

@section('script')
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('.datepicker').datepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });

        $('#delete').click(function (e) {
            if(confirm('請確認是否要一次刪除未處理及異常資料?')){
                $('#deleteForm').submit();
            };
        });

        $('.execute').click(function (e) {
            let id = $(this).val();
            let form = $('#executeForm');
            form.append($('<input type="hidden" name="id" value="'+id+'">'));
            if(confirm('請確認是否要處理這筆資料?\n注意! 處理這筆資料同時會將相同的訂單資料一併處理，\n若有資料狀態異常時該筆資料將會被忽略不做處理。')){
                form.submit();
            };
        });

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#search').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });

        var num_all = $('input[name="chk_box"]').length;
        var num = $('input[name="chk_box"]:checked').length;

        $('input[name="chk_box"]').change(function(){
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            if(num == num_all){
                $("#chkallbox").prop("checked",true);
                $('.multiProcess').prop("disabled",false);
            }else if(num > 0){
                $('#chkallbox').prop("checked",false);
                $('.multiProcess').prop("disabled",false);
            }else if(num == 0){
                $('#chkallbox').prop("checked",false);
                $('.multiProcess').prop("disabled",true);
            }
        });

        $('input[name="multiProcess"]').click(function(){
            var chkAllbox = document.getElementById("chkallbox");
            if(chkAllbox.checked){
                $('input[name="chk_box"]').prop("checked",true);
                $('.multiProcess').prop("disabled",false);
            }else{
                $('input[name="chk_box"]').prop("checked",false);
                $('.multiProcess').prop("disabled",true);
            }
            $('#showForm').html('使用欄位查詢');
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('.multiProcess').click(function (e){
            if(confirm('請確認是否要修改這些資料?')){
                let method = $(this).val();
                let form = $('#multiProcessForm');
                let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
                if(ids.length > 0){
                    for(let i=0;i<ids.length;i++){
                            form.append($('<input type="hidden" class="formappend" name="id['+i+']">').val(ids[i]));
                        }
                    form.append($('<input type="hidden" class="formappend" name="method">').val(method));
                    form.submit();
                }else{
                    alert('請先選擇要修改的資料');
                }
            }
        });

        $('.modify-btn').click(function(e){
            if(confirm('請確認是否要修改這筆資料?')){
                $(this).parents('form').submit();
            };
        });
    })(jQuery);

    function unProcess(id){
        let qty = prompt('請輸入返回處理後的數量??');
        if(isInteger(qty) ){
            alert(qty);
        }else{
            alert('請填寫整數。');
        }
        return;
    }
    function isInteger(str) {
        if (typeof str !== 'string') {
            return false;
        }
        const num = Number(str);
        if (Number.isInteger(num) && num > 0) {
            return true;
        }
        return false;
    }
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
</script>
@endsection
