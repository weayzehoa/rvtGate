@extends('gate.layouts.master')

@section('title', '廠商直寄資料管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>廠商直寄資料管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('vendorSellImports') }}">廠商直寄資料管理</a></li>
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
                                        <form id="executeForm" action="{{ route('gate.vendorSellImports.executeImport') }}" method="POST">
                                            @csrf
                                        </form>
                                        <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <button class="btn btn-sm btn-danger multiProcess mr-2" value="delete" disabled>刪除選擇</button>
                                        @endif
                                        <form id="deleteForm" action="{{ route('gate.vendorSellImports.delete') }}" method="POST">
                                            <input type="hidden" name="type" value="directShip">
                                            @csrf
                                        </form>
                                        <form id="multiProcessForm" action="{{ route('gate.vendorSellImports.multiProcess') }}" method="POST">
                                            @csrf
                                        </form>
                                        <button class="btn btn-sm btn-info multiProcess mr-2" value="process" disabled><span>多筆處理</span></button>
                                        <div class="icheck-primary d-inline mr-2">
                                            <input type="radio" id="selectorder" name="multiProcess" value="selected">
                                            <label for="selectorder">自行勾選 <span id="chkallbox_text"></span></label>
                                        </div>
                                        <div class="icheck-primary d-inline mr-2">
                                            <input type="radio" id="chkallbox" name="multiProcess" value="allOnPage">
                                            <label for="chkallbox">目前頁面全選</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($sellImports) ? number_format($sellImports->total()) : 0 }}</span>
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
                                <form id="searchForm" role="form" action="{{ url('vendorSellImports') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label for="order_number">iCarry訂單編號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單單號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="digiwin_no">鼎新貨號:</label>
                                                        <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫鼎新品號: 5TWXXXXXXX" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="product_name">商品名稱:</label>
                                                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="sell_date">出貨日期:</label>
                                                        <input type="datetime" class="form-control datepicker" id="sell_date" name="sell_date" placeholder="格式：2016-06-06" value="{{ isset($sell_date) ? $sell_date ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="vendor_name">物流資訊:</label>
                                                        <input type="text" class="form-control" id="shipping_number" name="shipping_number" placeholder="填寫物流資訊或單號" value="{{ isset($shipping_number) ? $shipping_number ?? '' : '' }}" autocomplete="off" />
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
                                            <div class="col-6">
                                                <label for="status">狀態:</label>
                                                <select class="form-control" id="status" size="5" multiple>
                                                    <option value="-2" {{ isset($status) ? in_array(-2,explode(',',$status)) ? 'selected' : '' : 'selected' }}  class="text-danger">銷貨異常</option>
                                                    <option value="-1" {{ isset($status) ? in_array(-1,explode(',',$status)) ? 'selected' : '' : 'selected' }}  class="text-danger">資料異常</option>
                                                    <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">尚未處理</option>
                                                    <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">已完成處理</option>
                                                </select><input type="hidden" value="-2,-1,0,1" name="status" id="status_hidden" />
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
                            @if(count($sellImports) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="2%"></th>
                                        <th class="text-left text-sm" width="8%">採購單號碼</th>
                                        <th class="text-left text-sm" width="8%">訂單號碼</th>
                                        <th class="text-left text-sm" width="8%">鼎新貨號</th>
                                        <th class="text-left text-sm" width="13%">商品名稱</th>
                                        <th class="text-left text-sm" width="7%">出貨日期</th>
                                        <th class="text-left text-sm" width="10%">物流資訊</th>
                                        <th class="text-right text-sm" width="5%">數量</th>
                                        <th class="text-center text-sm" width="5%">狀態</th>
                                        <th class="text-left text-sm" width="17%">異常原因</th>
                                        <th class="text-center text-sm" width="4%">處理</th>
                                        <th class="text-center text-sm" width="4%">刪除</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sellImports as $sellImport)
                                    <tr>
                                        @if($sellImport->status != 1)
                                        <form id="myform" action="{{ route('gate.vendorSellImports.update', $sellImport->id) }}" method="POST">
                                            <input type="hidden" name="_method" value="PATCH">
                                            @csrf
                                        @endif
                                        <td class="text-left text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="checkbox" class="chk_box_{{ $sellImport->id }}" name="chk_box" value="{{ $sellImport->id }}">
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="text" class="form-control form-control-sm " name="purchase_no" value="{{ $sellImport->purchase_no }}">
                                            @else
                                            <a href="{{ url('purchases').'?purchase_no='.$sellImport->purchase_no }}" target="_blank">{{ $sellImport->purchase_no }}</a>
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="text" class="form-control form-control-sm " name="order_number" value="{{ $sellImport->order_number }}">
                                            @else
                                            <a href="{{ url('orders').'?order_number='.$sellImport->order_number }}" target="_blank">{{ $sellImport->order_number }}</a>
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="text" class="form-control form-control-sm " name="digiwin_no" value="{{ $sellImport->digiwin_no }}">
                                            @else
                                            {{ $sellImport->digiwin_no }}
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="text" class="form-control form-control-sm " name="product_name" value="{{ $sellImport->product_name }}">
                                            @else
                                            {{ $sellImport->product_name }}
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="text" class="form-control form-control-sm datepicker" name="sell_date" value="{{ $sellImport->sell_date }}">
                                            @else
                                            {{ $sellImport->sell_date }}
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle text-warp">
                                            @if($sellImport->status != 1)
                                            <input type="text" class="form-control form-control-sm" name="shipping_number" value="{{ $sellImport->shipping_number }}">
                                            @else
                                            {{ $sellImport->shipping_number }}
                                            @endif
                                        </td>
                                        <td class="text-right text-sm align-middle">
                                            @if($sellImport->status != 1)
                                            <input type="number" class="form-control form-control-sm text-right" name="quantity" value="{{ $sellImport->quantity }}">
                                            @else
                                            {{ $sellImport->quantity }}
                                            @endif
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            @if($sellImport->status < 0)
                                            <span class="text-danger text-bold">異常</span>
                                            @elseif($sellImport->status == 0)
                                            未處理
                                            @elseif($sellImport->status == 1)
                                            已完成
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">{{ $sellImport->memo }}</td>
                                        <td class="text-center align-middle">
                                        @if($sellImport->status < 0)
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                                <button type="submit" class="btn btn-sm btn-primary">修改</button>
                                            @endif
                                        @elseif($sellImport->status == 0)
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                                <button type="submit" class="btn btn-sm btn-primary">修改</button>
                                            @endif
                                            @if(in_array($menuCode.'E',explode(',',Auth::user()->power)))
                                                <button type="button" class="btn btn-sm btn-success execute" value="{{ $sellImport->id }}">處理</button>
                                            @endif
                                        @endif
                                        </td>
                                        </form>
                                        <td class="text-center align-middle">
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        @if($sellImport->status != 1)
                                            <form action="{{ route('gate.vendorSellImports.destroy', $sellImport->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <h3>尚無資料</h3>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($sellImports) ? number_format($sellImports->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $sellImports->appends($appends)->render() }}
                                @else
                                {{ $sellImports->render() }}
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
        $("#chkallbox_text").text("("+num+"/"+num_all+")");

        $('input[name="chk_box"]').change(function(){
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            num > 0 ? $("#selectorder").prop("checked",true) : $("#selectorder").prop("checked",false);
            if(num == 0){
                $('#chkallbox').prop("checked",false);
                $('.multiProcess').prop("disabled",true);
            }else if(num > 0){
                $("#selectorder").prop("checked",true)
                $('.multiProcess').prop("disabled",false);
            }else if(num == num_all){
                $("#chkallbox").prop("checked",true);
                $('.multiProcess').prop("disabled",false);
            }
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('input[name="multiProcess"]').click(function(){
            if($(this).val() == 'allOnPage'){
                $('input[name="chk_box"]').prop("checked",true);
                $('.multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",false);
            }else if($(this).val() == 'selected'){
                $('input[name="chk_box"]').prop("checked",false);
                $('.multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",false);
            }else if($(this).val() == 'byQuery'){
                $('input[name="chk_box"]').prop("checked",false);
                $('.multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",true);
            }else{
                $('.multiProcess').prop("disabled",true);
                $('#oit').prop("disabled",false);
            }
            $('#orderSearchForm').hide();
            $('#showForm').html('使用欄位查詢');
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('.multiProcess').click(function (e){
            let method = $(this).val();
            let form = $('#multiProcessForm');
            let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
            if(ids.length > 0){
                for(let i=0;i<ids.length;i++){
                        form.append($('<input type="hidden" class="formappend" name="ids['+i+']">').val(ids[i]));
                    }
                form.append($('<input type="hidden" class="formappend" name="method">').val(method));
                form.submit();
            }else{
                alert('請先選擇要處理或刪除的資料');
            }
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
</script>
@endsection
