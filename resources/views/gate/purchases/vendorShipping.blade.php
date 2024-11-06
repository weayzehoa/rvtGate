@extends('gate.layouts.master')

@section('title', '商家出貨單資料')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>商家出貨單資料</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('vendorShipping') }}">商家出貨單資料</a></li>
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
                                <form id="searchForm" role="form" action="{{ url('vendorShipping') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6 mt-2">
                                                <label for="status">出貨單狀態:</label>
                                                <select class="form-control" id="status" size="5" multiple>
                                                    <option value="0"  {{ isset($status) ? in_array(0,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-secondary">待出貨</option>
                                                    <option value="1"  {{ isset($status) ? in_array(1,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">準備出貨</option>
                                                    <option value="2"  {{ isset($status) ? in_array(2,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-info">出貨中</option>
                                                    <option value="3"  {{ isset($status) ? in_array(3,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-success">已全部出貨</option>
                                                    <option value="4"  {{ isset($status) ? in_array(4,explode(',',$status)) ? 'selected' : '' : 'selected' }} class="text-primary">已完成入庫</option>
                                                </select><input type="hidden" value="0,1,2,3,4" name="status" id="status_hidden" />
                                            </div>
                                            <div class="col-6">
                                                <div class="row">
                                                    <div class="col-4 mt-2">
                                                        <label for="purchase_no">商家出貨單號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="shipping_no" name="shipping_no" placeholder="商家出貨單號" value="{{ isset($shipping_no) && $shipping_no ? $shipping_no : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-4 mt-2">
                                                        <label for="purchase_no">iCarry採購單號:</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="purchase_no" name="purchase_no" placeholder="iCarry採購單編號" value="{{ isset($purchase_no) && $purchase_no ? $purchase_no : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-4 mt-2">
                                                        <label for="order_number">iCarry訂單編號:(限直寄)</label>
                                                        <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單編號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="vendor_name">商家:</label>
                                                        <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <label class="control-label" for="product_name">商品名稱:</label>
                                                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品名稱ex:肉鬆蛋捲" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                                    </div>
                                                </div>
                                           </div>
                                           <div class="col-6 mt-2">
                                                <label for="vendor_arrival_date">應到貨日區間:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control datepicker" id="vendor_arrival_date" name="vendor_arrival_date" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date) ? $vendor_arrival_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="text" class="form-control datepicker" id="vendor_arrival_date_end" name="vendor_arrival_date_end" placeholder="格式：2016-06-06" value="{{ isset($vendor_arrival_date_end) ? $vendor_arrival_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="shipping_date">出貨日期區間:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control datepicker" id="shipping_date" name="shipping_date" placeholder="格式：2016-06-06" value="{{ isset($shipping_date) ? $shipping_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="text" class="form-control datepicker" id="shipping_date_end" name="shipping_date_end" placeholder="格式：2016-06-06" value="{{ isset($shipping_date_end) ? $shipping_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="express_way">物流商:</label>
                                                <input type="text" class="form-control" id="express_way" name="express_way" placeholder="填寫物流商" value="{{ isset($express_way) ? $express_way ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="express_no">物流單號:</label>
                                                <input type="text" class="form-control" id="express_no" name="express_no" placeholder="填寫物流單號" value="{{ isset($express_no) ? $express_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="digiwin_no">商品貨號:</label>
                                                <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫商品貨號" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
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
                                </form>
                            </div>
                            @if(count($shippings) > 0)
                            <div class="col-12"  style="overflow: auto">
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="25%">出貨單資訊</th>
                                            <th class="text-left" width="75%">品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($shippings as $shipping)
                                        <tr style="border-bottom:3px #000000 solid;border-bottom:3px #000000 solid;">
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    <input type="checkbox" class="chk_box_{{ $shipping->id }}" name="chk_box" value="{{ $shipping->id }}">
                                                    <span class="text-lg text-bold text-primary">{{ $shipping->shipping_no }}</span>
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-6 mb-1">
                                                        <span class="text-sm">所屬商家：{{ $shipping->vendor->name }}</span><br>
                                                        <span class="text-sm">應到貨日：{{ $shipping->vendor_arrival_date }}</span>
                                                    </div>
                                                    <div class="col-6 mb-1">
                                                        <span class="status_{{ $shipping->id }} text-bold">
                                                            @if($shipping->status == -1)
                                                                已取消
                                                            @elseif($shipping->status == 0)
                                                                待出貨
                                                            @elseif($shipping->status == 1)
                                                                準備出貨
                                                            @elseif($shipping->status == 2)
                                                                出貨中
                                                            @elseif($shipping->status == 3)
                                                                已全部出貨
                                                            @elseif($shipping->status == 4)
                                                                已完成入庫
                                                            @endif
                                                            <br>
                                                            @if($shipping->status == 3)
                                                            <span class="forhide mt-1 badge badge-success">已全部出貨：{{ $shipping->status == 3 ? str_replace('-','/',$shipping->shipping_finish_date) : '無' }}</span>
                                                            @endif
                                                            @if($shipping->status == 4)
                                                            <span class="forhide mt-1 badge badge-primary">已完成入庫：{{ $shipping->status == 4 ? str_replace('-','/',$shipping->stockin_finish_date) : '無' }}</span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="col-12">
                                                        <span class="text-sm">商家備註：{{ $shipping->memo }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-left align-top p-0">
                                                <table class="table table-sm">
                                                    <thead class="table-info">
                                                        <th width="10%" class="text-left align-middle text-sm">採購單號</th>
                                                        <th width="10%" class="text-left align-middle text-sm">訂單單號</th>
                                                        <th width="15%" class="text-left align-middle text-sm">貨號</th>
                                                        <th width="22%" class="text-left align-middle text-sm">品名</th>
                                                        <th width="5%" class="text-right align-middle text-sm">數量</th>
                                                        <th width="30%" class="text-left align-middle text-sm">
                                                            <div class="row">
                                                                <div class="col-3">出貨日</div>
                                                                <div class="col-3">物流商</div>
                                                                <div class="col-6">物流單號</div>
                                                            </div>
                                                        </th>
                                                        <th width="8%" class="text-center align-middle text-sm"></th>
                                                    </thead>
                                                    <tbody>
                                                        @if(count($shipping->items) > 0)
                                                            @foreach($shipping->items as $item)
                                                            <form>
                                                            <tr>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_no }}</span>
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    @if($item->direct_shipment == 1)<span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->order_numbers }}</span>@endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->digiwin_no }}
                                                                    @if($item->direct_shipment == 1)
                                                                    <span class="text-primary"><i class="fas fa-truck" title="廠商直寄"></i></span>
                                                                    @endif
                                                                    </span>
                                                                </td>
                                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->product_name }}</span>
                                                                </td>
                                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">
                                                                    {{ number_format($item->quantity) }}
                                                                </td>
                                                                <td class="text-left align-middle text-sm item_qty_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                                    <div class="row">
                                                                        @if(count($item->express) > 0)
                                                                        @foreach($item->express as $express)
                                                                        <div class="col-3">{{ $express->shipping_date }}</div>
                                                                        <div class="col-3">{{ $express->express_way }}</div>
                                                                        <div class="col-6">{{ $express->express_no }}</div>
                                                                        @endforeach
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">
                                                                    @if($item->is_del == 0)
                                                                    @if(count($item->stockins) > 0)
                                                                    <span>已入庫</span>
                                                                    @endif
                                                                    @else
                                                                    <span>已取消</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @if(strstr($item->sku,'BOM'))
                                                                @if(count($item->packages)>0)
                                                                <tr class="m-0 p-0">
                                                                    <td colspan="7" class="text-sm p-0">
                                                                        <table width="100%" class="table-sm m-0 p-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th width="10%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="10%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                                    <th width="22%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                                    <th width="5%"  class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                                    <th width="30%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                    <th width="8%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($item->packages as $packageItem)
                                                                                <tr>
                                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                                    <td class="text-left align-middle text-sm" ><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</span></td>
                                                                                    <td class="text-left align-middle text-sm" ><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $packageItem['quantity'] }}</span></td>
                                                                                    <td class="text-right align-middle text-sm" ></td>
                                                                                    <td class="text-right align-middle text-sm"></td>
                                                                                    <td class="text-right align-middle text-sm"></td>
                                                                                </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                                @endif
                                                            @endif
                                                            </form>
                                                            @endforeach
                                                        @endif
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($shippings) ? number_format($shippings->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(!empty($shippings))
                                @if(isset($appends))
                                {{ $shippings->appends($appends)->render() }}
                                @else
                                {{ $shippings->render() }}
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <form id="multiProcessForm" action="{{ url('shipping/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="cancelForm" action="{{ url('shipping/cancel') }}" method="POST">
        @csrf
    </form>
</div>
@endsection

@section('modal')
{{-- 填寫物流資料 Modal --}}
<div id="myModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">物流資料填寫</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group form-group-sm">
                    <form id="modalForm" action="{{ url('shipping/fillData') }}" method="POST">
                        @csrf
                        <div>
                            <label class="text-danger text-bold" id="shippingLabel"></label>
                            <div id="data">
                                <div class="input-group">
                                    <div class="input-group-prepend" >
                                        <span class="input-group-text">出貨日</span>
                                    </div>
                                    <div class="input-group-prepend" style="width:20%">
                                        <input type="text" class="form-control datepicker" name="data[0][shipping_date]" placeholder="請填寫出貨日，格式: 2023-05-05" required>
                                    </div>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">物流商</span>
                                    </div>
                                    <div class="input-group-prepend" style="width:20%">
                                        <input type="text" class="form-control" name="data[0][express_way]" placeholder="請填寫物流商，ex:台灣宅配通" required>
                                    </div>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">物流單號</span>
                                    </div>
                                    <div class="input-group-prepend" style="width:35%">
                                        <input type="text" class="form-control" name="data[0][express_no]" placeholder="多筆單號請用逗號,隔開" required>
                                    </div>
                                    <div class="input-group-append shippingBtn">
                                        <span class="btn btn-sm btn-danger btn-remove">移除</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="float-left"><button type="button" id="addShipping" class="btn btn-sm btn-primary mt-2">新增一筆</button></div>
                        <div class="float-right"><button type="submit" class="btn btn-sm btn-primary mt-2">送出</button></div>
                    </form>
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
                <form  id="importForm" action="{{ url('shipping/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
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
                    <span class="text-danger" id="importModalNotice">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/入庫報表範本.xls" target="_blank">入庫報表範本</a> ，製作正確的檔案。</span>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- 備註 Modal --}}
<div id="memoModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memoModalLabel">商家備註資料</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="memoForm" action="{{ url('shipping/updateMemo') }}" method="POST">
                    @csrf
                    <input type="hidden" id="shippingId" name="id">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="memo" name="memo" class="form-control" autocomplete="off" placeholder="請輸入備註(最多200字)">
                            <button type="submit" class="btn btn-md btn-primary">送出</button>
                        </div>
                    </div>
                </form>
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

        $('#importBtn').click(function(){
            let form = $('#importForm');
            $('#importBtn').attr('disabled',true);
            form.submit();
        });

        $('.btn-remove').click(function(){
            $(this).parent('div').parent('div').remove();
        });

        $('#addShipping').click(function(){
            let count = $('#data > .input-group').length;
            if(count != 0){
                count = Math.floor(Math.random()*10000);
            }
            let html = '<div class="input-group"><div class="input-group-prepend" ><span class="input-group-text">出貨日</span></div><div class="input-group-prepend" style="width:20%"><input type="text" class="form-control datepicker" name="data['+count+'][shipping_date]" placeholder="請填寫出貨日，格式: 2023-05-05" required></div><div class="input-group-prepend"><span class="input-group-text">物流商</span></div><div class="input-group-prepend" style="width:20%"><input type="text" class="form-control" name="data['+count+'][express_way]" placeholder="請填寫物流商，ex:台灣宅配通" required></div><div class="input-group-prepend"><span class="input-group-text">物流單號</span></div><div class="input-group-prepend" style="width:35%"><input type="text" class="form-control" name="data['+count+'][express_no]" placeholder="多筆單號請用逗號,隔開" required></div><div class="input-group-append shippingBtn"><span class="btn btn-sm btn-danger btn-remove">移除</span></div></div>';
            $('#data').append(html);
            $('.btn-remove').click(function(){
                $(this).parent('div').parent('div').remove();
            });
            $('.datepicker').datepicker({
                timeFormat: "HH:mm:ss",
                dateFormat: "yy-mm-dd",
            });
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

        $('.btn-memo').click(function(){
            let id = $(this).val();
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'shipping/getMemo',
                data: { id: id, _token: token },
                success: function(data) {
                    console.log(data);
                    $('#shippingId').val(id);
                    $('input[name=memo]').val(data);
                }
            });
            $('#memoModal').modal('show');
            return;
        });

        $('.btn-edit').click(function(){
            let method = ($(this).val()).split('_')[0];
            let id = ($(this).val()).split('_')[1];
            let directShip = ($(this).val()).split('_')[2];
            let form = $('#modalForm');
            form.append($('<input type="hidden" class="formappend" name="method" value="'+method+'">'));
            form.append($('<input type="hidden" class="formappend" name="id" value="'+id+'">'));
            if(method == 'shipping'){
                let text = '請確認貨物已經出貨，再填寫物流資料，填寫後，此出貨單將無法被取消，請勿提早填寫以免無法取消修改。<br>此功能僅適用批次填寫本出貨單上所有送至 iCary 倉庫的商品，且未曾填寫物流資料，不適用於直寄商品。';
                $('#shippingLabel').html(text);
                if(confirm('1. 請確認貨物已經出貨，再填寫物流資料，填寫後，此出貨單將無法被取消，請勿提早填寫以免無法取消修改。\n2. 此功能僅適用出貨單上所有送至 iCary 倉庫的商品，不適用直寄商品。\n3. 再次重新填寫則將會覆蓋掉前次輸入的物流資料。\n4. 直寄商品的物流資料請先填寫於直寄訂單入庫管理表Excel檔案，並由上方匯入檔案，或按右邊編輯圖案單筆填寫。')){
                    $('#myModal').modal('show');
                }
            }else if(method == 'item'){
                let text = '請確認貨物已經出貨，再填寫物流資料，填寫後，此出貨單將無法被取消，請勿提早填寫以免無法取消修改。再次重新填寫則將會覆蓋掉前次輸入的物流資料。';
                $('#shippingLabel').html(text);
                if(directShip == 1){
                    $('.btn-remove').remove();
                    $('#addShipping').prop('disabled',true);
                }else{
                    $('#addShipping').prop('disabled',false);
                    $('.shippingBtn').html('<span class="btn btn-sm btn-danger btn-remove">移除</span>');
                    $('.btn-remove').click(function(){
                        $(this).parent('div').parent('div').remove();
                    });
                }
                if(confirm('1. 請確認貨物已經出貨，再填寫物流資料，填寫後，此出貨單將無法被取消，請勿提早填寫以免無法取消修改。\n2. 此功能僅適用單一筆商品物流資料填寫或修改。\n3. 直寄商品的物流資料可由上方匯入檔案方式一次填寫。\n4. 再次重新填寫則將會覆蓋掉前次輸入的物流資料。\n5. 直寄資料將會顯示於 iCarry 後台，若已被 iCarry 入庫處理完成則將無法修改。')){
                    $('#myModal').modal('show');
                }
            }
            $('#myModal').modal('hide');
            return;
        });

        $('.btn-export').click(function(){
            let id = $(this).val();
            let form = $('#multiProcessForm');
            form.append($('<input type="hidden" class="formappend" name="id[0]">').val(id));
            form.append($('<input type="hidden" class="formappend" name="method" value="selected">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="shippingExport">'));
            form.append($('<input type="hidden" class="formappend" name="type" value="oneShipping">'));
            form.append( $('<input type="hidden" class="formappend" name="filename" value="匯出出貨單_單筆出貨單">') );
            form.append( $('<input type="hidden" class="formappend" name="model" value="shipping">') );
            form.submit();
            // setTimeout(() => {
            //     alert("檔案下載後請使用密碼將檔案解壓縮。");
            //     location.reload();
            // }, 1000);
            alert('檔案下載後請使用密碼將檔案解壓縮，並按F5重整頁面。');
        });
        $('#multiProcess').click(function(){
            if($('input[name="multiProcess"]:checked').val() == 'selected'){
                let num = $('input[name="chk_box"]:checked').length;
                if(num == 0){
                    alert('尚未選擇iCarry採購單');
                    return;
                }
            }

            let form = $('#multiProcessForm');
            let cate = $(this).val().split('_')[0];
            let type = $(this).val().split('_')[1];
            let filename = $(this).html();
            let condition = null;
            let multiProcess = $('input[name="multiProcess"]:checked').val();
            let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
            if(multiProcess == 'allOnPage' || multiProcess == 'selected'){
                if(cate == 'Export'){
                    let start = $('#vendor_arrival_date').val();
                    let end = $('#vendor_arrival_date_end').val();
                    form.append($('<input type="hidden" class="formappend" name="vendor_arrival_date">').val(start));
                    form.append($('<input type="hidden" class="formappend" name="vendor_arrival_date_end">').val(end));
                }
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="shipping">') );
            form.submit();
            $('.formappend').remove();
            $('#multiModal').modal('hide');
            setTimeout(() => {
                alert("檔案下載後請使用密碼將檔案解壓縮。");
                location.reload();
            }, 1000);
            return;
        });

        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#orderSearchForm').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });

        $('#showImportForm').click(function(){
            let form = $('#importForm');
            let label = '請選擇廠商直寄檔案';
            let notice = '注意! 匯入的資料將會覆蓋掉已經填寫的資料，若該筆資料 iCarry 已經完成出貨入庫後，則無法再覆蓋資料。<br>請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/入庫報表範本.xls" target="_blank">直寄訂單入庫單範本</a> ，製作正確的檔案。';
            $('#importModalLabel').html(label);
            $('#importModalNotice').html(notice);
            $('#importModal').modal('show');
        });

        $('.btn-cancel').click(function (e) {
            let method = ($(this).val()).split('_')[0];
            let id = ($(this).val()).split('_')[1];
            let form = $('#cancelForm');
            if(confirm('請確認是否要取消這筆出貨單?\n注意! 取消動作將會取消相同採購單號、相同貨號的資料，\n但若已入庫則不會被取消。\n取消前請務必與iCarry管理員確認。')){
                form.append('<input type="hidden" class="formappend" name="method" value="'+method+'">')
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
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
</script>
@endsection
