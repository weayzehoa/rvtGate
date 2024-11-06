@extends('gate.layouts.master')

@section('title', '管理員管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>訂單取消排除商品</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orderCancelProduct') }}">訂單取消排除商品</a></li>
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
                                <button type="button" class="btn btn-sm bg-success edit-btn mr-2" value="product_data_add">選擇商品</button>
                            </div>
                            <div class="float-right">
                                <div class="input-group input-group-sm align-middle align-items-middle">
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($products) ? number_format($products->total()) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="product_data_add" style="display:none">
                                <div class="card-primary card-outline mt-2"></div>
                                <form class="ProductForm" action="{{ route('gate.orderCancelProduct.store') }}" method="POST">
                                    @csrf
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-12">
                                                <div class="row">
                                                <div class="form-group col-2">
                                                    <label for="">使用產品類別搜尋產品</label>
                                                    <select id="selectByCategory" class="form-control select2bs4 select2-primary">
                                                        <option value="">產品類別</option>
                                                        @foreach($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_on == 0 ? ' [已停用]' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group col-3">
                                                    <label for="">使用商家名稱搜尋產品</label>
                                                    {{-- <select id="selectByVendor" class="form-control select2bs4 select2-primary" data-dropdown-css-class="select2-primary" > --}}
                                                    <select id="selectByVendor" class="form-control select2bs4 select2-primary">
                                                        <option value="">商家名稱</option>
                                                        @foreach($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}{{ $vendor->is_on == 0 ? ' [已停用]' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group col-3">
                                                    <label for="">使用關鍵字搜尋產品</label>
                                                    <div class="input-group">
                                                        <input class="form-control form-control-navbar" type="search" id="keyword" name="keyword" value="" placeholder="輸入商家或產品名稱搜尋..." aria-label="Search">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-info search-btn" type="button">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                            <div class="form-group col-12">
                                                <div class="row">
                                                    <div class="col-5">
                                                        <label>單一規格產品列表</label>
                                                        <select id="productSelect" class="form-control" size="15" multiple="multiple">
                                                        </select>
                                                    </div>
                                                    <div class="col-1">
                                                        <div>
                                                            <label>　</label><br><br><br>
                                                            <button type="button" id="productSelect_rightAll" class="btn btn-secondary btn-block"><i class="fas fa-angle-double-right"></i></button>
                                                            <button type="button" id="productSelect_rightSelected" class="btn btn-primary btn-block"><i class="fas fa-caret-right"></i></button>
                                                            <button type="button" id="productSelect_leftSelected" class="btn btn-primary btn-block"><i class="fas fa-caret-left"></i></button>
                                                            <button type="button" id="productSelect_leftAll" class="btn btn-secondary btn-block"><i class="fas fa-angle-double-left"></i></button>
                                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                                            <button type="submit" class="btn btn-success btn-block">儲存</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-5">
                                                        <label>已選擇單一規格產品</label>
                                                        <select name="product_model_id[]" id="productSelect_to" class="form-control" size="12" multiple="multiple">
                                                            @foreach($selectedProducts as $product)
                                                            <option value="{{ $product->product_model_id }}">{{ $product->vendor_name }}___{{ $product->product_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-1 align-items-middle">
                                                        <label>　</label><br><br><br><br>
                                                        <div class="col-12">
                                                            <button type="button" id="productSelect_move_up" class="btn btn-primary"><i class="fas fa-caret-up"></i></button>
                                                        </div>
                                                        <br><br><br><br>
                                                        <div class="col-12">
                                                            <button type="button" id="productSelect_move_down" class="btn btn-primary"><i class="fas fa-caret-down"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div class="card-primary card-outline mb-2"></div>
                            </div>
                            @if(!empty($products))
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="15%">商家</th>
                                        <th class="text-left text-sm" width="10%">iCarry品號</th>
                                        <th class="text-left text-sm" width="10%">鼎新貨號</th>
                                        <th class="text-left text-sm" width="30%">商品名稱</th>
                                        <th class="text-left text-sm" width="30%">規格</th>
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="5%">刪除</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                    <tr>
                                        <td class="text-left text-sm align-middle">{{ $product->vendor_name }}</td>
                                        <td class="text-left text-sm align-middle">{{ $product->sku }}</td>
                                        <td class="text-left text-sm align-middle">{{ $product->digiwin_no }}</td>
                                        <td class="text-left text-sm align-middle">{{ $product->product_name }}</td>
                                        <td class="text-left text-sm align-middle">{{ $product->serving_size }}</td>
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.orderCancelProduct.destroy', $product->id) }}" method="POST">
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
                            <h3>無商品資料</h3>
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
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
{{-- 顏色選擇器 --}}
<script src="{{ asset('vendor/vanilla-picker/dist/vanilla-picker.min.js') }}"></script>
{{-- Ekko Lightbox --}}
<script src="{{ asset('vendor/ekko-lightbox/dist/ekko-lightbox.min.js') }}"></script>
{{-- multiselect --}}
<script src="{{ asset('vendor/multiselect/dist/js/multiselect.min.js') }}"></script>
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
{{-- Ckeditor 4.x --}}
<script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
        });

        $('.select2').select2();

        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });

        $('.edit-btn').click(function(e){
            $('.'+$(this).val()).toggle();
            if($(this).html() == '選擇商品'){
                $(this).html('取消');
            }else if($(this).html() == '取消'){
                $(this).html('選擇商品');
            }
        });

        $(document).ready(function($) {
            $('#productSelect').multiselect({
                sort: false,
                search: {
                    left: '<input type="text" name="q" class="form-control" placeholder="輸入關鍵字，查詢下方產品，不需要按Enter即可查詢" />',
                    right: '<input type="text" name="q" class="form-control" placeholder="輸入關鍵字，查詢下方產品，不需要按Enter即可查詢" />',
                },
                fireSearch: function(value) {
                    return value.length > 0;
                }
            });
        });

        $('#selectByCategory').change(function(){
            $('input[name=keyword]').val('');
            $('#selectByVendor').find('option:not(:first)').prop('selected',false);
            if($(this).val()){
                search($(this).val(),'');
            }
        });

        $('#selectByVendor').change(function(){
            $('#selectByCategory').find('option:not(:first)').prop('selected',false);
            $('input[name=keyword]').val('');
            if($(this).val()){
                search('','',$(this).val());
            }
        });

        $('.search-btn').click(function(){
            $('#selectByVendor').find('option:not(:first)').prop('selected',false);
            $('#selectByCategory').find('option:not(:first)').prop('selected',false);
            var keyword = $('#keyword').val();
            if(keyword){
                search('',keyword,'');
            }
        });

        $('input[name=keyword]').keyup(function(){
            // $('#selectByVendor').val(null).trigger('selected');
            $('#selectByVendor').find('option:not(:first)').prop('selected',false);
            $('#selectByCategory').find('option:not(:first)').prop('selected',false);
            if($(this).val()){
                search('',$(this).val(),'');
            }
        });

        function search(category,keyword,vendor){
        let token = '{{ csrf_token() }}';
        // let id = '{{ isset($curation) ? $curation->id : '' }}';
        let selected = $('#productSelect_to').find('option');
        let ids = [];
        for(let x=0;x<selected.length;x++){
            ids[x] = selected[x].value;
        }
        $.ajax({
            type: "post",
            url: 'excludeProducts/getProducts',
            data: {ids: ids, category: category, keyword: keyword, vendor: vendor, _token: token },
            success: function(data) {
                var options = '';
                for(let i=0;i<data.length;i++){
                    options +='<option value="'+data[i]['id']+'">'+data[i]['vendor_name']+'___'+data[i]['name']+'</option>';
                }
                $('#productSelect').html(options);
            }
        });
    }

    })(jQuery);
</script>
@endsection
