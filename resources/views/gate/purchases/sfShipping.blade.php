@extends('gate.layouts.master')

@section('title', '順豐運單管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>順豐運單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('sfShippings') }}">順豐運單管理</a></li>
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
                                <div class="col-2">
                                    <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                </div>
                                <div class="col-4 float-right">
                                    {{-- <div class="float-right d-flex align-items-center">
                                            <div class="icheck-primary d-inline mr-2">
                                                <input type="radio" id="selectorder" name="multiProcess" value="selected">
                                                <label for="selectorder">自行勾選 <span id="chkallbox_text"></span></label>
                                            </div>
                                            <div class="icheck-primary d-inline mr-2">
                                                <input type="radio" id="chkallbox" name="multiProcess" value="allOnPage">
                                                <label for="chkallbox">目前頁面全選</label>
                                            </div>
                                            <div class="icheck-primary d-inline mr-2">
                                                <input type="radio" id="queryorder" name="multiProcess" value="byQuery">
                                                <label for="queryorder">依查詢條件</label>
                                            </div>
                                        <button class="btn btn-sm btn-info" id="multiProcess" value="shippingExport" disabled>出貨單多筆匯出</button>
                                    </div> --}}
                                </div>
                                <div class="col-6">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($shippings) ? number_format($shippings->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left">
                                    </div>
                                    <div class="col-4 float-right">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="orderSearchForm" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('sfShippings') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6 mt-2">
                                                <label for="status">運單狀態:</label>
                                                <select class="form-control" id="status" size="5" multiple>
                                                    <option value="-1"  {{ isset($status) ? in_array(-1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">已取消</option>
                                                    <option value="9"  {{ isset($status) ? in_array(9,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-danger">待出貨</option>
                                                    <option value="6"  {{ isset($status) ? in_array(6,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">運送中</option>
                                                    <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">已送達</option>
                                                    <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">已結案</option>
                                                </select><input type="hidden" value="-1,0,1,6,9" name="status" id="status_hidden" />
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-6 mt-2">
                                                        <label for="vendor_arrival_date">廠商應到貨日區間:</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control datepicker" id="vendor_arrival_date" name="vendor_arrival_date" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date) ? $vendor_arrival_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="text" class="form-control datepicker" id="vendor_arrival_date_end" name="vendor_arrival_date_end" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date_end) ? $vendor_arrival_date_end ?? '' : '' }}" autocomplete="off" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="shipping_date">預定出貨日區間:</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control datepicker" id="shipping_date" name="shipping_date" placeholder="格式：2016-06-06" value="{{ isset($shipping_date) ? $shipping_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="text" class="form-control datepicker" id="shipping_date_end" name="shipping_date_end" placeholder="格式：2016-06-06" value="{{ isset($shipping_date_end) ? $shipping_date_end ?? '' : '' }}" autocomplete="off" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label for="vendor_arrival_date">入庫日區間:</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control datepicker" id="stockin_date" name="stockin_date" placeholder="格式：2016-06-06" value="{{ isset($stockin_date) ? $stockin_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="text" class="form-control datepicker" id="stockin_date_end" name="stockin_date_end" placeholder="格式：2016-06-06" value="{{ isset($stockin_date_end) ? $stockin_date_end ?? '' : '' }}" autocomplete="off" />
                                                        </div>
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
                                           <div class="col-3 mt-2">
                                                <label for="sf_express_no">順豐運單單號:</label>
                                                <input type="text" class="form-control" id="sf_express_no" name="sf_express_no" placeholder="順豐運單單號" value="{{ isset($sf_express_no) && $sf_express_no ? $sf_express_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="vendor_shipping_no">商家出貨單號</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="vendor_shipping_no" name="vendor_shipping_no" placeholder="商家出貨單號" value="{{ isset($vendor_shipping_no) && $vendor_shipping_no ? $vendor_shipping_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="vendor_name">商家名稱</label>
                                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="商家名稱 ex: 佳德" value="{{ isset($vendor_name) && $vendor_name ? $vendor_name : '' }}" autocomplete="off" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                        <button type="button" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                    </div>
                                </form>
                            </div>
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left" width="10%">順豐物流單號</th>
                                        <th class="text-left" width="15%">商家名稱</th>
                                        <th class="text-left" width="10%">商家出貨單號</th>
                                        <th class="text-left" width="5%">序號</th>
                                        <th class="text-left" width="10%">廠商應到貨日</th>
                                        <th class="text-left" width="10%">預定出貨日</th>
                                        <th class="text-left" width="10%">入庫日</th>
                                        <th class="text-left" width="15%">包裹位置(非即時)</th>
                                        <th class="text-center" width="5%">狀態</th>
                                        <th class="text-center" width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shippings as $shipping)
                                    <tr>
                                        <td class="text-left align-middle text-sm">{{ $shipping->sf_express_no }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->vendor_name }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->vendor_shipping_no }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->sno }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->vendor_arrival_date }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->shipping_date }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->stockin_date }}</td>
                                        <td class="text-left align-middle text-sm">{{ $shipping->trace_address }}</td>
                                        <td class="text-center align-middle">
                                            @if($shipping->status == -1)
                                            <span class="text-secondary text-bold">已取消</span>
                                            @elseif($shipping->status == 9)
                                            <span class="text-danger text-bold">待出貨</span>
                                            @elseif($shipping->status == 6)
                                            <span class="text-primary text-bold">運送中</span>
                                            @elseif($shipping->status == 1)
                                            <span class="text-success text-bold">已送達</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="row">
                                                {{-- <a href="{{ $shipping->label_url }}" target="_blank" title="下載列印物流單" class="btn btn-sm btn-primary mr-2"><i class="fas fa-print"></i></a> --}}
                                                @if($shipping->status == 6 || $shipping->status == 1)
                                                <button type="button" class="btn btn-sm btn-success mr-2 btn-check" value="{{ $shipping->id }}"><i class="fas fa-truck"></i></button>
                                                @endif
                                                @if($shipping->status == 9)
                                                @if(in_array($menuCode.'CO',explode(',',Auth::user()->power)))
                                                <form action="{{ route('gate.sfShippings.destroy', $shipping->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                        <i class="far fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                            </div>
                            <div class="float-right">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('modal')
{{-- 運送紀錄 Modal --}}
<div id="shippingModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shippingModalLabel">順豐物流單狀態查詢：</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="shippingDetail" class="row"></div><br>
                <table id="shippingTable" class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="10%" class="text-left">代碼</th>
                            <th width="15%" class="text-left">時間</th>
                            <th width="30%" class="text-left">包裹位置</th>
                            <th width="40%" class="text-left">說明</th>
                        </tr>
                    </thead>
                    <tbody id="shippingRecord"></tbody>
                </table>
            </div>
        </div>
    </div>
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

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要取消這筆運單資料?')){
                $(this).parents('form').submit();
            };
        });

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#orderSearchForm').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });

        $('.datepicker').datepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('.btn-check').click(function(){
            $('#shippingRecord').html('');
            $('#shippingDetail').html('');
            let id = $(this).val();
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'sfShippings/getStatus',
                data: {id: id, _token: token },
                success: function(data) {
                    // console.log(data);
                    let shippingDetail = '';
                    let traceItems = data['traceItems'];
                    let sfShippingNumber = data['sf_express_no'];
                    let vendor_name = data['vendor_name'];
                    let vendor_arrival_date = data['vendor_arrival_date'];
                    let shipping_date = data['shipping_date'];
                    let trace_address = data['trace_address'];
                    shippingDetail = '<div class="col-6">順豐運單號：</div><div class="col-6">商家名稱：'+vendor_name+'</div><div class="col-6">預定到貨日：'+vendor_arrival_date+'</div><div class="col-6">包裹出貨日：'+shipping_date+'</div><div class="col-6">包裹位置：'+trace_address+'</div>';
                    $('#shippingDetail').html(shippingDetail);
                    if(traceItems.length > 0){
                        let record = '';
                        for(let i=0; i<traceItems.length; i++){
                            let localTm = traceItems[i]['localTm'];
                            let opCode = traceItems[i]['opCode'];
                            let trackAddr = traceItems[i]['trackAddr'];
                            let trackOutRemark = traceItems[i]['trackOutRemark'];
                            record += '<tr><td width="5%" class="text-center">'+(i+1)+'</td><td width="10%" class="text-left">'+opCode+'</td><td width="15%" class="text-left">'+localTm+'</td><td width="30%" class="text-left">'+trackAddr+'</td><td width="40%" class="text-left">'+trackOutRemark+'</td></tr>';
                        }
                        $('#shippingRecord').html(record);
                        $('#shippingTable').remove('.d-none');
                    }else{
                        $('#shippingTable').addClass('d-none');
                    }
                }
            });
            $('#shippingModal').modal('show');
        });
    })(jQuery);

    function formSearch(){
        let sel="";
        $("#status>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#status_hidden").val(sel.substring(1));
        $("#searchForm").submit();
    }
</script>
@endsection
