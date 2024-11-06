@extends('gate.layouts.master')

@section('title', '團購訂單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>訂單管理</b><span class="badge badge-success text-sm">{{ $order->order_number }}</span></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">團購訂單管理</a></li>
                        <li class="breadcrumb-item active">{{ isset($order) ? '修改' : '新增' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        @if(isset($order))
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">訂單資料</h3>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 mb-2">
                                    <span class="text-bold">訂單資訊</span>
                                </div>
                                <div class="col-1 mb-2">
                                    <button class="btn btn-block btn-primary userInfo mr-2" value="{{ $order->id }}">訂單資訊</button>
                                </div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">建單時間</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $order->create_time ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">付款時間</span>
                                        </div>
                                        <input type="datetime" class="form-control datetimepicker" id="pay_time" value="{{ $order->pay_time ?? null}}" disabled>
                                    </div>
                                </div>
                                @if(!empty($order->partner_order_number))
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">iCarry訂單號碼</span>
                                        </div>
                                        <input type="datetime" class="form-control" id="partner_order_number" value="{{ $order->partner_order_number ?? null}}" disabled>
                                    </div>
                                </div>
                                <div class="col-2 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">預定出貨日</span>
                                        </div>
                                        <input type="datetime" class="form-control" id="book_shipping_date" value="{{ $order->book_shipping_date ?? null}}" disabled>
                                    </div>
                                </div>
                                @endif
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">訂單狀態</span>
                                        </div>
                                        <select class="form-control" name="status" id="status">
                                            <option value="-1" {{ $order->status == -1 ? 'selected' : '' }}>後台取消訂單</option>
                                            <option value="0" {{ $order->status == 0 ? 'selected' : '' }}>訂單建立，尚未付款 {{ $order->deleted_at != '' ? '(訂單已刪除)' : '' }}</option>
                                            <option value="1" {{ $order->status == 1 ? 'selected' : '' }}>訂單已付款，待出貨</option>
                                            <option value="2" {{ $order->status == 2 ? 'selected' : '' }}>訂單集貨中</option>
                                            <option value="3" {{ $order->status == 3 ? 'selected' : '' }}>訂單已出貨</option>
                                            <option value="4" {{ $order->status == 4 ? 'selected' : '' }}>訂單已完成</option>
                                        </select>
                                        <input type="hidden" id="oldStatus" value="{{ $order->status }}">
                                    </div>
                                </div>
                                <div class="col-5 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">管理者備註</span>
                                        </div>
                                        <input type="text" class="form-control" id="admin_memo" name="admin_memo" value="{{ $order->admin_memo ?? '' }}">
                                    </div>
                                    @if ($errors->has('admin_memo'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('admin_memo') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                <div class="col-1 mb-2">
                                    <button type="button" class="btn btn-primary" id="orderModify">確認修改</button>
                                </div>
                                @endif
                                @if(in_array($menuCode.'RM', explode(',',Auth::user()->power)))
                                @if($order->status != 0)
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">發送退款E-mail</span>
                                        </div>
                                        <input type="number" class="form-control" id="refund" name="refund" min="0" placeholder="退款金額">
                                        <div class="input-group-prepend">
                                            <a href="javascript:refund({{ $order->id }})" class="btn btn-danger">送出</a>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @endif
                                </form>
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                <div class="col-12 mb-2">
                                    <span class="text-bold">商品資訊</span><br>
                                    @if(($order->status != -1 && $order->status <=2) && !empty($order->partner_order_number))
                                    @if(in_array($menuCode.'MQ', explode(',',Auth::user()->power)))
                                    <button type="button" class="badge badge-purple mr-2" id="itemQtyModify" onclick="itemQtyModify(this)">修改<br>數量</button>
                                    @endif
                                    @endif
                                    @if($order->status >=3)
                                    @if(in_array($menuCode.'AL', explode(',',Auth::user()->power)))
                                    <button type="button" class="badge badge-primary mr-2" id="allowanceModify" onclick="allowanceModify(this)">折讓<br>處理</button>
                                    @endif
                                    @endif
                                </div>
                                <div class="input-group col-12 mb-2 item_modify_info" style="display:none" id="allowanceOptions">
                                    <input type="text" class="mr-2 datepicker form-control form-control-sm col-2" id="allowanceDate" name="allowanceDate" placeholder="折讓日期，未填寫以今日">
                                    <span>運費:</span><input type="text" class="mr-2 form-control form-control-sm col-1" id="shippingFee" name="shippingFee" placeholder="折讓運費" value="">
                                    <span>跨境稅:</span><input type="text" class="mr-2 form-control form-control-sm col-1" id="parcelTax" name="parcelTax" placeholder="折讓跨境稅" value="">
                                    <input type="text" class="form-control form-control-sm col-3" id="allowanceMemo" name="allowanceMemo" placeholder="請填寫備註" >
                                </div>
                                <div class="col-12 mb-2">
                                    <table class="table mb-0 table-sm">
                                        <thead class="table-info">
                                            <th width="8%" class="text-left align-middle text-sm">
                                                <span class="allowancemodify" style="display:none">填寫金額</span>
                                                <span class="returnmodify" style="display:none">填寫數量</span>
                                            </th>
                                            <th width="12%" class="text-left align-middle text-sm">貨號</th>
                                            <th width="28%" class="text-left align-middle text-sm">品名</th>
                                            <th width="5%" class="text-center align-middle text-sm">單位</th>
                                            <th width="6%" class="text-right align-middle text-sm">重量(g)</th>
                                            <th width="6%" class="text-right align-middle text-sm">總重(g)</th>
                                            <th width="5%" class="text-right align-middle text-sm">單價</th>
                                            <th width="5%" class="text-right align-middle text-sm">每一個折價</th>
                                            <th width="5%" class="text-right align-middle text-sm">數量</th>
                                            <th width="5%" class="text-right align-middle text-sm">總價</th>
                                        </thead>
                                        <tbody>
                                            @foreach($order->items as $item)
                                            <tr>
                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if(in_array($menuCode.'MQ', explode(',',Auth::user()->power)))
                                                    @if($order->status != -1)
                                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        @if($item->is_del == 0)
                                                        <input type="number" min="0" class="form-control form-control-sm itemqtymodify" name="{{ $item->id }}" value="{{ $item->quantity }}" style="display:none">
                                                        @endif
                                                    @endif
                                                    @endif
                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                    @if($order->status != -1)
                                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        @if($item->is_del == 0)
                                                        <input type="number" min="0" class="form-control form-control-sm shipqtymodify" name="{{ $item->id }}" value="{{ $item->direct_shipment == 1 ? $item->quantity : 0 }}" style="display:none">
                                                        @endif
                                                    @endif
                                                    @endif
                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                    @if($order->status != -1)
                                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        @if($item->is_del == 0)
                                                        <input type="text" class="form-control form-control-sm purchasepricemodify" name="{{ $item->id }}" value="{{ !empty($item->syncedOrderItem) ? $item->syncedOrderItem['purchase_price'] : '' }}" style="display:none">
                                                        @endif
                                                    @endif
                                                    @endif
                                                    @if(in_array($menuCode.'AL', explode(',',Auth::user()->power)))
                                                    @if($order->status >= 3)
                                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        @if($item->is_del == 0)
                                                        <div class="input-group allowancemodify" style="display:none"><span>$</span><input type="number" class="form-control form-control-sm allowancemodify" name="{{ $item->id }}" value="" max="" min="0" style="display:none"></div>
                                                        @endif
                                                    @endif
                                                    @endif
                                                </td>
                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    {{ $item->digiwin_no }}
                                                </td>
                                                <td class="text-left align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    {{ $item->product_name }}
                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已下架)</span>@endif
                                                </td>
                                                <td class="text-center align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ $item->unit_name }}</td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->gross_weight) }}</td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->gross_weight * $item->quantity) }}</td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->price) }}</td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    {{ number_format($item->discount) }}
                                                </td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    {{ number_format($item->quantity) }}
                                                </td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">{{ number_format($item->price * $item->quantity) }}</td>
                                            </tr>
                                            @if(strstr($item->sku,'BOM'))
                                            @if(count($item->package)>0)
                                            <tr class="item_package_{{ $item->id }} m-0 p-0">
                                                <td colspan="11" class="text-sm p-0">
                                                    <table width="100%" class="table-sm m-0 p-0">
                                                        <thead>
                                                            <tr>
                                                                <th colspan="1" width="8%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                <th width="12%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                <th width="28%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                <th width="5%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                <th colspan="2" width="12%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($item->package as $packageItem)
                                                            <tr>
                                                                <td colspan="1" class="text-left align-middle text-sm" ></td>
                                                                <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</td>
                                                                <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</td>
                                                                <td class="text-center align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</td>
                                                                <td colspan="2" class="text-right align-middle text-sm"></td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">

                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">
                                                                    {{ number_format($packageItem['quantity']) }}
                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['total'] }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            @endif
                                        @endif
                                            @endforeach
                                            <tr>
                                                <td class="text-left align-middle" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">
                                                    <a href="javascript:" onclick="itemQtyModifySend({{ $order->id }}, this)" class="itemqtymodify badge badge-purple mr-2" style="display:none">確認修改</a>
                                                    <span class="itemqtymodify text-sm" style="display:none">填0為取消</span>
                                                    <a href="javascript:" onclick="shipQtyModifySend({{ $order->id }}, this)" class="shipqtymodify badge badge-success mr-2" style="display:none">確認修改</a>
                                                    <span class="shipqtymodify text-sm" style="display:none">填0為取消</span>
                                                    <a href="javascript:" onclick="purchasePriceModifySend({{ $order->id }}, this)" class="purchasepricemodify badge badge-warning mr-2" style="display:none">確認修改</a>
                                                    <a href="javascript:" onclick="returnModifySend({{ $order->id }}, this)" class="returnmodify badge badge-danger mr-2" style="display:none">確認修改</a>
                                                    <a href="javascript:" onclick="allowanceModifySend({{ $order->id }}, this)" class="allowancemodify badge badge-primary mr-2" style="display:none">確認折讓</a>
                                                </td>
                                                {{-- <td colspan="1" class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;"></td> --}}
                                                <td class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;"></td>
                                                <td class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">運費 {{ number_format($order->shipping_fee) }}　　跨境稅 {{ $order->parcel_tax ?? 0 }}　　折扣 {{ number_format($order->discount) }}</td>
                                                <td class="text-center align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;"></td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">總重</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalWeight) }}</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;"></td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">商品總計</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalQty) }}</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalPrice) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    @if($order->status >= 3)
                                    <span class="text-sm text-bold text-danger allowancemodify" style="display:none">注意！填寫折讓資料時，請填寫各項折讓總額即可，稅金由訂單稅別自行計算。</span>
                                    @elseif($order->status <3 && $order->status >= 0)
                                    <span class="text-sm text-bold text-danger">注意！訂單已被取消/刪除或尚未成團，無法修改數量，修改數量不可大於原始數量。</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </section>
    <form id="refundForm" action="{{ url('groupBuyingOrders/multiProcess') }}" method="POST">
        @csrf
        <input type="hidden" name="id[]" value="{{ $order->id }}">
        <input type="hidden" name="method" value="selected">
        <input type="hidden" name="cate" value="Refund">
        <input type="hidden" name="type" value="refund">
        <input type="hidden" name="filename" value="退款信件">
        <input type="hidden" name="model" value="groupbuyOrders">
    </form>
</div>
@endsection

@section('modal')

{{-- 訂單資訊 Modal --}}
<div id="orderInfoModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderInfoModalLabel">訂單資訊</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateForm" action="{{ route('gate.groupBuyingOrders.update', $order->id) }}" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PATCH">
            <div id="orderInfoModalData" class="modal-body">
            </div>
            <div class="modal-footer"><button type="sumbit" class="btn btn-primary">確認修改</button></div>
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

        $('input[name=invoice_sub_type]').change(function(){
            let value = $(this).val();
            if(value == 2){
                $('.invoice_sub_type2').show();
                $('.invoice_sub_type3').hide();
                $('.invoice_sub_type1').hide();
            }else if(value == 3){
                $('.invoice_sub_type3').show();
                $('.invoice_sub_type2').hide();
                $('.invoice_sub_type1').hide();
            }else{
                $('.invoice_sub_type1').show();
                $('.invoice_sub_type2').hide();
                $('.invoice_sub_type3').hide();
            }
        });

        $('select[name=carrier_type]').change(function(){
            let val = $(this).val();
            val == 0 ? $('input[name=carrier_num]').attr('placeholder','手機條碼為8碼，第一碼為斜線') : '';
            val == 1 ? $('input[name=carrier_num]').attr('placeholder','自然人憑證條碼為2位大寫字母+14位數字') : '';
            val == 2 ? $('input[name=carrier_num]').attr('placeholder','智富寶載具，免填') : '';
            $('input[name=carrier_num]').focus();
        });

        $('#Synchronize').click(function (e){
            $('#multiProcessForm').submit();
        });

        $('#showShippingNote').click(function(){
            $('#shippingNote').show();
        });

        $('input[name=type]').click(function(){
            let type = $(this).val();
            if(type == '自行挑選'){
                $('#shippingNote').hide();
                $('#shippingVendor').show();
            }else if(type=='廠商發貨'){
                $('#shippingNote').hide();
                $('#shippingVendor').hide();
            }else if(type=='移除物流'){
                $('#shippingVendor').hide();
                $('#shippingNote').hide();
            }else if(type=='依系統設定'){
                $('#shippingNote').hide();
                $('#shippingVendor').hide();
            }
        });

        $('.userInfo').click(function(){
            $('#orderInfoModalData').html('');
            let token = '{{ csrf_token() }}';
            let pwd = prompt("請輸入密碼，輸入錯誤超過三次帳號將會被鎖住。");
            if(pwd != null){
                let id = $(this).val();
                $.ajax({
                    type: "post",
                    url: 'getInfo',
                    data: {pwd:pwd, id: id, _token:token },
                    success: function(data) {
                        let message = data['message'];
                        let order = data['order'];
                        let count = data['count'];
                        let invoice_type = null;
                        let invoice_type2 = null;
                        let invoice_type3 = null;
                        let carrier_type = null;
                        let carrier_type0 = null;
                        let carrier_type1 = null;
                        let carrier_type2 = null;
                        let carrier_num = null;
                        let love_code = null;
                        let invoice_sub_type1 = null;
                        let invoice_sub_type2 = null;
                        let invoice_sub_type3 = null;
                        let carrier_type_name = null;
                        let invoice_title = null;
                        let invoice_number = null;
                        if(count >= 3){ //滾
                            alert(message);
                            location.href = 'logout';
                        }else if(message != null){
                            alert(message);
                        }else{
                            order['china_id_img1'] == null ? order['china_id_img1'] = '' : '';
                            order['china_id_img2'] == null ? order['china_id_img2'] = '' : '';
                            let chinaIdImg1 = order['china_id_img1'];
                            let chinaIdImg2 = order['china_id_img2'];
                            order['invoice_type'] == 2 ? invoice_type2 = 'selected' : '';
                            order['invoice_type'] == 3 ? invoice_type3 = 'selected' : '';
                            order['invoice_type'] == 2 ? invoice_title = 'disabled' : '';
                            order['invoice_type'] == 2 ? invoice_number = 'disabled' : '';
                            order['user_memo'] == '' || order['user_memo'] == null ? order['user_memo'] = '' : '';
                            order['admin_memo'] == '' || order['admin_memo'] == null ? order['admin_memo'] = '' : '';
                            order['user_name'] == '' || order['user_name'] == null ? order['user_name'] = '' : '';
                            order['user_tel'] == '' || order['user_tel'] == null ? order['user_tel'] = '' : '';
                            order['user_email'] == '' || order['user_email'] == null ? order['user_email'] = '' : '';
                            order['receiver_name'] == '' || order['receiver_name'] == null ? order['receiver_name'] = '' : '';
                            order['receiver_zip_code'] == '' || order['receiver_zip_code'] == null ? order['receiver_zip_code'] = '' : '';
                            order['receiver_email'] == '' || order['receiver_email'] == null ? order['receiver_email'] = '' : '';
                            order['receiver_tel'] == '' || order['receiver_tel'] == null ? order['receiver_tel'] = '' : '';
                            order['greeting_card'] == '' || order['greeting_card'] == null ? order['greeting_card'] = '' : '';
                            order['receiver_keyword'] == '' || order['receiver_keyword'] == null ? order['receiver_keyword'] = '' : '';
                            order['receiver_key_time'] == '' || order['receiver_key_time'] == null ? order['receiver_key_time'] = '' : '';
                            order['buyer_email'] == '' || order['buyer_email'] == null ? order['buyer_email'] = '' : '';
                            order['buyer_name'] == '' || order['buyer_name'] == null ? order['buyer_name'] = '' : '';
                            order['invoice_number'] == '' || order['invoice_number'] == null ? order['invoice_number'] = '' : '';
                            order['invoice_title'] == '' || order['invoice_title'] == null ? order['invoice_title'] = '' : '';
                            order['carrier_num'] == '' || order['carrier_num'] == null ? order['carrier_num'] = '' : '';
                            order['love_code'] == '' || order['love_code'] == null ? order['love_code'] = '' : '';
                            order['is_invoice_no'] == '' || order['is_invoice_no'] == null ? order['is_invoice_no'] = '' : '';
                            order['carrier_type'] == '' || order['carrier_type'] == null ? carrier_type = 'selected' : '';
                            order['carrier_type'] == '' || order['carrier_type'] == null ? carrier_num = 'disabled' : '';
                            order['carrier_type'] == '0' ? carrier_type0 = 'selected' : '';
                            order['carrier_type'] == '1' ? carrier_type1 = 'selected' : '';
                            order['carrier_type'] == '2' ? carrier_type2 = 'selected' : '';
                            order['carrier_type'] == '0' ? carrier_type_name = '手機條碼' : '';
                            order['carrier_type'] == '1' ? carrier_type_name = '自然人憑證條碼' : '';
                            order['carrier_type'] == '2' ? carrier_type_name = '智富寶載具' : '';
                            order['invoice_sub_type'] == 1 ? invoice_sub_type1 = 'selected' : '';
                            order['invoice_sub_type'] == 2 ? invoice_sub_type2 = 'selected' : '';
                            order['invoice_sub_type'] == 3 ? invoice_sub_type3 = 'selected' : '';
                            order['invoice_sub_type'] != 1 ? love_code = 'disabled' : '';
                            order['invoice_sub_type'] == 1 ? order['invoice_sub_type'] = '發票捐贈：慈善基金會' : '';
                            order['invoice_sub_type'] == 2 ? order['invoice_sub_type'] = '個人戶' : '';
                            order['invoice_sub_type'] == 3 ? order['invoice_sub_type'] = '公司' : '';
                            let html = '<div class="row align-items-center"><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">團購ID :</span></div><input type="text" class="form-control" value="'+order['group_buying_id']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">收件人資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收件人</span></div><input type="text" class=" form-control" name="receiver_name" value="'+order['receiver_name']+'"></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">電話</span></div><input type="text" class=" form-control" name="receiver_tel" value="'+order['receiver_tel']+'"></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">E-Mail</span></div><input type="text" class=" form-control" name="receiver_email" value="'+order['receiver_email']+'"></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-plane"></i>班機號碼／<i class="fas fa-hotel"></i>旅店名稱</span></div><input type="text" class="form-control" name="receiver_keyword" value="'+order['receiver_keyword']+'"></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i>提貨時間</span></div><input type="datetime" class="form-control" id="receiver_key_time" name="receiver_key_time" value="'+order['receiver_key_time']+'"></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">郵遞區號</span></div><input type="text" class=" form-control" name="receiver_zip_code" value="'+order['receiver_zip_code']+'"></div></div><div class="col-5 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">地址</span></div><input type="text" class=" form-control" name="receiver_address" value="'+order['receiver_address']+'"></div></div><div class="col-5 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">賀卡留言</span></div><input type="text" class=" form-control" name="greeting_card" value="'+order['greeting_card']+'"></div></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">訂單備註</span></div><input type="text" class=" form-control" name="user_memo" value="'+order['user_memo']+'"></div></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">管理者備註</span></div><input type="text" class=" form-control" name="admin_memo" value="'+order['admin_memo']+'"></div></div><div class="col-12 offset-3 mb-2" id="data_datepicker" style="display:none"></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">發票資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票號碼</span></div><input type="text" class=" form-control" name="is_invoice_no" value="'+order['is_invoice_no']+'" ></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">類別</span></div><select class="form-control" id="invoice_sub_type" name="invoice_sub_type"><option value="1" '+invoice_sub_type1+'>發票捐贈:慈善基金會</option><option value="2" '+invoice_sub_type2+'>個人戶</option><option value="3" '+invoice_sub_type3+'>公司戶</option></select></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">愛心碼</span></div><input type="text" class=" form-control" id="love_code" name="love_code" value="'+order['love_code']+'" '+love_code+'></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">載具</span></div><select class="form-control" id="carrier_type" name="carrier_type"><option value="" '+carrier_type+' >不使用載具</option><option value="0" '+carrier_type0+'>手機條碼</option><option value="1" '+carrier_type1+'>自然人憑證條碼</option><option value="2" '+carrier_type2+'>智富寶載具</option></select></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">手機條碼/自然人憑證條碼</span></div><input type="text" class=" form-control" id="carrier_num" name="carrier_num" value="'+order['carrier_num']+'" '+carrier_num+'></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票聯式</span></div><select class="form-control" id="invoice_type" name="invoice_type"><option value="2" '+invoice_type2+'>二聯式</option><option value="3" '+invoice_type3+'>三聯式</option></select></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">統編</span></div><input type="text" class=" form-control" id="invoice_number" name="invoice_number" value="'+order['invoice_number']+'" '+invoice_number+'></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">抬頭</span></div><input type="text" class=" form-control" id="invoice_title" name="invoice_title" value="'+order['invoice_title']+'" '+invoice_title+'></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收受人真實姓名</span></div><input type="text" class=" form-control" id="buyer_name" name="buyer_name" value="'+order['buyer_name']+'" ></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票收受人E-Mail</span></div><input type="text" class=" form-control" id="buyer_email" name="buyer_email" value="'+order['buyer_email']+'" ></div></div>';
                            chinaIdImg1 != '' ? html += '<div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">中國身分證照片</span></div><div class="col-12 mb-2"><div class="row"><div class="col-6"><img class="col-12" src="'+chinaIdImg1+'"></div><div class="col-6"><img class="col-12" src="'+chinaIdImg2+'"></div></div></div>' : '';
                            html += '</div>';
                            $('#orderInfoModalData').html(html);
                            $('#invoice_sub_type').change(function(){
                                if($(this).val() == 1){
                                    $('#love_code').attr('disabled',false);
                                    $('#invoice_type').val(2);
                                    $('#invoice_title').attr('disabled',true);
                                    $('#invoice_number').attr('disabled',true);
                                    $('#invoice_number').val('');
                                    $('#invoice_title').val('');
                                }else if($(this).val() == 2){
                                    $('#love_code').attr('disabled',true);
                                    $('#invoice_type').val(2);
                                    $('#invoice_title').attr('disabled',true);
                                    $('#invoice_number').attr('disabled',true);
                                    $('#invoice_number').val('');
                                    $('#invoice_title').val('');
                                }else if($(this).val() == 3){
                                    $('#love_code').attr('disabled',true);
                                    $('#invoice_type').val(3);
                                    $('#invoice_sub_type').val(3);
                                    $('#invoice_title').attr('disabled',false);
                                    $('#invoice_number').attr('disabled',false);
                                }
                            });
                            $('#invoice_type').change(function(){
                                if($(this).val() == 2){
                                    $('#love_code').attr('disabled',true);
                                    $('#invoice_sub_type').val(2);
                                    $('#invoice_title').attr('disabled',true);
                                    $('#invoice_number').attr('disabled',true);
                                    $('#invoice_number').val('');
                                    $('#invoice_title').val('');
                                }else if($(this).val() == 3){
                                    $('#love_code').attr('disabled',true);
                                    $('#invoice_sub_type').val(3);
                                    $('#invoice_title').attr('disabled',false);
                                    $('#invoice_number').attr('disabled',false);
                                }
                            });
                            $('#carrier_type').change(function(){
                                if($(this).val() != ''){
                                    $('#carrier_num').attr('disabled',false);
                                }else{
                                    $('#carrier_num').attr('disabled',true);
                                    $('#carrier_num').val('');
                                }
                            });
                            $('.datetimepicker').datetimepicker({
                                timeFormat: "HH:mm:ss",
                                dateFormat: "yy-mm-dd",
                            });
                            $('#receiver_key_time').click(function(){
                                $('#data_datepicker').toggle();
                                $('#data_datetimepicker').toggle();
                            });

                            $('#data_datepicker').datetimepicker({
                                timeFormat: "HH:mm:ss",
                                dateFormat: 'yy-mm-dd',
                                onSelect: function (date) {
                                    $('input[name=receiver_key_time]').val(date);
                                    // $('#data_datepicker').toggle();
                                }
                            });
                            $('#orderInfoModal').modal('show');
                        }
                    }
                });
            }
        });

        $('#orderModify').click(function(){
            let form = $('#updateForm');
            let status = $('#status').val();
            let oldStatus = $('#oldStatus').val();
            let adminMemo = $('#admin_memo').val();
            let partnerOrderNumber = $('#partner_order_number').val();
            form.append($('<input type="hidden" class="formappend" name="status" value="'+status+'">'));
            form.append($('<input type="hidden" class="formappend" name="admin_memo" value="'+adminMemo+'">'));
            if(oldStatus != -1 && status == -1 && partnerOrderNumber != null){
                if(confirm("注意!! 此訂單已成團，取消訂單將會連動取消iCarry訂單。\n請務必記得通知客戶，並作退款。")){
                    form.submit();
                }
            }else{
                form.submit();
            }
        });

        $('.btn-cancel').click(function (e) {
            let id = $(this).val();
            let form = $('#cancelForm');
            if(confirm('請確認是否要取消這筆出貨單?')){
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
        });

    })(jQuery);

    function addShippingInfo(who){
        let t = 0;
        let token = '{{ csrf_token() }}';
        let orderId = {{ $order->id }};
        who == 'vendor' ? classs = 'vendor_shipping' : classs = 'order_shipping';
        who == 'vendor' ? $('#'+classs).find('.'+classs).length > 0 ? t = $('#'+classs).find('.'+classs).length / 5 : '' : $('#'+classs).find('.'+classs).length > 0 ? t = $('#'+classs).find('.'+classs).length / 4 : '';
        $.ajax({
            type: "post",
            url: 'getshippingvendors',
            data: { _token: token },
            success: function(data) {
                var options = '<option value="">請選擇快遞物流廠商</option>';
                now = getNowTime();
                for(i=0;i<data.length;i++){
                    options = options + '<option value="'+data[i]['name']+'">'+data[i]['name']+'</option>';
                }
                if(who == 'vendor'){
                    $.ajax({
                        type: "post",
                        url: 'getvendors',
                        data: { _token: token },
                        success: function(vendor) {
                            select = '<option value="">請選擇商家</option>';
                            for(j=0;j<vendor.length;j++){
                                select = select + '<option value="'+vendor[j]['id']+'">'+vendor[j]['name']+'</option>';
                            }
                            html = '<div class="mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">'+now+'</span><input type="hidden" class="'+classs+'" name="'+classs+'['+t+'][id]" value=""><input type="hidden" class="'+classs+'" name="'+classs+'['+t+'][order_id]" value="'+orderId+'"></div><div class="input-group-prepend"><span class="input-group-text">商家</span></div><select class="col-3 form-control selectvendor select2-primary '+classs+'" data-dropdown-css-class="select2-primary" name="vendor_shipping['+t+'][vendor_id]" required>'+select+'</select><div class="input-group-prepend"><span class="input-group-text">快遞公司</span></div><select name="'+classs+'['+t+'][express_way]" class="'+classs+' form-control" required>'+options+'</select><div class="input-group-prepend"><span class="input-group-text">快遞單號</span></div><input type="text" class="'+classs+' form-control" name="'+classs+'['+t+'][express_no]" placeholder="輸入快遞單號" required><a href="javascript:" class="btn btn-secondary">無法查詢</a><a href="javascript:" class="btn btn-danger" onclick="removeShippingInfo(this)"><i class="far fa-trash-alt"></i></a></div></div>';
                            $('#'+classs).append(html);
                            $('.selectvendor').select2({
                                theme: 'bootstrap4'
                            });
                        }
                    });
                }else{
                    html = '<div class="mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">'+now+'</span><input type="hidden" class="'+classs+'" name="'+classs+'['+t+'][id]" value=""><input type="hidden" class="'+classs+'" name="'+classs+'['+t+'][order_id]" value="'+orderId+'"></div><div class="input-group-prepend"><span class="input-group-text">快遞公司</span></div><select name="'+classs+'['+t+'][express_way]" class="'+classs+' form-control" required>'+options+'</select><div class="input-group-prepend"><span class="input-group-text">快遞單號</span></div><input type="text" class="'+classs+' form-control" name="'+classs+'['+t+'][express_no]" placeholder="輸入快遞單號" required><a href="javascript:" class="btn btn-secondary">無法查詢</a><a href="javascript:" class="btn btn-danger" onclick="removeShippingInfo(this)"><i class="far fa-trash-alt"></i></a></div></div>';
                    $('#'+classs).append(html);
                }
            }
        });
    }

    function removeShippingInfo(e){
        if(confirm('請確認是否要移除此快遞資訊')){
            $(e).parent().parent().remove();
        }
    }

    function delOrderShipping(who, id, e){
        if(confirm('請確認是否要移除此快遞資訊')){
            who == 'vendorshipping' ? url = '../ordervendorshippings/'+id : url = '../ordershippings/'+id;
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: 'post',
                url: url,
                data: { _method: 'DELETE', _token: token },
                success: function(data) {
                    if(data=='success'){
                        $(e).parent().parent().remove();
                    }
                }
            });
        }
    }

    function itemQtyModify(e){
        html = '';
        orderStatus = '{{ $order->status }}';
        if(confirm('請先確認本頁面其他資料更新已儲存，再按下確定。')){
            $('.returnmodify').hide();
            $('.shipqtymodify').hide();
            $('.purchasepricemodify').hide();
            $('#allowanceOptions').hide();
            orderStatus != -1 ? $('.itemqtymodify').toggle('display') : '';
            orderStatus != -1 ? $('#shippingFeeModify').toggle('display') : '';
            if($(e).html() == '修改<br>數量'){
                $(e).html('取消<br>修改');
                $('#shipQtyModify').attr('disabled',true);
                $('#returnModify').attr('disabled',true);
                $('#purchasePriceModify').attr('disabled',true);
                $('#allowanceModify').attr('disabled',true);
            }else{
                $(e).html('修改<br>數量');
                $('#shipQtyModify').attr('disabled',false);
                $('#returnModify').attr('disabled',false);
                $('#purchasePriceModify').attr('disabled',false);
                $('#allowanceModify').attr('disabled',false);
            }
        }
    }

    function shipQtyModify(e){
        html = '';
        orderStatus = '{{ $order->status }}';
        if(confirm('請先確認本頁面其他資料更新已儲存，再按下確定。')){
            $('.returnmodify').hide();
            $('.itemqtymodify').hide();
            $('.purchasepricemodify').hide();
            $('#shippingFeeModify').hide();
            $('#allowanceOptions').hide();
            orderStatus != -1 ? $('.shipqtymodify').toggle('display') : '';
            if($(e).html() == '修改<br>直寄'){
                $(e).html('取消<br>直寄');
                $('#itemQtyModify').attr('disabled',true);
                $('#returnModify').attr('disabled',true);
                $('#purchasePriceModify').attr('disabled',true);
                $('#allowanceModify').attr('disabled',true);
            }else{
                $(e).html('修改<br>直寄');
                $('#itemQtyModify').attr('disabled',false);
                $('#returnModify').attr('disabled',false);
                $('#purchasePriceModify').attr('disabled',false);
                $('#allowanceModify').attr('disabled',false);
            }
        }
    }

    function purchasePriceModify(e){
        html = '';
        orderStatus = '{{ $order->status }}';
        if(confirm('請先確認本頁面其他資料更新已儲存，再按下確定。')){
            $('#shippingFeeModify').hide();
            $('#allowanceOptions').hide();
            $('.returnmodify').hide();
            $('.itemqtymodify').hide();
            $('.shipqtymodify').hide()
            orderStatus != -1 ? $('.purchasepricemodify').toggle('display') : '';
            if($(e).html() == '修改<br>採購價'){
                $(e).html('取消<br>修改');
                $('#itemQtyModify').attr('disabled',true);
                $('#returnModify').attr('disabled',true);
                $('#shipQtyModify').attr('disabled',true);
                $('#allowanceModify').attr('disabled',true);
            }else{
                $(e).html('修改<br>採購價');
                $('#itemQtyModify').attr('disabled',false);
                $('#returnModify').attr('disabled',false);
                $('#shipQtyModify').attr('disabled',false);
                $('#allowanceModify').attr('disabled',false);
            }
        }
    }

    function returnModify(e){
        html = '';
        orderStatus = '{{ $order->status }}';
        if(confirm('請先確認本頁面其他資料更新已儲存，再按下確定。')){
            $('#shippingFeeModify').toggle();
            $('.itemqtymodify').hide();
            $('.shipqtymodify').hide()
            $('#allowanceOptions').hide();
            orderStatus >= 3 ? $('.returnmodify').toggle('display') : '';
            if($(e).html() == '退貨<br>處理'){
                $(e).html('取消<br>退貨');
                $('#itemQtyModify').attr('disabled',true);
                $('#purchasePriceModify').attr('disabled',true);
                $('#shipQtyModify').attr('disabled',true);
                $('#allowanceModify').attr('disabled',true);
            }else{
                $(e).html('退貨<br>處理');
                $('#itemQtyModify').attr('disabled',false);
                $('#purchasePriceModify').attr('disabled',false);
                $('#shipQtyModify').attr('disabled',false);
                $('#allowanceModify').attr('disabled',false);
            }
        }
    }

    function allowanceModify(e){
        html = '';
        orderStatus = '{{ $order->status }}';
        if(confirm('請先確認本頁面其他資料更新已儲存，再按下確定。')){
            $('#allowanceOptions').toggle();
            $('.itemqtymodify').hide();
            $('.shipqtymodify').hide()
            $('#shippingFeeModify').hide();
            orderStatus >= 3 ? $('.allowancemodify').toggle('display') : '';
            if($(e).html() == '折讓<br>處理'){
                $(e).html('取消<br>折讓');
                $('#itemQtyModify').attr('disabled',true);
                $('#purchasePriceModify').attr('disabled',true);
                $('#shipQtyModify').attr('disabled',true);
                $('#returnModify').attr('disabled',true);
            }else{
                $(e).html('折讓<br>處理');
                $('#itemQtyModify').attr('disabled',false);
                $('#purchasePriceModify').attr('disabled',false);
                $('#shipQtyModify').attr('disabled',false);
                $('#returnModify').attr('disabled',false);
            }
        }
    }

    function itemQtyModifySend(id, e){
        if(confirm('請確認訂單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            form.append($('<input type="hidden" name="itemQty" value="1">'));
            let itemIds = $('.itemqtymodify').serializeArray().map( item => item.name );
            let itemQty = $('.itemqtymodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][qty]" value="'+itemQty[i]+'">');
                form.append(tmp1);
                form.append(tmp2);
            }
            form.submit();
        }
    }

    function allowanceModifySend(id, e){
        if(confirm('請確認訂單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            form.append($('<input type="hidden" name="allowance" value="1">'));
            let allowanceDate = $('#allowanceDate').val();
            let shippingFee = $('#shippingFee').val();
            let parcelTax = $('#parcelTax').val();
            let allowanceMemo = $('#allowanceMemo').val();
            if(allowanceMemo.length > 0){
                form.append($('<input type="hidden" name="allowanceMemo" value="'+allowanceMemo+'">'));
            }else{
                alert('請填寫折讓原因');
                return;
            }
            form.append($('<input type="hidden" name="allowanceDate" value="'+allowanceDate+'">'));
            form.append($('<input type="hidden" name="shippingFee" value="'+shippingFee+'">'));
            form.append($('<input type="hidden" name="parcelTax" value="'+parcelTax+'">'));
            let itemIds = $('.allowancemodify').serializeArray().map( item => item.name );
            let itemQty = $('.allowancemodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][price]" value="'+itemQty[i]+'">');
                form.append(tmp1);
                form.append(tmp2);
            }
            form.submit();
        }
    }

    function returnModifySend(id, e){
        if(confirm('請確認訂單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            let returnDate = $('#returnDate').val();
            form.append($('<input type="hidden" name="returnQty" value="1">'));
            form.append($('<input type="hidden" name="returnDate" value="'+returnDate+'">'));
            let chkShipping = $('input[name=shippingFeeModify]:checked').val();
            if(chkShipping == 'on'){
                form.append($('<input type="hidden" name="shippingFeeModify" value="1">'));
            }else{
                form.append($('<input type="hidden" name="shippingFeeModify" value="0">'));
            }
            let chkZero = $('input[name=zeroFeeModify]:checked').val();
            let returnMemo = $('#returnMemo').val();
            if(returnMemo.length > 0){
                form.append($('<input type="hidden" name="returnMemo" value="'+returnMemo+'">'));
            }else{
                alert('請填寫銷退原因');
                return;
            }
            if(chkZero == 'on'){
                form.append($('<input type="hidden" name="zeroFeeModify" value="1">'));
            }else{
                form.append($('<input type="hidden" name="zeroFeeModify" value="0">'));
            }
            let itemIds = $('.returnmodify').serializeArray().map( item => item.name );
            let itemQty = $('.returnmodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][qty]" value="'+itemQty[i]+'">');
                form.append(tmp1);
                form.append(tmp2);
            }
            form.submit();
        }
    }

    function shipQtyModifySend(id, e){
        if(confirm('請確認訂單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            form.append($('<input type="hidden" name="directShip" value="1">'));
            let itemIds = $('.shipqtymodify').serializeArray().map( item => item.name );
            let itemQty = $('.shipqtymodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][qty]" value="'+itemQty[i]+'">');
                form.append(tmp1);
                form.append(tmp2);
            }
            form.submit();
        }
    }

    function purchasePriceModifySend(id, e){
        if(confirm('請確認訂單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            form.append($('<input type="hidden" name="purchasePrice" value="1">'));
            let itemIds = $('.purchasepricemodify').serializeArray().map( item => item.name );
            let itemPrice = $('.purchasepricemodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                let tmp1 = $('<input type="hidden" class="formappend" name="items['+i+'][id]" value="'+itemIds[i]+'">');
                let tmp2 = $('<input type="hidden" class="formappend" name="items['+i+'][price]" value="'+itemPrice[i]+'">');
                form.append(tmp1);
                form.append(tmp2);
            }
            form.submit();
        }
    }

    function pickupShipping(itemId){
        $('#shippingRecord').html('');
        let token = '{{ csrf_token() }}';
        $.ajax({
            type: "post",
            url: 'getlog',
            data: { order_item_id: itemId, column_name: 'shipping_memo' , _token: token },
            success: function(data) {
                if(data.length > 0){
                    $('#myShippingRecord').removeClass('d-none');
                    for(let i=0; i<data.length; i++){
                        let dateTime = data[i]['created_at'];
                        let name = data[i]['name'];
                        let log = data[i]['log'];
                        let col_name = data[i]['column_name'];
                        let record = '<tr class="record"><td class="align-middle">'+(data.length - i)+'</td><td class="align-middle">'+dateTime+'</td><td class="align-middle">'+name+'</td><td class="text-left align-middle">'+col_name+'</td><td class="align-middle">'+log+'</td></tr>';
                        $('#shippingRecord').append(record);
                    }
                }else{
                    $('#myShippingRecord').addClass('d-none');
                }
            }
        });
        $('#shippingModel').modal('show');
        let cate = 'pickupShipping';
        let form = $('#pickupShippingForm');
        let condition = null;
        if(itemId){ //訂單商品id
            let multiProcess = 'selected';
            form.append($('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="'+cate+'">'));
            form.append($('<input type="hidden" class="formappend" name="order_item_id">').val(itemId));
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
        }else{ //訂單多重
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
            }else{
                return;
            }
            form.append($('<input type="hidden" class="formappend" name="method" value="'+multiProcess+'">'));
            form.append($('<input type="hidden" class="formappend" name="cate" value="'+cate+'">'));
            form.append( $('<input type="hidden" class="formappend" name="model" value="groupbuyOrders">') );
        }
    }

    function notPurchase(id,e){
        let token = '{{ csrf_token() }}';
        let text = $(e).parent().text().replace(/\s/g, '');
        $.ajax({
            type: "post",
            url: 'markNotPurchase',
            data: { order_item_id: id, _token: token },
            success: function(data) {
                // console.log(data);
                // return;
                if(data == 0){
                    $(e).removeClass('active')
                    alert(text+'已取消不採購標記')
                }else if(data == 1){
                    $(e).addClass('active')
                    alert(text+'已增加不採購標記')
                }
            }
        });
    }

    function purchaseCancel(id)
    {
        if(confirm('注意!! 移除採購註記，並不會移除採購單內的商品資料，請自行手動修改採購單內商品資料。請確認是否移除採購註記??')){
            let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'purchaseCancel',
                data: { id: id, _token: token },
                success: function(data) {
                    console.log(data);
                    if(data){
                        $('.syncedOrderItem_'+id).popover('hide');
                        $('.syncedOrderItem_'+id).remove();
                    }
                }
            });
        }
    }

    function refund(id){
        if(confirm('請確認是否發送退款信件?')){
            if($('input[name=refund]').val() > 0){
                let refund = $('input[name=refund]').val();
                let form = $('#refundForm');
                form.append($('<input type="hidden" class="formappend" name="refundMail" value="1">'))
                form.append($('<input type="hidden" class="formappend" name="refund" value="'+refund+'">'))
                form.submit();
            }else{
                alert('金額必須大於0');
            }
        }
    }
</script>
@endsection
