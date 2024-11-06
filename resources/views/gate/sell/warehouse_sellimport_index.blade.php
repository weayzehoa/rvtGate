@extends('gate.layouts.master')

@section('title', '倉庫出貨資料管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>倉庫出貨資料管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('warehouseSellImports') }}">倉庫出貨資料管理</a></li>
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
                            <div class="float-left">
                                <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                <button id="delete" class="btn btn-sm btn-danger mr-2" title="一鍵刪除">一鍵刪除</button>
                                @endif
                                <form id="deleteForm" action="{{ route('gate.warehouseSellImports.delete') }}" method="POST">
                                    <input type="hidden" name="type" value="warehouse">
                                    @csrf
                                </form>
                            </div>
                            <div class="float-right">
                                <div class="input-group input-group-sm align-middle align-items-middle">
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($sellImports) ? number_format($sellImports->total()) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="search" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('warehouseSellImports') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label for="order_number">iCarry訂單編號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單單號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="gtin13">商品條碼:</label>
                                                        <input type="text" class="form-control" id="gtin13" name="gtin13" placeholder="填寫商品條碼: 417XXXXXXX" value="{{ isset($gtin13) ? $gtin13 ?? '' : '' }}" autocomplete="off" />
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
                                        <th class="text-left text-sm" width="10%">訂單號碼</th>
                                        <th class="text-left text-sm" width="10%">條碼號碼</th>
                                        <th class="text-left text-sm" width="20%">商品名稱</th>
                                        <th class="text-left text-sm" width="10%">出貨日期</th>
                                        <th class="text-left text-sm" width="10%">物流號碼</th>
                                        <th class="text-right text-sm" width="5%">數量</th>
                                        <th class="text-center text-sm" width="5%">狀態</th>
                                        <th class="text-left text-sm" width="20%">異常原因</th>
                                        <th class="text-center text-sm" width="5%">刪除</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sellImports as $sellImport)
                                    <tr>
                                        <td class="text-left text-sm align-middle">
                                            <a href="{{ url('orders').'?order_number='.$sellImport->order_number }}" target="_blank">{{ $sellImport->order_number }}</a>
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $sellImport->gtin13 }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $sellImport->product_name }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $sellImport->sell_date }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $sellImport->shipping_number }}
                                        </td>
                                        <td class="text-right text-sm align-middle">
                                            {{ $sellImport->quantity }}
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            @if($sellImport->status == -1)
                                            <span class="text-danger text-bold">異常</span>
                                            @elseif($sellImport->status == 0)
                                            未處理
                                            @elseif($sellImport->status == 1)
                                            已完成
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">{{ $sellImport->memo }}</td>
                                        <td class="text-center align-middle">
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        @if($sellImport->status != 1)
                                            <form action="{{ route('gate.warehouseSellImports.destroy', $sellImport->id) }}" method="POST">
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

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#search').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
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
