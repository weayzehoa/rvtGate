@extends('gate.layouts.master')

@section('title', '自動入庫商品管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>自動入庫商品管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('excludeProducts') }}">自動入庫商品管理</a></li>
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

                            </div>
                            <div class="float-right">
                                <div class="input-group input-group-sm align-middle align-items-middle">
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($products) ? number_format($products->total()) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="product_data_add">
                                    <div class="card-body">
                                        <div class="row">
                                            <form class="col-4 offset-1" action="{{ route('gate.autoStockinProduct.store') }}" method="POST">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="text" class="form-control col-12" name="digiwin_no" value="" placeholder="請輸入鼎新貨號" autocomplete="off" required>
                                                    <button type="submit" class="btn btn-success" >新增</button>
                                                </div>
                                            </form>
                                            <form class="col-5 offset-1" action="{{ route('gate.autoStockinProduct.index') }}" method="GET">
                                                <div class="input-group">
                                                    <input type="text" class="form-control col-12" name="digiwin_no" value="{{ isset($digiwin_no) ? $digiwin_no : '' }}" placeholder="請輸入鼎新貨號" autocomplete="off" >
                                                    <select class="form-control" id="list" name="list">
                                                        <option value="50" {{ isset($list) && $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                                        <option value="100" {{ isset($list) && $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                                        <option value="300" {{ isset($list) && $list == 300 ? 'selected' : '' }}>每頁 300 筆</option>
                                                        <option value="500" {{ isset($list) && $list == 500 ? 'selected' : '' }}>每頁 500 筆</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary" >查詢</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @if(!empty($products))
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left" width="20%">鼎新貨號</th>
                                        <th class="text-left" width="20%">商家</th>
                                        <th class="text-left" width="50%">商品名稱</th>
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="10%">刪除</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                    <tr>
                                        <td class="text-left text-sm align-middle">{{ $product->digiwin_no }}</td>
                                        <td class="text-left text-sm align-middle">{{ $product->vendor_name }}</td>
                                        <td class="text-left text-sm align-middle">{{ $product->product_name }}</td>
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.autoStockinProduct.destroy', $product->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                        @endif
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($products) ? number_format($products->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $products->appends($appends)->render() }}
                                @else
                                {{ $products->render() }}
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
{{-- Ekko Lightbox --}}
<link rel="stylesheet" href="{{ asset('vendor/ekko-lightbox/dist/ekko-lightbox.css') }}">
{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css') }}">
@endsection

@section('script')
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });
    })(jQuery);
</script>
@endsection
