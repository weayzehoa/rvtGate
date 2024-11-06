@extends('gate.layouts.master')

@section('title', '折抵單/退貨單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>折抵單/退貨單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('purchases') }}">折抵單/退貨單管理</a></li>
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
                                    <button id="showForm" class="btn btn-sm btn-success" title="使用欄位查詢">使用欄位查詢</button>
                                    @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                    <a href="{{ route('gate.returnDiscounts.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i>新增折抵單</a>
                                    @endif
                                </div>
                                <div class="col-7">
                                    <div class=" float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($returns) ? number_format($returns->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="col-8 float-left">
                                        <span clas="d-flex align-items-center">查詢條件：</span>
                                        @if(!empty($created_at) || !empty($created_at_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('created_at')">X </span>
                                            建立時間區間：
                                            @if(!empty($created_at)){{ $created_at.' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                            @if(!empty($created_at_end)){{ '至 '.$created_at_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($return_date) || !empty($return_date_end))
                                        <span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('return_date')">X </span>
                                            退貨日期區間：
                                            @if(!empty($return_date)){{ $return_date.' ' }}@else{{ '2015-01-01' }}@endif
                                            @if(!empty($return_date_end)){{ '至 '.$return_date_end.' ' }}@else{{ '至 現在' }}@endif
                                        </span>
                                        @endif
                                        @if(!empty($product_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('product_name')">X</span> 商品名稱：{{ $product_name }}</span>@endif
                                        @if(!empty($purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('purchase_no')">X</span> iCarry採購單號：{{ $purchase_no }}</span>@endif
                                        @if(!empty($erp_purchase_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('erp_purchase_no')">X</span> 鼎新採購單號：{{ $erp_purchase_no }}</span>@endif
                                        @if(!empty($return_discount_no))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('return_discount_no')">X</span> 折抵/退貨單號：{{ $return_discount_no }}</span>@endif
                                        @if(!empty($vendor_name))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('vendor_name')">X</span> 商家名稱：{{ $vendor_name }}</span>@endif
                                        @if(!empty($is_del))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('is_del')">X</span> 是否取消：{{ $is_del == 1 ? '已取消' : '未取消' }}</span>@endif
                                        @if($list)<span class="badge badge-info mr-1">每頁：{{ $list }} 筆</span>@endif
                                    </div>
                                    {{-- <div class="col-4 float-right">
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
                                    </div> --}}
                                </div>
                                <div class="col-12 mt-2" id="showExpressTable" style="display: none">
                                    <div id="expressData" class="row"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="orderSearchForm" class="card card-primary" style="display: none">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('returnDiscounts') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6 mt-2">
                                                <label for="order_number">折抵/退貨單單號:</label>
                                                <input type="text" class="form-control" id="return_discount_no" name="return_discount_no" placeholder="iCarry退貨單單號" value="{{ isset($return_discount_no) && $return_discount_no ? $return_discount_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="order_number">iCarry採購單號:</label>
                                                <input type="text" class="form-control" id="purchase_no" name="purchase_no" placeholder="iCarry採購單編號" value="{{ isset($purchase_no) && $purchase_no ? $purchase_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label for="vendor_name">商家名稱:</label>
                                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱:海邊走走" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="product_name">商品名稱:</label>
                                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-6 mt-2">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label class="control-label" for="created_at">退貨日期區間:</label>
                                                        <div class="input-group">
                                                            <input type="datetime" class="form-control datepicker" id="return_date" name="return_date" placeholder="格式：2016-06-06" value="{{ isset($return_date) ? $return_date ?? '' : '' }}" autocomplete="off" />
                                                            <span class="input-group-addon bg-primary">~</span>
                                                            <input type="datetime" class="form-control datepicker" id="return_date_end" name="return_date_end" placeholder="格式：2016-06-06" value="{{ isset($return_date_end) ? $return_date_end ?? '' : '' }}" autocomplete="off" />
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="control-label" for="created_at">是否取消:</label>
                                                        <div class="input-group">
                                                            <select class="form-control" name="is_del" id="is_del">
                                                                <option value="">不拘</option>
                                                                <option value="1" {{ isset($is_del) && $is_del == 1 ? 'selected' : '' }}>已取消</option>
                                                                <option value="0" {{ isset($is_del) && $is_del == 0 ? 'selected' : '' }}>未取消</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label for="book_shipping_date">類別:</label>
                                                        <div class="input-group">
                                                            <select class="form-control" name="cate">
                                                                <option value="">不拘</option>
                                                                <option value="discount">折抵單</option>
                                                                <option value="return">退貨單</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
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
                           @if(count($returns) > 0)
                           <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="25%">折抵單/退貨單資訊</th>
                                            <th class="text-left" width="75%">折抵/退貨品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($returns as $return)
                                        <tr style="border-bottom:3px #000000 solid;border-bottom:3px #000000 solid;">
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    {{-- <input type="checkbox" class="chk_box_{{ $return->id }}" name="chk_box" value="{{ $return->id }}"> --}}
                                                    <a href="{{ route('gate.returnDiscounts.show', $return->id) }}" class="mr-2">
                                                        <span class="text-lg text-bold order_number_{{ $return->id }}">{{ $return->return_discount_no }}</span>
                                                    </a>
                                                    @if($return->is_del == 0)
                                                    @if($return->is_lock==0)
                                                    @if(in_array($menuCode.'CO', explode(',',Auth::user()->power)))
                                                    <button type="button" value="{{ $return->id }}" class="badge btn-sm btn btn-danger btn-cancel">取消{{ $return->type == 'A351' ? '退貨' : '折抵' }}單</button>
                                                    @endif
                                                    @endif
                                                    @else
                                                    <span class="badge bagde-sm badge-warning">已被取消</span>
                                                    @endif
                                                    {{-- @if(count($return->checkStockin) > 0)
                                                    <a href="{{ route('gate.purchases.returnForm', $return->id) }}" value="{{ $return->id }}" class="badge btn-sm btn btn-warning">退貨</a>
                                                    @endif --}}
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <span class="text-sm">商　　家：{{ $return->vendor_name }}</span><br>
                                                        <span class="text-sm">金　　額：{{ $return->amount }}</span><br>
                                                        <span class="text-sm">稅　　金：{{ $return->tax }}</span><br>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-sm">類　　別：{{ $return->type == 'A351' ? '退貨' : '折抵' }}</span><br>
                                                        <span class="text-sm">商品總數：{{ $return->quantity }}</span><br>
                                                        <span class="text-sm">總計金額：{{ $return->amount + $return->tax }}</span><br>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-left align-top p-0">
                                                <table class="table table-sm">
                                                    <thead class="table-info">
                                                        <th width="15%" class="text-left align-middle text-sm">採購單號</th>
                                                        <th width="15%" class="text-left align-middle text-sm">貨號</th>
                                                        <th width="35%" class="text-left align-middle text-sm">品名</th>
                                                        <th width="5%" class="text-center align-middle text-sm">倉別</th>
                                                        <th width="5%" class="text-center align-middle text-sm">單位</th>
                                                        <th width="5%" class="text-right align-middle text-sm">{{ $return->type == 'A352' ? null : '數量' }}</th>
                                                        <th width="10%" class="text-right align-middle text-sm">{{ $return->type == 'A352' ? '折抵價' : '採購價' }}</th>
                                                        <th width="10%" class="text-right align-middle text-sm">總價</th>
                                                    </thead>
                                                    <tbody>
                                                        <form id="itemsform_order_{{ $return->id }}" method="POST">
                                                            @foreach($return->items as $item)
                                                            <tr>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_no }}</span>
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->sku }}</span>
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->product_name }}</span>
                                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已取消)</span>@endif
                                                                </td>
                                                                <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->direct_shipment == 1 ? 'W02' : 'W01' }}</span></td>
                                                                <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->unit_name }}</span></td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $return->type == 'A352' ? null : number_format($item->quantity) }}</span></td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span></td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price * $item->quantity }}</span></td>
                                                            </tr>
                                                            @if(strstr($item->sku,'BOM'))
                                                                @if(count($item->packages)>0)
                                                                <tr class="m-0 p-0">
                                                                    <td colspan="8" class="text-sm p-0">
                                                                        <table width="100%" class="table-sm m-0 p-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                                    <th width="35%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                                    <th width="5%" class="text-center align-middle text-sm" style="border: none; outline: none">倉別</th>
                                                                                    <th width="5%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">{{ $return->type == 'A352' ? null : '數量' }}</th>
                                                                                    <th width="10%" class="text-right align-middle text-sm" style="border: none; outline: none">{{ $return->type == 'A352' ? '折抵價' : '採購價' }}</th>
                                                                                    <th width="10%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($item->packages as $packageItem)
                                                                                <tr>
                                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                                    <td class="text-left align-middle text-sm" >
                                                                                        <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['sku'] }}</span>
                                                                                    </td>
                                                                                    <td class="text-left align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</span></td>
                                                                                    <td class="text-center align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['direct_shipment'] == 1 ? 'W02' : 'W01' }}</span></td>
                                                                                    <td class="text-center align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['quantity'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['purchase_price'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ></td>
                                                                                </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                @endif
                                                            @endif
                                                            @endforeach
                                                        </form>
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($returns) ? number_format($returns->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $returns->appends($appends)->render() }}
                                @else
                                {{ $returns->render() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="multiProcessForm" action="{{ url('purchases/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="cancelForm" action="{{ url('returnDiscounts/cancel') }}" method="POST">
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
                <form action="{{ url('purchases/notice') }}" method="POST" class="float-right">
                    @csrf
                    <input type="hidden" id="purchaseOrderId" name="id" value="">
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
                    <button class="btn btn-sm btn-primary multiProcess mr-2" value="SyncToDigiwin">中繼同步至鼎新</button>
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Export_Stockin">匯出入庫單</button>
                    <button class="btn btn-sm btn-success multiProcess mr-2" value="Export_WithSingle">匯出採購單(組合+單品)</button>
                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="Notice_Email">通知廠商</button>
                    <button class="btn btn-sm btn-warning multiProcess mr-2" value="Notice_Download">通知廠商(下載)</button>
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
                    alert('尚未選擇訂單或商品');
                    return;
                }
            }else if(multiProcess == 'byQuery'){ //by條件
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
                condition = $('#searchForm').serializeArray();
                let con_val = $('#searchForm').serializeArray().map( item => item.value );
                let con_name = $('#searchForm').serializeArray().map( item => item.name );
                for(let j=0; j<con_name.length;j++){
                    let tmp = '';
                    tmp = $('<input type="hidden" class="formappend" name="con['+con_name[j]+']" value="'+con_val[j]+'">');
                    form.append(tmp);
                }
            }else if(cate == 'SyncToGate'){ //鼎新同步至中繼
                form.submit();
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="purchase">') );
            if(cate == 'Export' || cate == 'Notice'){
                alert('注意! 匯出採購單與通知廠商僅會 匯出/通知 已採購、已完成入庫 狀態，\n尚未採購狀態的採購單，請先同步至鼎新才可使用此功能。')
            }
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
                    alert('尚未選擇訂單');
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

        $('.moreOption').click(function(){
            $('#MoreSearch').toggle();
            $(this).html() == '更多選項' ? $(this).html('隱藏更多選項') : $(this).html('更多選項');
        });

        $('.modifybyQuerySend').click(function(){
            $('#mark').submit();
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

        $('#stockinImport').click(function(){
            $('#importModal').modal('show');
        });

        $('input[type=file]').change(function(x) {
            let name = this.name;
            let file = x.currentTarget.files;
            let filename = file[0].name; //不檢查檔案直接找出檔名
            if (file.length >= 1) {
                if (filename) {
                    $('label[for=' + name + ']').html(filename);
                } else {
                    $(this).val('');
                    $('label[for=' + name + ']').html('瀏覽選擇EXCEL檔案');
                }
            } else {
                $(this).val('');
                $('label[for=' + name + ']').html('瀏覽選擇EXCEL檔案');
            }
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

    function getLog(purchase_number,purchase_id,column_name,column_value,e,item_id){
        let token = '{{ csrf_token() }}';
        let id = purchase_id;
        let datepicker = '';
        let dateFormat = 'yy-mm-dd';
        let timeFormat = 'HH:mm:ss';
        let html = '';
        let label = '採購單號：'+purchase_number+'，同步紀錄';
        $('#myform').html('');
        $('#record').html('');
        $('#syncRecord').html('');
        $('#myrecord').addClass('d-none');
        $('#syncModal').modal('show');
        $('#myrecord').removeClass('d-none');
        $.ajax({
            type: "post",
            url: 'purchases/getlog',
            data: { id: id, _token: token },
            success: function(data) {
                let record = '';
                if(data.length > 0){
                        let purchaseNo = data[0]['purchase_no'];
                        let erpPurchaseNo = data[0]['erp_purchase_no'];
                        let purchaseOrderId = data[0]['purchase_order_id'];
                        for(let i=0; i<data.length; i++){
                            let dateTime = data[i]['synced_time'];
                            let amount = data[i]['amount'];
                            let quantity = data[i]['quantity'];
                            let tax = data[i]['tax'];
                            let noticeTime = data[i]['notice_time'];
                            noticeTime == null ? noticeTime = '' : '';
                            let total = parseFloat(amount) + parseFloat(tax);
                            let record = '<tr><td class="text-center">'+i+'</td><td class="text-left">'+dateTime+'</td><td class="text-left">'+quantity+'</td><td class="text-right">'+amount+'</td><td class="text-right">'+tax+'</td><td class="text-right">'+total+'</td><td class="text-center">'+noticeTime+'</td></tr>';
                            $('#syncRecord').append(record);
                            $('#purchaseOrderId').val(purchaseOrderId);
                        }
                        label = '採購單編號：'+purchaseNo+'，鼎新採購單編號：'+erpPurchaseNo+'，同步紀錄';
                }
                $('#syncModalLabel').html(label);
                $('#syncModal').modal('show');
            }
        });
    }


    function itemmemo (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let memo = $(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/itemmemo',
                data: { id: id, memo: memo , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        $("#item_memo_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="itemmemo(event,'+id+');">'+memo+'</textarea>');
                        $("#item_memo_"+id).html('<i class="fa fa-info-circle"></i>');
                        $("#item_memo_"+id).popover('hide');
                    }
                }
            });
        }
    }

    function itemQty (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let qty = $(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/qtyModify',
                data: { id: id, type: 'item', qty: qty , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        if(qty == 0){
                            $("#item_qty_"+id).popover('hide');
                            $("#item_qty_"+id).remove();
                            $(".stockin_item_"+id).html('');
                            $(".stockin_item_"+id).attr("data-content",'');
                            $(".item_qty_"+id).html('');
                        }else{
                            $("#item_qty_"+id).popover('hide');
                            $("#item_qty_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="itemQty(event,'+id+');">'+qty+'</textarea>');
                            $("#item_qty_"+id).html('<span class="badge badge-purple badge-sm text-xs">改</span>');
                            $(".new_item_qty_"+id).html(qty);
                        }
                    }
                }
            });
        }
    }

    function packageQty (event,id){
        if(event.keyCode==13){
            event.preventDefault();
            let qty = $(event.target).val();//.replace(/\n/g,"");
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchases/qtyModify',
                data: { id: id, type: 'package', qty: qty , _token: token },
                success: function(data) {
                    if(data == 'success'){
                        if(qty == 0){
                            $("#package_qty_"+id).popover('hide');
                            $("#package_qty_"+id).remove();
                            $(".stockin_package_"+id).html('');
                            $(".stockin_package_"+id).attr("data-content",'');
                            $(".package_qty_"+id).html('');
                        }else{
                            $("#package_qty_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="packageQty(event,'+id+');">'+qty+'</textarea>');
                            $("#package_qty_"+id).html('<span class="badge badge-purple badge-sm text-xs">改</span>');
                            $("#package_qty_"+id).popover('hide');
                            $(".new_package_qty_"+id).html(qty);
                        }
                    }
                }
            });
        }
    }


    function removeCondition(name){
        let sel="";
        $("#status>option:selected").each(function(){
            sel+=","+$(this).val();
        });
        $("#status_hidden").val(sel.substring(1));
        if(name == 'vendor_arrival_date' || name == 'return_date' || name == 'created_at' || name == 'book_shipping_date'){
            $('input[name="'+name+'"]').val('');
            $('input[name="'+name+'_end"]').val('');
        }else if(name == 'status'){
            $('input[name="'+name+'"]').val('0');
        }else if(name == 'is_del'){
            $('#is_del').val('');
        }else{
            $('input[name="'+name+'"]').val('');
        }
        $("#searchForm").submit();
    }
</script>
@endsection

