@extends('gate.layouts.master')

@section('title', '票券管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>票券管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orderCancel') }}">票券管理</a></li>
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
                                        @if(in_array($menuCode.'N', explode(',',Auth::user()->power)))
                                        <button id="OpenTicket" class="btn btn-sm btn-primary mr-2">開票</button>
                                        @endif
                                        @if(in_array($menuCode.'REO', explode(',',Auth::user()->power)))
                                        <button id="reOpenTicket" class="btn btn-sm btn-danger mr-2">重開票券</button>
                                        @endif
                                        @if(in_array($menuCode.'IM', explode(',',Auth::user()->power)))
                                        <button id="Import" class="btn btn-sm btn-warning">匯入</button>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($tickets) ? number_format($tickets->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left"></div>
                                    <div class="col-4 float-right">
                                        <div class="float-right d-flex align-items-center">
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
                                            <button class="btn btn-sm btn-info" id="multiProcess" disabled><span>多筆處理</span></button>
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
                                <form id="searchForm" role="form" action="{{ url('tickets') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6 mt-2">
                                                <label for="status">狀態:</label>
                                                <select class="form-control" id="status" name="status" multiple style="height: 96pt">
                                                    <option value="-1"  {{ isset($status) ? in_array(-1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-danger">已作廢</option>
                                                    <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">未銷售</option>
                                                    <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">已售出</option>
                                                    <option value="9"  {{ isset($status) ? in_array(9,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">已核銷</option>
                                                    <option value="2"  {{ isset($status) ? in_array(2,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">已結帳</option>
                                                </select>
                                                <input type="hidden" class="form-control" id="status_hidden" name="status">
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                <div class="col-4 mt-2">
                                                    <label class="control-label" for="purchase_no">票券號碼:</label>
                                                    <input type="text" class="form-control" id="ticket_no" name="ticket_no" placeholder="填寫票券號碼" value="{{ isset($ticket_no) ? $ticket_no ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <label for="order_number">iCarry訂單編號:</label>
                                                    <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單單號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <label for="purchase_no">採購單單編號:</label>
                                                    <input type="number" inputmode="numeric" class="form-control" id="purchase_no" name="purchase_no" placeholder="iCarry採購單單號" value="{{ isset($purchase_no) && $purchase_no ? $purchase_no : '' }}" autocomplete="off" />
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <label class="control-label" for="used_time">使用渠道:</label>
                                                    <div class="input-group">
                                                        <select class="form-control" name="create_type">
                                                            <option value="">選擇渠道</option>
                                                            @foreach($createTypes as $type)
                                                            <option value="{{ $type->create_type }}" {{ isset($create_type) && $create_type == $type->create_type ? 'selected' : '' }}>{{ $type->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <label class="control-label" for="vendor_name">商家:</label>
                                                    <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <label class="control-label" for="digiwin_no">鼎新貨號:</label>
                                                    <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫鼎新品號: 5TWXXXXXXX" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="create_time">開票日期:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="create_time" name="create_time" placeholder="格式：2016-06-06" value="{{ isset($used_time) ? $used_time ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="create_time_end" name="create_time_end" placeholder="格式：2016-06-06" value="{{ isset($used_time_end) ? $used_time_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="used_time">核銷日期:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datetimepicker" id="used_time" name="used_time" placeholder="格式：2016-06-06" value="{{ isset($used_time) ? $used_time ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datetimepicker" id="used_time_end" name="used_time_end" placeholder="格式：2016-06-06" value="{{ isset($used_time_end) ? $used_time_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="digiwin_no">商品品名:</label>
                                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品品名:  胡同燒肉夜食" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
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
                            @if(count($tickets) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="10%">開票日期<br>票券號碼</th>
                                        <th class="text-left text-sm" width="10%">貨號</th>
                                        <th class="text-left text-sm" width="25%">票券商品名稱</th>
                                        <th class="text-left text-sm" width="10%">使用渠道</th>
                                        <th class="text-left text-sm" width="10%">訂單號碼</th>
                                        <th class="text-left text-sm" width="10%">核銷日期</th>
                                        <th class="text-left text-sm" width="10%">採購單號</th>
                                        <th class="text-left text-sm" width="5%">狀態</th>
                                        <th class="text-center text-sm" width="5%">處理</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tickets as $ticket)
                                    <tr>
                                        <td class="text-left text-sm align-middle">
                                            {{ explode(' ',$ticket->created_at)[0] }}
                                            <br>
                                            <input type="checkbox" class="chk_box_{{ $ticket->id }} mr-2" name="chk_box" value="{{ $ticket->id }}">
                                            <a href="javascript:" class="mr-2" onclick="getInfo({{ $ticket->id }})" title="點我，輸入密碼，看完整號碼">{{ $ticket->ticket_no }}</a>
                                            @if(!empty($ticket->order) && $ticket->order['create_type'] == 'web' && $ticket->status == 1)
                                            <a href="javascript:" onclick="resend({{ $ticket->id }})" title="重新發送票號給購買者"><i class="fas fa-envelope"></i></a>
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">{{ $ticket->digiwin_no }}</td>
                                        <td class="text-left text-sm align-middle">{{ $ticket->product_name }}</td>
                                        <td class="text-left text-sm align-middle">{{ $ticket->create_type == 'web' ? 'iCarry與其他' : $ticket->create_type }}</td>
                                        <td class="text-left text-sm align-middle">
                                            @if(!empty($ticket->order_id))
                                            <a href="{{ route('gate.orders.show', $ticket->order_id) }}" taget="_blank">{{ $ticket->order_number }}</a>
                                            @else
                                            {{ $ticket->order_number }}
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">{{ $ticket->used_time }}</td>
                                        <td class="text-left text-sm align-middle">
                                            @if(!empty($ticket->purchase_no))
                                            <a href="{{ route('gate.purchases.show', $ticket->purchase->id) }}" taget="_blank"><span class="text-sm">{{ $ticket->purchase_no }}</span></a>
                                            @elseif(!empty($ticket->order) && $ticket->order['create_type'] != 'web')
                                            <span class="text-sm">(外渠訂單)</span>
                                            @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            @if($ticket->status == -1)
                                            <span class="text-danger">已作廢</span>
                                            @elseif($ticket->status == 0)
                                            <span class="text-secondary">未售出</span>
                                            @elseif($ticket->status == 1)
                                            <span class="text-info">已售出</span>
                                            @elseif($ticket->status == 2)
                                            <span class="text-success">已結帳</span>
                                            @elseif($ticket->status == 9)
                                            <span class="text-primary">已核銷</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                            @if($ticket->status != 9 && $ticket->status != 2 && $ticket->status != -1)
                                                <form action="{{ route('gate.tickets.destroy', $ticket->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger btn-cancel">作廢</button>
                                                </form>
                                            @elseif($ticket->status == 9)
                                            <form action="{{ route('gate.tickets.settle', $ticket->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $ticket->id }}">
                                                <button type="button" class="btn btn-sm btn-success btn-settle">結帳</button>
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($tickets) ? number_format($tickets->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $tickets->appends($appends)->render() }}
                                @else
                                {{ $tickets->render() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form id="multiProcessForm" action="{{ url('tickets/multiProcess') }}" method="POST">
            @csrf
        </form>
    </section>
</div>
@endsection
@section('modal')
{{-- 開票 Modal --}}
<div id="OpenTicketModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="OpenTicketModalLabel">請選擇要開票的渠道、商品及數量</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-append col-3">
                                <select class="form-control" id="createType">
                                    <option value="">選擇渠道</option>
                                    @foreach($createTypes as $type)
                                    <option value="{{ $type->create_type }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-group-append col-6">
                                <select class="form-control" id="digiwinNo">
                                    <option value="">選擇票券</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->digiwin_no }}">{{ $product->product_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-group-append col-2">
                                <input class="form-control" type="number" id="Quantity" placeholder="輸入票券數量">
                            </div>
                            <div class="input-group-append col-1">
                                <button id="newBtn" type="button" class="btn btn-md btn-primary btn-block">新增</button>
                            </div>
                        </div>
                    </div>
                    <form  id="OpenTicketForm" action="{{ url('tickets/open') }}" method="POST"  style="display: none">
                        @csrf
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <td width="20%" class="text-left text-sm">渠道</td>
                                <td width="20%" class="text-left text-sm">票券品號</td>
                                <td width="40%" class="text-left text-sm">票券名稱</td>
                                <td width="10%" class="text-left text-sm">數量</td>
                                <td width="10%" class="text-left text-sm">取消</td>
                            </tr>
                        </thead>
                        <tbody id="openTicketData"></tbody>
                    </table>
                    <button id="OpenTicketBtn" type="button" class="btn btn-md btn-primary">開票</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- 重新開票 Modal --}}
<div id="reOpenTicketModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reOpenTicketModalLabel">請輸入要重新開票的訂單號碼</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="reOpenTicketForm" action="{{ url('openTicket') }}" method="POST">
                    @csrf
                    <input type="hidden" name="chk" value="$2y$10$6AE.uCYFYJP0EFbx41zeSufTvEwpD8vtIRZj116rQVJG0YNyTVZf6">
                    <input type="hidden" name="site" value="gate">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-append col-6">
                            <input class="form-control" type="number" id="orderNumber" name="orderNumber" placeholder="請輸入要重開票券的訂單號碼" required>
                        </div>
                        <div class="input-group-append col-1">
                            <button type="submit" class="btn btn-md btn-primary btn-block">開票</button>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
{{-- 匯入 Modal --}}
<div id="importModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">請選擇匯入檔案</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="importForm" action="{{ url('tickets/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="cate" value="tickets">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" id="filename" name="filename" class="custom-file-input" required autocomplete="off">
                                <label class="custom-file-label" for="filename">瀏覽選擇EXCEL檔案</label>
                            </div>
                            <div class="input-group-append">
                                <button id="importBtn" type="button" class="btn btn-md btn-primary btn-block">上傳</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div>
                    <span class="text-danger">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考<a href="{{ 'https://'.env('GATE_DOMAIN').'/sample/外渠票券訂單資料匯入範例.xlsx' }}" class="mb-3" target="_blank">外渠票券訂單資料匯入範例</a>，製作正確的檔案。</span>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- 多處理Modal --}}
<div id="multiModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="multiModalLabel">請選擇功能</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div>
                    @if(in_array($menuCode.'EX', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Export_Ticket">匯出票券</button>
                    @endif
                    @if(in_array($menuCode.'ST', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Settle">票券結帳</button>
                    @endif
                </div>
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

        // date time picker 設定
        $('.datetimepicker').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('#OpenTicket').click(function(){
            $('#OpenTicketModal').modal('show');
        });

        $('#reOpenTicket').click(function(){
            $('#reOpenTicketModal').modal('show');
        });

        $('#Import').click(function(){
            $('#importModal').modal('show');
        });

        $('#importBtn').click(function(){
            let form = $('#importForm');
            $('#importBtn').attr('disabled',true);
            form.submit();
        });

        $('#OpenTicketBtn').click(function(){
            let form = $('#OpenTicketForm');
            $('#OpenTicketBtn').attr('disabled',true);
            form.submit();
        });

        $('.delete-btn').click(function (e) {
            $(this).parents('tr').remove();
            trCount = $('#openTicketData tr').length;
                if(trCount == 0){
                    $('#OpenTicketForm').hide();
                }
        });

        $('#newBtn').click(function(){
            let html = $('#openTicketData').html();
            let createType = $('#createType').val();
            let createTypeName = $('#createType :selected').text();
            let digiwinNo = $('#digiwinNo').val();
            let digiwinNoName = $('#digiwinNo :selected').text();
            let quantity = $('#Quantity').val();
            if(createType == ''){
                alert('請選擇渠道');
                return;
            }
            if(digiwinNo == ''){
                alert('請選擇票券商品');
                return;
            }
            if(quantity == ''){
                alert('請填寫數量');
                return;
            }
            $('#OpenTicketForm').show();
            let rowCount = $('#openTicketData tr').length;
            html = html+'<tr><td><input type="hidden" name="open['+rowCount+'][create_type]" value="'+createType+'">'+createTypeName+'</td><td><input type="hidden" name="open['+rowCount+'][digiwin_no]" value="'+digiwinNo+'">'+digiwinNo+'</td><td>'+digiwinNoName+'</td><td><input type="hidden" name="open['+rowCount+'][quantity]" value="'+quantity+'">'+quantity+'</td><td><button type="button" class="btn btn-sm btn-danger delete-btn"><i class="far fa-trash-alt"></i></button></td></tr>';
            $('#openTicketData').html(html);
            $('.delete-btn').click(function (e) {
                $(this).parents('tr').remove();
                let trCount = $('#openTicketData tr').length;
                if(trCount == 0){
                    $('#OpenTicketForm').hide();
                }
            });
        });

        $('.btn-cancel').click(function (e) {
            if(confirm('請確認是否要作廢這筆資料?')){
                $(this).parents('form').submit();
            };
        });

        $('.btn-settle').click(function (e) {
            if(confirm('請確認是否要結帳這筆資料? \r\n (系統將會同時處理相同訂單做銷貨動作。)')){
                $(this).parents('form').submit();
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
                $('#multiProcess').prop("disabled",true);
            }else if(num > 0){
                $("#selectorder").prop("checked",true)
                $('#multiProcess').prop("disabled",false);
            }else if(num == num_all){
                $("#chkallbox").prop("checked",true);
                $('#multiProcess').prop("disabled",false);
            }
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('input[name="multiProcess"]').click(function(){
            if($(this).val() == 'allOnPage'){
                $('input[name="chk_box"]').prop("checked",true);
                $('#multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",false);
            }else if($(this).val() == 'selected'){
                $('input[name="chk_box"]').prop("checked",false);
                $('#multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",false);
            }else if($(this).val() == 'byQuery'){
                $('input[name="chk_box"]').prop("checked",false);
                $('#multiProcess').prop("disabled",false);
                $('#oit').prop("disabled",true);
            }else{
                $('#multiProcess').prop("disabled",true);
                $('#oit').prop("disabled",false);
            }
            $('#orderSearchForm').hide();
            $('#showForm').html('使用欄位查詢');
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('#multiProcess').click(function(){
            if($('input[name="multiProcess"]:checked').val() == 'selected'){
                let num = $('input[name="chk_box"]:checked').length;
                if(num == 0){
                    alert('尚未選擇訂單');
                    return;
                }
            }
            $('#multiModal').modal('show');
        });

        $('.multiProcess').click(function (e){
            let form = $('#multiProcessForm');
            let cate = $(this).val().split('_')[0];
            let type = $(this).val().split('_')[1];
            let filename = $(this).html();
            let condition = null;
            let multiProcess = $('input[name="multiProcess"]:checked').val();
            let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
            if(multiProcess == 'allOnPage' || multiProcess == 'selected'){
                if(ids.length > 0){
                    for(let i=0;i<ids.length;i++){
                        form.append($('<input type="hidden" class="formappend" name="id['+i+']">').val(ids[i]));
                    }
                }else{
                    alert('尚未選擇票券資料');
                    return;
                }
            }else if(multiProcess == 'byQuery'){ //by條件
                let sel = "";
                $("#status>option:selected").each(function(){
                    sel+=","+$(this).val();
                });
                $("#status_hidden").val(sel.substring(1));
                condition = $('#searchForm').serializeArray();
                let con_val = $('#searchForm').serializeArray().map( item => item.value );
                let con_name = $('#searchForm').serializeArray().map( item => item.name );
                for(let j=0; j<con_name.length;j++){
                    let tmp = '';
                    tmp = $('<input type="hidden" class="formappend" name="con['+con_name[j]+']" value="'+con_val[j]+'">');
                    form.append(tmp);
                }
            }else{
                return;
            }
            let export_method = $('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">');
            let export_cate = $('<input type="hidden" class="formappend" name="cate" value="'+cate+'">');
            let export_type = $('<input type="hidden" class="formappend" name="type" value="'+type+'">');
            form.append(export_method);
            form.append(export_cate);
            form.append(export_type);
            form.append( $('<input type="hidden" class="formappend" name="filename" value="'+filename+'">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="tickets">') );
            form.submit();
            $('.formappend').remove();
            $('#multiModal').modal('hide');
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

    function getInfo(id){
        let token = '{{ csrf_token() }}';
        let pwd = prompt("請輸入密碼，輸入錯誤超過三次帳號將會被鎖住。");
        if(pwd != null){
            $.ajax({
                type: "post",
                url: 'tickets/getInfo',
                data: {pwd:pwd, id: id, _token:token },
                success: function(data) {
                    let message = data['message'];
                    let ticketNo = data['ticket_no'];
                    let count = data['count'];
                    if(count >= 3){ //滾
                        alert(message);
                        location.href = 'logout';
                    }else if(message != null){
                        alert(message);
                    }else{
                        alert('票券號碼： '+ticketNo);
                    }
                }
            })
        }
    }

    function resend(id){
        let token = '{{ csrf_token() }}';
        if(confirm("請確認是否要發送票號給購買者?")){
            $.ajax({
                type: "post",
                url: 'tickets/resend',
                data: {id: id, _token:token },
                success: function(data) {
                    if(data == '成功'){
                        alert("補寄成功");
                    }else{
                        alert(data);
                    }
                }
            })
        }
    }

</script>
@endsection
