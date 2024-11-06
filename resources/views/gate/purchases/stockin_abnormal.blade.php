@extends('gate.layouts.master')

@section('title', '進貨異常列表')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>進貨異常列表</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('stockinAbnormals') }}">進貨異常列表</a></li>
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
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($stockinAbnormals) ? number_format($stockinAbnormals->total()) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($stockinAbnormals) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="27%">異常原因</th>
                                        <th class="text-left text-sm" width="10%">條碼/鼎新品號</th>
                                        <th class="text-left text-sm" width="18%">商品名稱</th>
                                        <th class="text-right text-sm" width="5%">入庫<br>數量</th>
                                        <th class="text-right text-sm" width="5%">需求<br>數量</th>
                                        <th class="text-right text-sm" width="5%">異常<br>數量</th>
                                        <th class="text-center text-sm" width="8%">處理日期</th>
                                        <th class="text-left text-sm" width="7%">處理者</th>
                                        @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                        <th class="text-center text-sm" width="5%">處理狀態</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockinAbnormals as $stockinAbnormal)
                                    <tr>
                                        <td class="text-left text-sm align-middle">{{ $stockinAbnormal->memo }}</td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $stockinAbnormal->gtin13 }}
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            {{ $stockinAbnormal->product_name }}
                                            @if($stockinAbnormal->direct_shipment == 1)
                                            <span class="text-primary "><i class="fas fa-truck" title="廠商直寄"></i></span>
                                            @endif
                                        </td>
                                        <td class="text-right text-sm align-middle">{{ $stockinAbnormal->stockin_quantity }}</td>
                                        <td class="text-right text-sm align-middle">{{ $stockinAbnormal->need_quantity }}</td>
                                        <td class="text-right text-sm align-middle">{{ $stockinAbnormal->quantity }}</td>
                                        <td class="text-center text-sm align-middle">{{ !empty($stockinAbnormal->chk_date) ? explode(' ',$stockinAbnormal->chk_date)[0] : null }}<br>{{ !empty($stockinAbnormal->chk_date) ? explode(' ',$stockinAbnormal->chk_date)[1] : null }}</td>
                                        <td class="text-left text-sm align-middle">{{ $stockinAbnormal->admin_name }}</td>
                                        @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            @if($stockinAbnormal->is_chk == 0)
                                            <form action="{{ route('gate.stockinAbnormals.update', $stockinAbnormal->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="PATCH">
                                                <button type="button" class="chk-btn btn btn-danger">處理</button>
                                            </form>
                                            @else
                                                <button type="button" class="chk-btn btn btn-success" disabled>已處理</button>
                                            @endif
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($stockinAbnormals) ? number_format($stockinAbnormals->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $stockinAbnormals->appends($appends)->render() }}
                                @else
                                {{ $stockinAbnormals->render() }}
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

        $('.chk-btn').click(function (e) {
            if(confirm('請確認是否已處理完成這筆資料?')){
                $(this).parents('form').append($('<input type="hidden" name="is_chk" value="1">'));
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
