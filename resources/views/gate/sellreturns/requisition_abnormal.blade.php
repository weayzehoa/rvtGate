@extends('gate.layouts.master')

@section('title', '調撥異常提示')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>調撥異常提示</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('requisitionAbnormal') }}">調撥異常提示</a></li>
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
                                        <button class="btn btn-sm btn-info multiProcess mr-2" value="process" disabled><span>多筆處理</span></button>
                                        <div class="icheck-primary d-inline mr-2">
                                            <input type="radio" id="selectorder" name="multiProcess" value="selected">
                                            <label for="selectorder">自行勾選 <span id="chkallbox_text"></span></label>
                                        </div>
                                        <div class="icheck-primary d-inline mr-2">
                                            <input type="radio" id="chkallbox" name="multiProcess" value="allOnPage">
                                            <label for="chkallbox">目前頁面全選</label>
                                        </div>
                                        <form id="multiProcessForm" action="{{ route('gate.requisitionAbnormal.multiProcess') }}" method="POST">
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($abnormals) ? number_format($abnormals->total()) : 0 }}</span>
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
                                <form id="searchForm" role="form" action="{{ url('requisitionAbnormal') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-3 mt-2">
                                                <label for="gtin13">商品條碼:</label>
                                                <input type="text" class="form-control" id="gtin13" name="gtin13" placeholder="填寫商品條碼" value="{{ isset($gtin13) && $gtin13 ? $gtin13 : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="product_name">商品名稱:</label>
                                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="stockin_date">入庫日期區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="stockin_date" name="stockin_date" placeholder="格式：2016-06-06" value="{{ isset($stockin_date) ? $stockin_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="stockin_date_end" name="stockin_date_end" placeholder="格式：2016-06-06" value="{{ isset($stockin_date_end) ? $stockin_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="expiry_date">效期日期區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="expiry_date" name="expiry_date" placeholder="格式：2016-06-06" value="{{ isset($expiry_date) ? $expiry_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="expiry_date_end" name="expiry_date_end" placeholder="格式：2016-06-06" value="{{ isset($expiry_date_end) ? $expiry_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="is_chk">狀態:</label>
                                                <select class="form-control" id="is_chk">
                                                    <option value="" {{ !isset($is_chk) ? 'selected' : '' }}  class="text-danger">不拘</option>
                                                    <option value="0"  {{ isset($is_chk) ? in_array(0,explode(',',$is_chk)) ? 'selected' : '' : '' }} class="text-secondary">未處理</option>
                                                    <option value="1"  {{ isset($is_chk) ? in_array(1,explode(',',$is_chk)) ? 'selected' : '' : '' }} class="text-primary">已處理</option>
                                                </select>
                                            </div>
                                            <div class="col-3 mt-2">
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
                            @if(count($abnormals) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="3%"></th>
                                        <th class="text-left text-sm" width="10%">商品條碼</th>
                                        <th class="text-left text-sm" width="25%">商品名稱</th>
                                        <th class="text-left text-sm" width="8%">入庫日期</th>
                                        <th class="text-left text-sm" width="8%">商品效期</th>
                                        <th class="text-right text-sm" width="5%">數量</th>
                                        <th class="text-left text-sm" width="20%">異常原因</th>
                                        <th class="text-center text-sm" width="7%">處理日期</th>
                                        <th class="text-left text-sm" width="7%">處理者</th>
                                        <th class="text-center text-sm" width="7%">處理</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($abnormals as $abnormal)
                                    <tr>
                                        <td class="text-left text-sm align-middle">
                                            @if($abnormal->is_chk != 1)
                                            <input type="checkbox" class="chk_box_{{ $abnormal->id }}" name="chk_box" value="{{ $abnormal->id }}">
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $abnormal->gtin13 }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $abnormal->product_name }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $abnormal->stockin_date }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $abnormal->expiry_date }}
                                        </td>
                                        <td class="text-right text-sm align-middle">
                                            {{ $abnormal->quantity }}
                                        </td>
                                        <td class="text-left text-sm align-middle">{{ $abnormal->memo }}</td>
                                        <td class="text-center text-sm align-middle">{{ !empty($abnormal->chk_date) ? explode(' ',$abnormal->chk_date)[0] : null }}<br>{{ !empty($abnormal->chk_date) ? explode(' ',$abnormal->chk_date)[1] : null }}</td>
                                        <td class="text-left text-sm align-middle">{{ $abnormal->admin_name }}</td>
                                        <td class="text-center align-middle">
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                                @if($abnormal->is_chk == 0)
                                                <form action="{{ route('gate.requisitionAbnormal.update', $abnormal->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="_method" value="PATCH">
                                                    <input type="hidden" name="is_chk" value="1">
                                                    <button type="submit" class="chk-btn btn btn-danger">處理</button>
                                                </form>
                                                @else
                                                    <button type="button" class="chk-btn btn btn-success" disabled>已處理</button>
                                                @endif
                                            @endif
                                        </td>
                                        </form>
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($abnormals) ? number_format($abnormals->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $abnormals->appends($appends)->render() }}
                                @else
                                {{ $abnormals->render() }}
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

        $('.datetimepicker').datetimepicker({
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
            if(confirm('請確認是否要執行多筆處理?')){
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
                    alert('請先選擇要處理的資料');
                }
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
