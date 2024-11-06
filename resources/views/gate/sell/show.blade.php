@extends('gate.layouts.master')

@section('title', '出貨單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>出貨單管理</b><span class="badge badge-success text-sm">{{ $sell->order_number }}</span></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">出貨單管理</a></li>
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
                            <h3 class="card-title mr-1">出貨單資料</h3><span class="text-warning">{{ $sell->purchase_no }}</span>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">鼎新銷貨單號</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $sell->erp_sell_no ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">iCarry訂單號碼</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $sell->order_number ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">鼎新訂單號碼</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $sell->erp_order_number ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">稅別</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $sell->tax_type == 1 ? '應稅內含' : $sell->tax_type == 2 ? '應稅外加' : '零稅率' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">單品數量</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $sell->quantity ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">單價</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $sell->amount ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">稅金</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ $sell->tax ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">總計</span>
                                        </div>
                                        <input type="text" class=" form-control" value="{{ ($sell->amount + $sell->tax) ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">出貨日期</span>
                                        </div>
                                        <input type="text" class=" form-control datepicker" value="{{ $sell->sell_date ?? '' }}" name="sell_date">
                                    </div>
                                </div>
                                <div class="col-1 mb-2">
                                    <button id="modifyDate" type="button" class="btn btn-primary btn-block">修改日期</button>
                                </div>
                                <div class="col-12 "><span class="float-right text-danger">注意! 修改出貨日期，僅需將鼎新出貨單作廢。</span></div>
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                <table class="table table-sm">
                                    <thead class="table-info">
                                        <th width="10%" class="text-left align-middle text-sm">鼎新銷貨編號</th>
                                        <th width="10%" class="text-left align-middle text-sm">鼎新訂單編號</th>
                                        <th width="10%" class="text-left align-middle text-sm">鼎新品號</th>
                                        <th width="10%" class="text-left align-middle text-sm">貨號</th>
                                        <th width="15%" class="text-left align-middle text-sm">商家名稱</th>
                                        <th width="25%" class="text-left align-middle text-sm">品名</th>
                                        <th width="5%" class="text-center align-middle text-sm">單位</th>
                                        <th width="5%" class="text-right align-middle text-sm">數量</th>
                                        <th width="5%" class="text-right align-middle text-sm">單價</th>
                                        <th width="10%" class="text-right align-middle text-sm">總價</th>
                                    </thead>
                                    <tbody>
                                        @foreach($sell->items as $item)
                                        <tr>
                                            <td class="text-left align-middle text-sm">{{ $item->erp_sell_no.'-'.$item->erp_sell_sno }}</td>
                                            <td class="text-left align-middle text-sm">{{ $item->erp_order_no.'-'.$item->erp_order_sno }}</td>
                                            <td class="text-left align-middle text-sm">{{ $item->digiwin_no }}</td>
                                            <td class="text-left align-middle text-sm">{{ $item->sku }}</td>
                                            <td class="text-left align-middle text-sm">{{ $item->vendor_name }}</td>
                                            <td class="text-left align-middle text-sm">{{ $item->product_name ?? $item->memo }}</td>
                                            <td class="text-center align-middle text-sm">{{ $item->unit_name ?? '個' }}</td>
                                            <td class="text-right align-middle text-sm">{{ $item->sell_quantity }}</td>
                                            <td class="text-right align-middle text-sm">{{ number_format(round($item->sell_price)) }}</td>
                                            <td class="text-right align-middle text-sm">{{ number_format($item->sell_quantity * $item->sell_price) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="modifyDateForm" action="{{ url('sell/modifyDate') }}" method="POST">
        <input type="hidden" name="id" value="{{ $sell->id }}">
        @csrf
    </form>
</div>

{{-- 修改入庫 Modal --}}
<div id="stockinModifyModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockinModifyModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="stockinModifyForm" action="{{ url('purchases/stockinModify') }}" method="POST" class="float-right">
                @csrf
                <div class="modal-body">
                    <div id="purchaseQty"></div>
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="15%" class="text-left">鼎新入庫單號</th>
                                <th width="10%" class="text-left">入庫單序號</th>
                                <th width="30%" class="text-left">商品名稱</th>
                                <th width="10%" class="text-right">採購金額</th>
                                <th width="10%" class="text-right">入庫數量</th>
                                <th width="15%" class="text-right">入庫日期</th>
                            </tr>
                        </thead>
                        <tbody id="stockinModifyRecord"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary float-right btn-stockinModify">確定修改</button>
                </div>
            </form>
        </div>
    </div>
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
        var oldDate = '{{ $sell->sell_date }}';
        $('#modifyDate').click(function(){
            let form = $('#modifyDateForm');
            let newDate = $('input[name=sell_date]').val();
            form.append( $('<input type="hidden" name="sell_date" value="'+newDate+'">') );
            if(confirm('修改出貨單日期前，請先確認鼎新出貨單(及廠商直寄進貨單)是否已經作廢???')){
                if(newDate == oldDate){
                    alert('出貨日期並未改變');
                    return;
                }else{
                    form.submit();
                }
            }
        });
    })(jQuery);
</script>
@endsection
