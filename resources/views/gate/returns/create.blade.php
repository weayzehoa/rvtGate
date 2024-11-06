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
                    <h1 class="m-0 text-dark"><b>折抵單/退貨單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">折抵單/退貨單管理</a></li>
                        <li class="breadcrumb-item active">新增</li>
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
                                <h3 class="card-title">折抵/退貨資料</h3>
                            </div>
                            <div class="card-body">
                                <form class="curationProductForm" action="{{ route('gate.returnDiscounts.store') }}" method="POST">
                                    <input type="hidden" name="type" value="discount">
                                    @csrf
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-12">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label for="">選擇商家名稱搜尋產品</label>
                                                            <select id="selectByVendor" name="vendor_id" class="form-control select2bs4 select2-primary" data-dropdown-css-class="select2-primary" >
                                                                <option value="">商家名稱</option>
                                                                @foreach($vendors as $vendor)
                                                                <option value="{{ $vendor->id }}">{{ $vendor->is_on == 0 ? $vendor->name.' [已停用]' : $vendor->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="">備註</label>
                                                            <textarea rows="5" class="form-control" name="memo"></textarea>
                                                        </div>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">退貨日期</span>
                                                            </div>
                                                            <input type="text" class="form-control datepicker" name="return_date" value="{{ $return->return_date ?? date('Y-m-d') }}" >
                                                            <button type="button" id="addSelect" class="btn btn-primary float-right" style="display: none">新增商品</button>
                                                        </div>
                                                    </div>
                                                    <div class="col-9">
                                                        <label>產品列表</label>
                                                        <select id="productSelect" class="form-control" size="12" multiple="multiple">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <span>已選擇產品：<span class="text-danger text-bold">金額請填寫含稅</span></span>
                                                </div>
                                            </div>
                                            <div class="form-group col-12" id="selectedProduct"></div>
                                        </div>
                                        <div class="col-12 text-center">
                                            <button id="submit" type="submit" class="btn btn-primary" style="display:none">送出</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
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

        $('#selectByVendor').change(function(){
            if($(this).val() == ''){
                search('','','');
            }else{
                search('','',$(this).val());
            }
        });

        $('.btn-remove').click(function(){
            $(this).parent().parent().remove();
        });
        sessionStorage.setItem('ids',[]);
        $('#addSelect').click(function(){
            let token = '{{ csrf_token() }}';
            let seletedIds = $( "#productSelect").val();
            let selectdIdsJSON = JSON.stringify(seletedIds);
            let storageIds = sessionStorage.getItem('ids');
            if(storageIds == ''){
                storageIds = [];
            }else{
                storageIds = JSON.parse(storageIds);
            }
            let ids = storageIds.concat(seletedIds);
            let idsJSON = JSON.stringify(ids);
            sessionStorage.setItem('ids',idsJSON);
            $.ajax({
                type: "post",
                url: 'getProducts',
                data: {ids: ids, _token: token },
                success: function(data) {
                    $('#selectedProduct').html('');
                    let html = '';
                    for(let i = 0; i<data.length; i++){
                        html += '<div class="input-group mt-2"><input type="hidden" class="form-control col-10" name="data['+i+'][product_model_id]" value="'+data[i]['id']+'"><div class="input-group-prepend"><span class="input-group-text">品名</span></div><input type="text" class="form-control col-10" value="'+data[i]['name']+'" disabled><input type="hidden" class="form-control col-1" name="data['+i+'][quantity]" value="1"><div class="input-group-prepend"><span class="input-group-text">金額</span></div><input type="number" step=".0001" class="form-control col-2" name="data['+i+'][price]" value="" placeholder="請填寫含稅金額"><div class="input-group-prepend"><button type="button" class="btn btn-danger btn-remove">移除</button></div></div>';
                    }
                    $('#selectedProduct').append(html);
                    $('#submit').show();
                    $('.btn-remove').click(function(){
                        $(this).parent().parent().remove();
                    });
                }
            });
        });
    })(jQuery);

    function search(category,keyword,vendor){
        $('#selectedProduct').html('');
        $('#submit').hide();
        let token = '{{ csrf_token() }}';
        if(vendor != ''){
            $.ajax({
                type: "post",
                url: 'getProducts',
                data: {vendor: vendor, _token: token },
                success: function(data) {
                    var options = '';
                    for(let i=0;i<data.length;i++){
                        options +='<option value="'+data[i]['id']+'">'+data[i]['name']+'</option>';
                    }
                    $('#addSelect').show();
                    $('#productSelect').html(options);
                }
            });
        }else{
            $('#productSelect').html('');
            $('#addSelect').hide();
            $('#selectedProduct').html('');
            $('#submit').hide();
        }
    }

</script>
@endsection
