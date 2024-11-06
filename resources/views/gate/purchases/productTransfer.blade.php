@extends('gate.layouts.master')

@section('title', '商品貨號轉換')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>商品貨號轉換</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('productTransfer') }}">商品貨號轉換</a></li>
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
                                <span class="text-danger text-bold">注意!! 轉換的貨號務必輸入鼎新貨號。</span>
                            </div>
                            <div class="float-right">
                                <div class="input-group input-group-sm align-middle align-items-middle">
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($productModels) ? number_format($productModels->total()) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="searchForm" role="form" action="{{ url('productTransfer') }}" method="get">
                                <div class="row">
                                    <div class="col-3 mb-2">
                                        <label class="control-label" for="gtin13">商品條碼:</label>
                                        <input type="text" class="form-control" id="gtin13" name="gtin13" placeholder="填寫商品條碼: 417XXXXXXX" value="{{ isset($gtin13) ? $gtin13 ?? '' : '' }}" autocomplete="off" />
                                    </div>
                                    <div class="col-3 mb-2">
                                        <label for="sku">iCarry商品貨號:</label>
                                        <input type="text" inputmode="numeric" class="form-control" id="sku" name="sku" placeholder="填寫iCarry商品貨號: EC002134XX" value="{{ isset($sku) && $sku ? $sku : '' }}" autocomplete="off" />
                                    </div>
                                    <div class="col-3 mb-2">
                                        <label class="control-label" for="digiwin_no">鼎新商品貨號:</label>
                                        <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫鼎新商品貨號: 5TW0XXXXXX" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                    </div>
                                    <div class="col-3 mb-2">
                                        <label class="control-label" for="digiwin_no">轉換後貨號(對應原始商品鼎新貨號):</label>
                                        <input type="text" class="form-control" id="origin_digiwin_no" name="origin_digiwin_no" placeholder="填寫鼎新商品貨號: 5TW0XXXXXX" value="{{ isset($origin_digiwin_no) ? $origin_digiwin_no ?? '' : '' }}" autocomplete="off" />
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                    </div>
                                </div>
                            </form>
                            @if(count($productModels) > 0)
                            <hr>
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="10%">條碼號碼</th>
                                        <th class="text-left text-sm" width="10%">iCarry貨號</th>
                                        <th class="text-left text-sm" width="10%">鼎新貨號</th>
                                        <th class="text-left text-sm" width="20%">商品名稱</th>
                                        <th class="text-left text-sm" width="10%">轉換貨號</th>
                                        <th class="text-center text-sm" width="5%">修改</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($productModels as $productModel)
                                    <form action="{{ route('gate.productTransfer.update', $productModel->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="_method" value="PATCH">
                                        <tr>
                                            <td class="text-left text-sm align-middle">
                                                {{ $productModel->gtin13 }}
                                            </td>
                                            <td class="text-left text-sm align-middle">
                                                {{ $productModel->sku }}
                                            </td>
                                            <td class="text-left text-sm align-middle">
                                                {{ $productModel->digiwin_no }}
                                            </td>
                                            <td class="text-left text-sm align-middle">
                                                {{ $productModel->product_name }}
                                            </td>
                                            <td class="text-left text-sm align-middle">
                                                <input class="form-control" type="text" name="origin_digiwin_no" value="{{ $productModel->origin_digiwin_no }}">
                                            </td>
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                            <td class="text-center align-middle">
                                                <button type="submit" class="btn btn-primary">修改</button>
                                            </td>
                                            @endif
                                        </tr>
                                    </form>
                                    @endforeach
                                </tbody>

                            </table>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($productModels) ? number_format($productModels->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $productModels->appends($appends)->render() }}
                                @else
                                {{ $productModels->render() }}
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
