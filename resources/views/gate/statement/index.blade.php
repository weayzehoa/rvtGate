@extends('gate.layouts.master')

@section('title', '對帳單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>對帳單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('statements') }}">對帳單管理</a></li>
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
                                <div class="col-5">
                                    <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                    <button id="createForm" class="btn btn-sm btn-primary mr-2" title="建立對帳單">建立對帳單</button>
                                </div>
                                <div class="col-7">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($statements) ? number_format($statements->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left">
                                        <span clas="d-flex align-items-center">查詢條件：</span>
                                        @if(!empty($start_date) || !empty($end_date))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('start_date')">X </span>
                                            對帳日期區間：
                                            @if(!empty($start_date)){{ $start_date.' ' }}@else{{ '開站' }}@endif
                                            @if(!empty($end_date)){{ '至 '.$end_date.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($notice))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('notice')">X</span> 通知廠商：{{ $notice == 'Y' ? '已通知' : '未通知' }}</span>@endif
                                        @if(!empty($return_discount_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('return_discount_no')">X</span> 退貨折抵單號：{{ $return_discount_no }}</span>@endif
                                        @if(!empty($erp_purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_purchase_no')">X</span> 鼎新採購單號：{{ $erp_purchase_no }}</span>@endif
                                        @if(!empty($purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('purchase_no')">X</span> icarry採購單號：{{ $purchase_no }}</span>@endif
                                        @if(!empty($statement_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('statement_no')">X</span> 對帳單號：{{ $statement_no }}</span>@endif
                                        @if(!empty($VAT_number))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('VAT_number')">X</span> 廠商統編：{{ $VAT_number }}</span>@endif
                                        @if(!empty($vendor_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_name')">X</span> 商家名稱：{{ $vendor_name }}</span>@endif
                                        @if(!empty($payment_com))
                                        @for($i=0;$i<count($payments);$i++)
                                        @if($payments[$i]['vendorIds'] == $payment_com)
                                            <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('payment_com')">X</span> 付款條件：{{ $payments[$i]['name'] }}</span>
                                        @endif
                                        @endfor
                                        @endif
                                        @if(!empty($invoice_date_not_fill))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_date_not_fill')">X </span>預定出貨日區間：未預定</span>
                                        @else
                                            @if((!empty($invoice_date) || !empty($invoice_date_end)))
                                            <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('invoice_date')">X </span>
                                                發票收受日區間：
                                                @if(!empty($invoice_date)){{ $invoice_date.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                @if(!empty($invoice_date_end)){{ '至 '.$invoice_date.' ' }}@else{{ '至 現在' }}@endif
                                            </span>
                                            @endif
                                        @endif
                                        @if($list)<span class="badge badge-info mr-1">每頁：{{ $list }} 筆</span>@endif
                                    </div>
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
                                <div class="col-12 mt-2" id="showExpressTable" style="display: none">
                                    <div id="expressData" class="row"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="statementForm" action="{{ route('gate.statements.store') }}" method="POST">
                                @csrf
                                <div id="statementCreateForm" class="card card-primary" style="display: none">
                                    <div class="card-header">
                                        <h3 class="card-title">建立對帳單</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row offset-1">
                                            <div class="col-5">
                                                <label>選擇廠商 <b>(按住 ctrl + 滑鼠 多選)</b></label>
                                                <select id="vendorSelect" class="form-control" name="vendorIds[]" size="10" multiple>
                                                    <option value="">不拘</option>
                                                    @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->is_on == 0 ? $vendor->name.' [已停用]' : $vendor->name }}</option>
                                                    @endforeach
                                                    </select>
                                            </div>
                                            <div class="col-5">
                                                <div class="col-12">
                                                    <label>選擇付款條件</label>
                                                    <select id="paymentCon" class="form-control" name="paymentCon[]" size="6" multiple>
                                                        <option value="" {{ isset($payment_com) ? 'selected' : '' }}>不拘</option>
                                                        @for($i=0;$i<count($payments);$i++)
                                                            <option value="{{ $payments[$i]['vendorIds'] }}">{{ $payments[$i]['name'] }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <label class="control-label" for="start_date">對帳日期區間:</label>
                                                    <div class="input-group">
                                                        <input type="date" class="form-control datepicker" id="startDate" name="start_date" placeholder="格式：2021-01-01" value="{{ isset($start_date) ? $start_date ?? '' : '' }}" autocomplete="off" />
                                                        <span class="input-group-addon bg-primary">~</span>
                                                        <input type="date" class="form-control datepicker" id="endDate" name="end_date" placeholder="格式：2021-12-31" value="{{ isset($end_date) ? $end_date ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <label>說明</label>
                                                    <span class="mt-2 text-primary text-bold">若有選擇廠商則以廠商為優先，付款條件將會被取消</span>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <button type="button" class="btn btn-primary btn-send">送出建立</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div id="orderSearchForm" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('statements') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-3 mt-2">
                                                <label for="statement_no">對帳單編號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="statement_no" name="statement_no" placeholder="對帳單編號" value="{{ isset($statement_no) && $statement_no ? $statement_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="start_time">對帳日期區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="start_date" name="start_date" placeholder="格式：2016-06-06" value="{{ isset($start_date) ? $start_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="end_date" name="end_date" placeholder="格式：2016-06-06" value="{{ isset($end_date) ? $end_date ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label>選擇付款條件</label>
                                                <select id="payment_com" class="form-control" name="payment_com">
                                                    <option value="" {{ isset($payment_com) ? 'selected' : '' }}>不拘</option>
                                                    @for($i=0;$i<count($payments);$i++)
                                                        <option value="{{ $payments[$i]['vendorIds'] }}" {{ isset($payment_com) && $payment_com == $payments[$i]['vendorIds'] ? 'selected' : '' }}>{{ $payments[$i]['name'] }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="vendor_name">商家名稱:</label>
                                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱:海邊走走" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">鼎新採購單編號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="erp_purchase_no" name="erp_purchase_no" placeholder="鼎新採購單單號" value="{{ isset($erp_purchase_no) && $erp_purchase_no ? $erp_purchase_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">iCarry採購單號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="purchase_no" name="purchase_no" placeholder="iCarry採購單編號" value="{{ isset($purchase_no) && $purchase_no ? $purchase_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="invoice_date">發票收受日日期區間:(有輸入日期則無法勾選未預定)</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="invoice_date" name="invoice_date" placeholder="格式：2016-06-06" value="{{ isset($invoice_date) ? $invoice_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="invoice_date_end" name="invoice_date_end" placeholder="格式：2016-06-06" value="{{ isset($invoice_date_end) ? $invoice_date_end ?? '' : '' }}" autocomplete="off" />
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">未預定</span>
                                                    </div>
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <input type="checkbox" id="invoice_date_not_fill" name="invoice_date_not_fill" value="1" {{ isset($invoice_date_not_fill) ? $invoice_date_not_fill == 1 ? 'checked' : '' : '' }}>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">退貨折抵單號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="return_discount_no" name="return_discount_no" placeholder="退貨折抵單編號" value="{{ isset($return_discount_no) && $return_discount_no ? $return_discount_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">廠商統編:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="VAT_number" name="VAT_number" placeholder="廠商統編" value="{{ isset($VAT_number) && $VAT_number ? $VAT_number : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="list">通知廠商:</label>
                                                <select class="form-control" id="notice" name="notice">
                                                    <option value="" {{ !isset($notice) ? 'selected' : '' }}>不拘</option>
                                                    <option value="Y" {{ isset($notice) && $notice == 'Y' ? 'selected' : '' }}>已通知</option>
                                                    <option value="N" {{ isset($notice) && $notice == 'N' ? 'selected' : '' }}>未通知</option>
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
                                        <button type="submit" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" id="reset" value="清空">
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                            @if(!empty($statements))
                            <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="20%">對帳單資訊</th>
                                            <th class="text-left" width="80%">對帳日期/採購/退貨品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($statements as $statement)
                                        <tr>
                                            <td>
                                                <div>
                                                    <input type="checkbox" class="chk_box_{{ $statement->id }}" name="chk_box" value="{{ $statement->id }}">
                                                    <span class="text-lg text-bold order_number_{{ $statement->id }}">{{ $statement->statement_no }}</span>
                                                    @if(in_array($menuCode.'CO',explode(',',Auth::user()->power)))
                                                    <button type="button" value="{{ $statement->id }}" class="badge btn-sm btn btn-danger btn-cancel">取消對帳單</button>
                                                    @endif
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <span class="text-sm">商家：{{ $statement->vendor_name }}</span><br>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-sm">統編：{{ $statement->VAT_number }}</span><br>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-sm">採購金額：{{ $statement->stockin_price }}</span>　　<span class="text-sm">退貨金額：</span><span class="text-danger text-sm">-{{ $statement->return_price }}</span><br>
                                                    <span class="text-sm">折抵金額：</span><span class="text-danger text-sm">-{{ $statement->discount_price }}</span>　　<span class="text-sm">總計金額：{{ $statement->stockin_price - $statement->return_price -$statement->discount_price }}</span>
                                                </div>
                                                <form action="{{ route('gate.statements.update', $statement->id) }}" method="POST">
                                                    <input type="hidden" name="_method" value="PATCH">
                                                    @csrf
                                                <div class="input-group">
                                                    <span class="input-group-prepend text-sm">發票收受日：</span>
                                                    <input type="text" class="datepicker form-control form-control-sm" name="invoice_date" placeholder="請輸入發票收受日" value="{{ $statement->invoice_date }}">
                                                    <div class="input-group-prepend">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            @if(!empty($statement->invoice_date))
                                                            修改
                                                            @else
                                                            儲存
                                                            @endif
                                                        </button>
                                                    </div>
                                                </div>
                                                </form>
                                            </td>
                                            <td>
                                                <table class="table table-hover table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-left text-sm" width="10%">對帳起始日</th>
                                                            <th class="text-left text-sm" width="10%">對帳結束日</th>
                                                            <th class="text-left text-sm" width="25%">進貨商品採購單號</th>
                                                            <th class="text-left text-sm" width="25%">退貨折抵單號/訂單號</th>
                                                            <th class="text-center text-sm" width="10%">通知時間</th>
                                                            <th class="text-center text-sm" width="5%">檔案下載</th>
                                                            <th class="text-center text-sm" width="5%">通知廠商</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-left text-sm align-middle">{{ $statement->start_date }}</td>
                                                            <td class="text-left text-sm align-middle">{{ $statement->end_date }}</td>
                                                            <td class="text-left text-sm align-middle">
                                                                @if(count($statement->purchaseOrders) > 0)
                                                                @foreach($statement->purchaseOrders as $purchaseOrder)
                                                                    <a href="{{ route('gate.purchases.show',$purchaseOrder->id) }}" target="_blank">{{ $purchaseOrder->purchase_no }}</a>　
                                                                @endforeach
                                                                @if(count($statement->purchaseOrders) == 10)
                                                                ......etc.
                                                                @endif
                                                                @endif
                                                            </td>
                                                            <td class="text-left text-sm align-middle">
                                                                @if($statement->returnDiscounts)
                                                                @foreach($statement->returnDiscounts as $returnDiscount)
                                                                    <a href="{{ route('gate.returnDiscounts.show',$returnDiscount->id) }}" target="_blank">{{ $returnDiscount->return_discount_no }}</a>　
                                                                @endforeach
                                                                @endif
                                                                @if($statement->returnOrders)
                                                                <span class="text-sm">訂單號碼</span><br>
                                                                @foreach($statement->returnOrders as $returnOrder)
                                                                    <a href="{{ route('gate.orders.show',$returnOrder->id) }}" target="_blank">{{ $returnOrder->order_number }}</a>　
                                                                @endforeach
                                                                @endif
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                {{ $statement->notice_time }}
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                @if(in_array($menuCode.'DL', explode(',',Auth::user()->power)))
                                                                {{-- <a href="{{ asset('exports/statements/'.$statement->filename) }}" class="btn btn-sm btn-success" title="{{ $statement->filename }}" target="_blank"><i class="fas fa-file-excel"></i></a> --}}
                                                                <a href="{{ env('AWS_FILE_URL').'/upload/statement/'.$statement->filename }}" class="btn btn-sm btn-success" title="{{ $statement->filename }}" target="_blank"><i class="fas fa-file-excel"></i></a>
                                                                @endif
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                @if(in_array($menuCode.'SEM', explode(',',Auth::user()->power)))
                                                                <form action="{{ url('statements/multiProcess') }}" method="POST">
                                                                    @csrf
                                                                    <input type="hidden" name="id[]" value="{{ $statement->id }}">
                                                                    <input type="hidden" name="method" value="selected">
                                                                    <input type="hidden" name="cate" value="Statement">
                                                                    <input type="hidden" name="type" value="Email">
                                                                    <input type="hidden" name="filename" value="通知廠商">
                                                                    <input type="hidden" name="model" value="statement">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-mail-bulk"></i></button>
                                                                </form>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <h3>無資料</h3>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($statements) ? number_format($statements->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ !empty($statements) ? $statements->appends($appends)->render() : null }}
                                @else
                                {{ !empty($statements) ? $statements->render() : null }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="multiProcessForm" action="{{ url('statements/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="cancelForm" action="{{ url('statements/cancel') }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('modal')
{{-- 同步紀錄 Modal --}}
<div id="syncModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ url('statements/notice') }}" method="POST" class="float-right">
                    @csrf
                    <input type="hidden" id="statementId" name="id" value="">
                    <button type="submit" class="btn btn-sm btn-primary">通知廠商</button>
                </form>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="10%" class="text-center">#</th>
                            <th width="15%" class="text-left">同步時間</th>
                            <th width="10%" class="text-left">單品數量</th>
                            <th width="10%" class="text-right">金額</th>
                            <th width="10%" class="text-right">稅金</th>
                            <th width="10%" class="text-right">總金額</th>
                            <th width="15%" class="text-center">通知時間</th>
                        </tr>
                    </thead>
                    <tbody id="syncRecord"></tbody>
                </table>
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
                    @if(in_array($menuCode.'SEM', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-danger multiProcess mr-2" value="Statement_Email">通知廠商</button>
                    @endif
                    @if(in_array($menuCode.'DL', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="Statement_Download">對帳單下載</button>
                    @endif
                    {{-- <button class="btn btn-sm btn-danger multiProcess mr-2" value="CancelStatement">取消對帳單</button> --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 匯入Modal --}}
<div id="importModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">請選擇匯入格式</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="importForm" action="{{ url('purchases/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="cate" value="stockin">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" id="filename" name="filename" class="custom-file-input" required autocomplete="off">
                                <label class="custom-file-label" for="filename">瀏覽選擇EXCEL檔案</label>
                            </div>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-md btn-primary btn-block">上傳</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div>
                    <span class="text-danger">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/入庫報表範本.xls" target="_blank">入庫報表範本</a> ，製作正確的檔案。</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 修改紀錄 Modal --}}
<div id="modifyModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="10%" class="text-left">修改時間</th>
                            <th width="15%" class="text-left">iCarry品號</th>
                            <th width="10%" class="text-left">鼎新品號</th>
                            <th width="28%" class="text-left">商品名稱</th>
                            <th width="7%" class="text-right">原採購金額<br>變更後金額</th>
                            <th width="7%" class="text-right">原採購數量<br>變更後數量</th>
                            <th width="12%" class="text-center">原入庫日期<br>變更後日期</th>
                            <th width="5%" class="text-center">結案否</th>
                        </tr>
                    </thead>
                    <tbody id="modifyRecord"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">
{{-- DataTable --}}
<link rel="stylesheet" href="{{ asset('vendor/datatables/media/css/jquery.dataTables.min.css') }}">
{{-- <link rel="stylesheet" href="{{ asset('vendor/datatables/media/css/dataTables.bootstrap4.min.css') }}"> --}}
<link rel="stylesheet" href="{{ 'https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css' }}">
{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css') }}">
@endsection

@section('script')
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
{{-- DataTable --}}
<script src="{{ asset('vendor/datatables/media/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ 'https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js' }}"></script>
{{-- <script src="{{ asset('vendor/datatables/media/js/dataTables.bootstrap4.min.js') }}"></script> --}}
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
{{-- multiselect --}}
<script src="{{ asset('vendor/multiselect/dist/js/multiselect.min.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('[data-toggle="popover"]').popover({
            html: true,
            sanitize: false,
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

        $('.select2').select2();

        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $('#reset').click(function(){
            $('#start_date').attr('value', '');
            $('#end_date').attr('value', '');
        });

        $(document).ready(function($) {
            $('#vendorSelect').multiselect({
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

        $('.btn-send').click(function(){
            let form = $('#statementForm');
            let vendorIds = $('#vendorSelect').val();
            let paymentCon = $('#paymentCon').val();
            if(vendorIds[0]){
                $('#paymentCon').remove();
            }else if(paymentCon[0]){
                $('#vendorSelect').remove();
            }
            form.submit();
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
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
                    alert('尚未選擇對帳單');
                    return;
                }
            }else if(multiProcess == 'byQuery'){ //by條件
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="statement">') );
            form.submit();
            $('.formappend').remove();
            $('#multiModal').modal('hide');
        });

        $('.purchaseProcess').click(function(){
            let form = $('#multiProcessForm');
            form.append( $('<input type="hidden" class="formappend" name="filename" value="商品採購">') );
            $('#multiProcessForm > input[name=type]').val($(this).val());
            if($(this).val() == 'all'){
                form.submit();
            }else{
                $('#multiProcessForm > input[name=orderIds]').remove();
                let selected = $('.purchase_data:checked').serializeArray().map( item => item.value );
                for(let j=0; j<selected.length;j++){
                    let tmp = '';
                    tmp = $('<input type="hidden" class="formappend" name="selected['+j+']" value="'+selected[j]+'">');
                    form.append(tmp);
                }
                form.submit();
            }
            $('.formappend').remove();
        });

        $('#multiProcess').click(function(){
            if($('input[name="multiProcess"]:checked').val() == 'selected'){
                let num = $('input[name="chk_box"]:checked').length;
                if(num == 0){
                    alert('尚未選擇對帳單');
                    return;
                }
            }
            $('#multiModal').modal('show');
        });

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#orderSearchForm').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });

        $('#createForm').click(function(){
            let text = $('#createForm').html();
            $('#statementCreateForm').toggle();
            text == '建立對帳單' ? $('#createForm').html('取消') : $('#createForm').html('建立對帳單');
        });

        $('.btn-cancel').click(function (e) {
            let id = $(this).val();
            let form = $('#cancelForm');
            if(confirm('請確認是否要取消這筆採購單?')){
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
        });

        $('#invoice_date_end').change(function(){
            $('input[name="invoice_date_not_fill"]').prop('checked',false);
        });

        $('#invoice_date_not_fill').click(function(){
            if($('input[name="invoice_date_not_fill"]:checked').length > 0){
                $('#invoice_date').val('');
                $('#invoice_date_end').val('');
            };
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

    function removeCondition(name){
        if(name == 'start_date' || name == 'end_date'){
            $('#start_date').attr('value', '');
            $('#end_date').attr('value', '');
        }else if(name == 'invoice_date'){
            $('input[name="'+name+'"]').val('');
            $('input[name="'+name+'_end"]').val('');
        }else if(name == 'payment_com' || name === 'notice'){
            $('#'+name).val('');
        }else{
            $('input[name="'+name+'"]').val('');
        }
        $("#searchForm").submit();
    }
</script>
@endsection

