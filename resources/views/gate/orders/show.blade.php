@extends('gate.layouts.master')

@section('title', '訂單管理')

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
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">訂單管理</a></li>
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
                                        <input type="datetime" class="form-control datetimepicker" id="pay_time" name="pay_time" value="{{ $order->pay_time ?? null}}">
                                    </div>
                                </div>
                                <div class="col-3 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">同步鼎新時間</span>
                                        </div>
                                        <input type="datetime" class="form-control" value="{{ $order->syncDate->created_at ?? '' }}" disabled>
                                    </div>
                                </div>
                                @if($order->status <= 2)
                                @if(in_array($menuCode.'SY', explode(',',Auth::user()->power)))
                                @if($order->status != 0)
                                <div class="col-1 mb-2">
                                    <button class="btn btn-block btn-primary multiProcess mr-2" id="Synchronize" value="Synchronize">同步至鼎新</button>
                                </div>
                                @endif
                                @endif
                                @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                <div class="col-1 mb-2">
                                    <a href="javascript:" class="btn btn-block btn-danger mr-2" onclick="pickupShipping(null,{{ $order->id }})">挑選物流</a>
                                </div>
                                @endif
                                @endif
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                <div class="col-12 mb-2">
                                    <span class="text-bold">出貨資訊</span>
                                </div>
                                @if(count($order->shippings) > 0)
                                <div id="order_shipping" class="col-12">
                                    @if(isset($order->shippings))
                                    @foreach($order->shippings as $shipping)
                                    <div class="mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">{{ $shipping->created_at }}</span>
                                                <input class="order_shipping" type="hidden" name="order_shipping[{{ $loop->iteration - 1 }}][id]" value="{{ $shipping->id }}"  disabled>
                                                <input class="order_shipping" type="hidden" name="order_shipping[{{ $loop->iteration - 1 }}][order_id]" value="{{ $order->id }}"  disabled>
                                            </div>
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">快遞公司</span>
                                            </div>
                                            <input type="text" class="vendor_shipping form-control" name="order_shipping[{{ $loop->iteration - 1 }}][express_way]" value="{{ $shipping->express_way }}"  disabled>
                                            <select name="order_shipping[{{ $loop->iteration - 1 }}][express_way]" class="order_shipping form-control express_way"  disabled>
                                                <option value="">請選擇快遞公司</option>
                                                @foreach($shippingVendors as $shippingVendor)
                                                <option value="{{ $shippingVendor->name }}" {{ $shipping->express_way == $shippingVendor->name ? 'selected' : '' }}>{{ $shippingVendor->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">快遞單號</span>
                                            </div>
                                            <input type="text" class="order_shipping form-control" name="order_shipping[{{ $loop->iteration - 1 }}][express_no]" value="{{ $shipping->express_no }}" disabled>
                                            <div class="input-group-prepend">
                                            @foreach($shippingVendors as $shippingVendor)
                                            @if($shippingVendor->name == $shipping->express_way)
                                            @if($shippingVendor->api_url == '無法查詢')
                                            <a href="javascript:" class="btn btn-secondary">無法查詢</a>
                                            @else
                                            <a href="{{ $shippingVendor->api_url }}{{ $shipping->express_no }}" target="_blank" class="btn btn-primary"><span>包裹查詢</span></a>
                                            @endif
                                            @endif
                                            @endforeach
                                            {{-- @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                            <a href="javascript:" class="btn btn-danger" onclick="delOrderShipping('ordershipping', {{ $shipping->id }}, this)"><i class="far fa-trash-alt"></i></a>
                                            @endif --}}
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif
                                </div>
                                @endif
                                <div class="col-12 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">物流單號</span>
                                        </div>
                                        <input type="text" class="col-5 form-control mr-2" id="shipping_number" name="shipping_number" value="{{ $order->shipping_number }}" {{ $order->status == 1 || $order->status == 2 ? '' : 'disabled' }}>
                                    </div>
                                </div>
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
                                @if($order->status != 0 && $order->create_type != 'groupbuy')
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
                                    @if(!in_array($order->digiwin_payment_id,['AC0001','AC000101','AC000102','AC0002','AC000201','AC000202']))
                                        @if($order->status != -1 && $order->status <=2)
                                            @if($order->create_type != 'groupbuy')
                                                @if(in_array($menuCode.'MQ', explode(',',Auth::user()->power)))
                                                <button type="button" class="badge badge-purple mr-2" id="itemQtyModify" onclick="itemQtyModify(this)">修改<br>數量</button>
                                                @endif
                                                @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                <button type="button" class="badge badge-success mr-2" id="shipQtyModify" onclick="shipQtyModify(this)">修改<br>直寄</button>
                                                @endif
                                            @endif
                                            @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                            <button type="button" class="badge badge-warning mr-2" id="purchasePriceModify" onclick="purchasePriceModify(this)">修改<br>採購價</button>
                                            @endif
                                            @if(in_array($order->status,[1,2]) && !empty($order->syncedOrder) && $order->chkDirectShipment > 0)
                                            <button type="button" class="badge badge-primary mr-2" id="sell">手動<br>銷貨</button>
                                            @endif
                                        @endif
                                    @if($order->status >=3 && $order->create_type != 'groupbuy')
                                    @if(in_array($menuCode.'RT', explode(',',Auth::user()->power)))
                                    <button type="button" class="badge badge-danger mr-2" id="returnModify" onclick="returnModify(this)">退貨<br>處理</button>
                                    @endif
                                    @if(in_array($menuCode.'AL', explode(',',Auth::user()->power)))
                                    <button type="button" class="badge badge-primary mr-2" id="allowanceModify" onclick="allowanceModify(this)">折讓<br>處理</button>
                                    @endif
                                    @endif
                                    @endif
                                </div>
                                <div class="col-12 mb-2 item_modify_info" style="display:none" id="shippingFeeModify">
                                    <input type="checkbox" class="mr-2" name="shippingFeeModify" {{ $order->status >= 1 ? 'checked' : ''}}><span class="text-sm text-bold text-primary">同時調整運費、行郵稅、折扣</span>
                                    <input type="checkbox" class="mr-2" name="usingPoint"><span class="text-sm text-bold text-primary">使用退還購物金方式</span>
                                    @if($order->status >=3)
                                    <input type="checkbox" class="mr-2" name="zeroFeeModify" {{ $order->shipping_method == 1 ? 'checked' : '' }}><span class="text-sm text-bold text-primary">將銷退單金額設為0元</span>
                                    <input type="text" class="col-3 form-control form-control-sm mb-1 datepicker" id="returnDate" name="returnDate" placeholder="退貨日期，未填寫以今日">
                                    @endif
                                    <input type="text" class="col-3 form-control form-control-sm" id="returnMemo" name="returnMemo" placeholder="請填寫備註" >
                                    <input type="text" class="col-3 form-control form-control-sm" id="returnShippingFee" name="returnShippingFee" placeholder="請先取消勾選同時調整運費選項，再填寫退還運費金額" >
                                </div>
                                <div class="input-group col-12 mb-2 item_modify_info" style="display:none" id="allowanceOptions">
                                    <input type="text" class="mr-2 datepicker" id="allowanceDate" name="allowanceDate" placeholder="折讓日期，未填寫以今日">
                                    <span>運費:</span><input type="text" class="mr-2" id="shippingFee" name="shippingFee" placeholder="折讓運費" value="{{ $order->shipping_fee }}">
                                    <span>跨境稅:</span><input type="text" class="mr-2" id="parcelTax" name="parcelTax" placeholder="折讓跨境稅" value="{{ $order->parcel_tax }}">
                                    <input type="text" class="col-3 " id="allowanceMemo" name="allowanceMemo" placeholder="請填寫備註" >
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
                                            <th width="5%" class="text-right align-middle text-sm">數量</th>
                                            <th width="5%" class="text-right align-middle text-sm">出貨</th>
                                            <th width="10%" class="text-right align-middle text-sm">物流</th>
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
                                                    @if(in_array($menuCode.'RT', explode(',',Auth::user()->power)))
                                                    @if($order->status >= 3)
                                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][id]" value="{{ $item->id }}">
                                                        @if($item->is_del == 0)
                                                        <input type="number" class="form-control form-control-sm returnmodify" name="{{ $item->id }}" value="{{ $item->quantity - $item->return_quantity }}" max="{{ $item->quantity - $item->return_quantity }}" min="0" style="display:none">
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
                                                    @if(!empty($item->syncedOrderItem['purchase_no']))
                                                    <span data-toggle="popover" class="text-primary syncedOrderItem_{{ $item->syncedOrderItem['id'] }}" data-content="
                                                        <small>
                                                            採購單號：{{ $item->syncedOrderItem['purchase_no'] }}<br>
                                                            採購日期：{{ $item->syncedOrderItem['purchase_date'] }}<br>
                                                            @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                            <button class='btn btn-outline-secondary btn-xs' onclick='purchaseCancel({{ $item->syncedOrderItem['id'] }})'>移除</button>
                                                            @endif
                                                        </small>
                                                        "><i class="fas fa-store-alt" title="採購資料"></i></span>
                                                    @else
                                                    <i class="store-slash fas fa-store-alt-slash {{ $item->not_purchase == 1 ? 'active' : '' }}" {{ in_array($menuCode.'MK', explode(',',Auth::user()->power)) ? 'onclick=notPurchase('.$item->id.',this)' : '' }}></i>
                                                    @endif
                                                    @if($item->direct_shipment == 1)
                                                    <span class="text-primary "><i class="fas fa-truck" title="廠商直寄"></i></span>
                                                    @endif
                                                    @if(!empty($item->vendor_shipping_no))
                                                    @if($item->is_del == 0)
                                                    <span data-toggle="popover" class="text-danger" data-content="商家出貨單號：{{ $item->vendor_shipping_no }}"><i class="fas fa-tags" title="商家出貨單"></i></span>
                                                    @endif
                                                    @endif
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
                                                    {{ number_format($item->quantity) }}
                                                </td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if(count($item->sells) > 0)
                                                    <span class="text-primary" data-toggle="popover" title="銷貨資訊" data-placement="top" id="item_sell_{{ $item->id }}" data-content="
                                                        @foreach($item->sells as $sell)
                                                            銷貨單號：{{ $sell->sell_no }} 數量：{{ $sell->sell_quantity }}<br>
                                                        @endforeach
                                                        ">{{ number_format($item->sell_quantity) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right align-middle text-sm {{ $item->is_del == 1 || $item->quantity == 0 ? 'double-del-line' : '' }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                                    @if($order->status <=2)
                                                    @if($item->shipping_memo == null)
                                                    <a href="javascript:" onclick="pickupShipping({{ $item->id }})">挑選</a>
                                                    @else
                                                    <a href="javascript:" onclick="pickupShipping({{ $item->id }})">{{ $item->shipping_memo }}</a>
                                                    @endif
                                                    @else
                                                    {{ $item->shipping_memo }}
                                                    @endif
                                                    @endif
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
                                                                <th colspan="2" width="12%" class="text-right align-middle text-sm" style="border: none; outline: none">iCarry單價</th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">拆分<br>單價</th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">數量</th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">出貨</th>
                                                                <th width="10%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">總價</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($item->split as $packageItem)
                                                            <tr>
                                                                <td colspan="1" class="text-left align-middle text-sm" ></td>
                                                                <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</td>
                                                                <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</td>
                                                                <td class="text-center align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</td>
                                                                <td colspan="2" class="text-right align-middle text-sm">{{ $packageItem['origin_price'] }}</td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">{{ $packageItem['price'] }}</td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">
                                                                    {{ number_format($packageItem['quantity']) }}
                                                                </td>
                                                                <td class="text-right align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}">
                                                                    @if(!empty($packageItem['sell_no']))
                                                                    <span class="text-primary" data-toggle="popover" title="銷貨資訊" data-placement="top" id="item_sell_{{ $item->id }}" data-content="
                                                                            銷貨單號：{{ $packageItem['sell_no'] }}
                                                                        ">{{ number_format($packageItem['quantity']) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-left align-middle text-sm {{ $packageItem['is_del'] == 1 || $packageItem['quantity'] == 0 ? 'double-del-line' : '' }}"></td>
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
                                                <td class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">使用購物金 {{ number_format($order->spend_point) }}</td>
                                                <td class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">運費 {{ number_format($order->shipping_fee) }}　　跨境稅 {{ $order->parcel_tax ?? 0 }}　　折扣 {{ number_format($order->discount) }}</td>
                                                <td class="text-center align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">總重</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalWeight) }}</td>
                                                <td colspan="{{ $order->packageCount > 0 ? 3 : 2 }}" class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">商品總計</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalQty) }}</td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ $order->totalSellQty != 0 ? number_format($order->totalSellQty) : null }}</td>
                                                <td class="text-left align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;"></td>
                                                <td class="text-right align-middle text-sm text-primary text-bold" style="border-top:1px #000000 solid;border-bottom:1px #000000 solid;">{{ number_format($order->totalPrice) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    @if($order->status >= 3)
                                    <span class="text-sm text-bold text-danger returnmodify" style="display:none">注意！填寫銷退資料時，請填寫剩餘數量，稅金由訂單稅別自行計算。</span>
                                    <span class="text-sm text-bold text-danger allowancemodify" style="display:none">注意！填寫折讓資料時，請填寫各項折讓總額即可，稅金由訂單稅別自行計算。</span>
                                    @if($order->create_type == 'groupbuy')
                                    <span class="text-sm text-bold text-danger">注意！團購訂單請至團購訂單管理頁面作銷退/折讓。</span>
                                    @endif
                                    @if(count($order->returns) > 0)
                                    <br><span class="text-sm text-bold text-danger">下方若有出現 <span class="text-primary">銷退單</span> 資訊時，若有相同商品需要再次銷退時，退貨處理填寫的 剩餘數量 必須小於 <span class="text-primary">(單品：訂單數量 減去 該品項銷退數量、組合品：訂單數量 減去 [單品項銷退數量除以單品項需求量])</span>，否則將不做退貨處理。</span>
                                    @endif
                                    @elseif($order->status <3 && $order->status >= 0)
                                    @if($order->create_type == 'groupbuy')
                                    <span class="text-sm text-bold text-danger">注意！團購訂單請至團購訂單管理頁面修改數量。</span>
                                    @else
                                    @if(in_array($order->digiwin_payment_id,['AC0001','AC000101','AC000102','AC0002']))
                                    <span class="text-sm text-bold text-danger">注意！錢街與你訂訂單，無法修改。</span><br>
                                    @else
                                    <span class="text-sm text-bold text-danger">注意！訂單已被取消/刪除，無法修改直寄數量，修改/退貨數量不可大於原始數量。</span><br>
                                    @endif
                                    @endif
                                    @endif
                                </div>
                                @if(count($order->returns) > 0)
                                <div class="card-primary card-outline col-12 mb-2"></div>
                                {{-- <div class="col-12 mb-2">
                                    <span class="text-bold">銷退折讓資訊</span><br>
                                </div> --}}
                                <table class="table table-hover table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-left" width="25%">銷貨折讓單資訊</th>
                                            <th class="text-left" width="75%">銷貨折讓品項<br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->returns as $return)
                                        <tr style="border-bottom:3px #000000 solid;border-bottom:3px #000000 solid;">
                                            <td class="text-left align-top p-0">
                                                <div>
                                                    <span class="text-lg text-bold">{{ $return->return_no }}</span>
                                                    @if(count($return->chkStockin) == 0 && $return->is_del == 0)
                                                    <button type="button" value="{{ $return->id }}" class="badge btn-sm btn btn-danger btn-cancel">取消{{ $return->type }}單</button>
                                                    @endif
                                                </div>
                                                <hr class="mb-1 mt-1">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <span class="text-sm">銷退折讓單類別：{{ $return->type }}單</span><br>
                                                        <span class="text-sm">訂　單　單　號：<a href="{{ route('gate.orders.show',$return->order_id) }}" target="_blank">{{ $return->order_number }}</a></span><br>
                                                        <span class="text-sm">鼎　新{{ $return->type }}單別：{{ $return->erp_return_type }}</span><br>
                                                        <span class="text-sm">鼎　新{{ $return->type }}單號：{{ $return->erp_return_no }}</span><br>
                                                        <span class="text-sm">{{ mb_substr($return->type,0,1) }}　{{ mb_substr($return->type,1,1) }}　日　期：{{ $return->return_date }}</span><br>
                                                    </div>
                                                    <div class="col-6 float-left">
                                                        <span class="text-sm">金　額：{{ number_format(round($return->price,0)) }}</span><br>
                                                        <span class="text-sm"><span class="text-sm">總金額：{{ number_format(round($return->price + $return->tax,0)) }}</span><br>
                                                    </div>
                                                    <div class="col-6 float-right">
                                                        <span class="text-sm">稅　金：{{ number_format(round($return->tax,0)) }}</span><br>
                                                    </div>
                                                    <div class="col-12">
                                                        <span class="text-sm"><span class="text-sm">備　註：{{ $return->memo }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-left align-top p-0">
                                                <table class="table table-sm">
                                                    <thead class="table-info">
                                                        <th width="10%" class="text-left align-middle text-sm">鼎新{{ $return->type }}單號-序號<br>鼎新調撥單號-序號</th>
                                                        <th width="15%" class="text-left align-middle text-sm">商家</th>
                                                        <th width="10%" class="text-left align-middle text-sm">訂單商品鼎新品號<br>採購商品鼎新品號</th>
                                                        <th width="25%" class="text-left align-middle text-sm">品名</th>
                                                        <th width="5%" class="text-center align-middle text-sm">單位</th>
                                                        <th width="5%" class="text-right align-middle text-sm">數量</th>
                                                        <th width="5%" class="text-right align-middle text-sm">單價</th>
                                                        <th width="5%" class="text-right align-middle text-sm">金額</th>
                                                    </thead>
                                                    <tbody>
                                                        <form id="itemsform_order_{{ $return->id }}" method="POST">
                                                            @foreach($return->items as $item)
                                                            <tr>
                                                                <td class="text-left align-middle text-sm">{{ $item->erp_return_no.'-'.$item->erp_return_sno }}<br>{{ !empty($item->erp_requisition_no) ? $item->erp_requisition_no.'-'.$item->erp_requisition_sno : '' }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->vendor_name }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->order_digiwin_no }}<br>{{ $item->origin_digiwin_no }}</td>
                                                                <td class="text-left align-middle text-sm">{{ $item->product_name ?? $item->memo }}</td>
                                                                <td class="text-center align-middle text-sm">{{ $item->unit_name }}</td>
                                                                <td class="text-right align-middle text-sm">{{ $return->type == '折讓' ? null : $item->quantity }}</td>
                                                                <td class="text-right align-middle text-sm">{{ number_format(round($item->price)) }}</td>
                                                                <td class="text-right align-middle text-sm">{{ number_format( $return->type == '折讓' ? 1 * $item->price : $item->quantity * $item->price) }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </form>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </section>
    <form id="multiProcessForm" action="{{ url('orders/multiProcess') }}" method="POST">
        @csrf
        <input type="hidden" name="id[]" value="{{ $order->id }}">
        <input type="hidden" name="method" value="selected">
        <input type="hidden" name="cate" value="Synchronize">
        <input type="hidden" name="type" value="">
        <input type="hidden" name="filename" value="同步至鼎新">
        <input type="hidden" name="model" value="orders">
    </form>
    <form id="refundForm" action="{{ url('orders/multiProcess') }}" method="POST">
        @csrf
        <input type="hidden" name="id[]" value="{{ $order->id }}">
        <input type="hidden" name="method" value="selected">
        <input type="hidden" name="cate" value="Refund">
        <input type="hidden" name="type" value="refund">
        <input type="hidden" name="filename" value="退款信件">
        <input type="hidden" name="model" value="orders">
    </form>
    <form id="updateForm" action="{{ route('gate.orders.update', $order->id) }}" method="POST">
        @csrf
        <input type="hidden" name="_method" value="PATCH">
    </form>
</div>
@endsection

@section('modal')
{{-- 物流 Modal --}}
<div id="shippingModel" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 80%;height: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shippingModalLabel">訂單編號：220012345678，商品：佳德-鳳梨酥-單一商品，選擇物流</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body table-responsive">
                <form id="pickupShippingForm" action="{{ url('orders/multiProcess') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cate" value="pickupShipping">
                    <input type="hidden" name="id[]" value="{{ $order->id }}">
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group clearfix">
                                <div class="icheck-primary">
                                    <input type="radio" id="type1" name="type" value="依系統設定">
                                    <label for="type1">依系統設定</label><a href="javascript:" id="showShippingNote">(註)</a>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type2" name="type" value="自行挑選">
                                    <label for="type2">自行挑選</label>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type3" name="type" value="廠商發貨">
                                    <label for="type3">廠商直送</label>
                                </div>
                                <div class="icheck-primary">
                                    <input type="radio" id="type4" name="type" value="移除物流">
                                    <label for="type4">移除物流</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-9">
                            <div id="shippingNote" style="border:1px solid red;display:none">
                                M欄 貨運別 判斷規則如下：<br />
                                <ol>
                                    <li>01 台灣宅配通：<br />
                                    iCarry 官網訂單 AND 機場提貨</li>

                                    <li>02 順豐速運：<br />
                                    任何訂單 AND ( 旅店提貨 OR 寄送當地 OR 寄送台灣 ) AND 順豐速打單非紅色標示<br />
                                    蝦皮訂單 AND 寄送台灣 AND 備註包括 (台灣) AND 地址不包括 “全家” OR “7-11” AND 順豐速打單非紅色標示<br />
                                    Asiamiles 訂單 AND 寄送海外 AND 地址出現（香港 OR 澳門）<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（香港 OR 澳門  ）</li>
                                    <li>02 順豐-中國：<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（中國 ）</li>
                                    <li>02 順豐-日本：<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（日本） </li>

                                    <li>03 DHL：<br />
                                    任何訂單 AND 寄送海外 AND 地址出現（美國 OR 加拿大 OR 澳洲 OR 紐西蘭 OR 南韓）</li>

                                    <li>04 LINEX-新加坡：<br />
                                    所有訂單 AND 寄送海外 AND 地址出現 新加坡 <br />
                                    蝦皮訂單 AND 寄送海外 AND 備註包括 (新加坡)</li>
                                    <li>04 LINEX-馬來西亞：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 馬來西亞</li>
                                    <li>04 LINEX-法國：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 法國</li>
                                    <li>04 LINEX-越南：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 越南</li>
                                    <li>04 LINEX-泰國：<br />
                                    iCarry 官網訂單 AND 寄送海外 AND 地址出現 泰國</li>

                                    <li>06 黑貓宅急便：<br />
                                    iCarry 官網訂單 AND ( 旅店提貨 OR 寄送當地 ) AND 順豐速打單為紅色標示
                                    蝦皮訂單 AND 寄送台灣 AND 備註包括 (台灣) AND 地址不包括 “全家” OR “7-11” AND 順豐速打單為紅色標示</li>

                                    <li>08 MOMO-宅配通：<br />
                                    momo 匯入訂單</li>

                                    <li>09 7-11 大智通：<br />
                                    蝦皮訂單 AND (寄送台灣 OR 寄送當地) AND 備註包括 (台灣) AND 地址包括 “7-”<br />
                                    松果訂單 AND 寄送當地 AND 地址包括 “台灣 7-11”</li>

                                    <li>10 全家 日翊：<br />
                                    蝦皮訂單 AND (寄送台灣 OR 寄送當地) AND 備註包括 (台灣) AND 地址包括 “全家”<br />
                                    松果訂單 AND 寄送當地 AND 地址包括 “全家”</li>
                                    <li>11 萊爾富：<br />
                                    蝦皮訂單 AND 寄送台灣 AND 備註包括 (台灣) AND 地址包括 “萊爾</li>
                                </ol>
                            </div>
                            <div id="shippingVendor" style="border:1px solid blue;display:none">
                                <div class="row">
                                    @foreach($shippingVendors as $shippingVendor)
                                    @if($shippingVendor->name != '廠商發貨')
                                    <div class="icheck-success col-3">
                                        <input type="radio" id="shippingMemo{{ $shippingVendor->id }}" name="shippingMemo" value="{{ $shippingVendor->name }}">
                                        <label for="shippingMemo{{ $shippingVendor->id }}">{{ $shippingVendor->name }}</label>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary ">確定</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">取消</span>
                            </button>
                        </div>
                    </div>
                </form>
                <div class="form-group form-group-sm d-none" id="myShippingRecord">
                    <hr>
                    <label for="message-text" class="col-form-label">修改紀錄</label>
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th width="10%">#</th>
                                        <th width="25%">時間</th>
                                        <th width="20%">註記者</th>
                                        <th width="20%">欄位名稱</th>
                                        <th width="25%">紀錄內容</th>
                                    </tr>
                                </thead>
                                <tbody id="shippingRecord"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
            <form id="updateForm" action="{{ route('gate.orders.update', $order->id) }}" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PATCH">
            <div id="orderInfoModalData" class="modal-body">
            </div>
            <div class="modal-footer"><button type="sumbit" class="btn btn-primary">確認修改</button></div>
            </form>
            <form id="storeForm" action="{{ route('gate.orders.store') }}" method="POST">
                @csrf
            </form>
            <form id="cancelForm" action="{{ route('gate.sellReturn.cancel') }}" method="POST">
                @csrf
            </form>
        </div>
    </div>
</div>

{{-- 手動銷貨 Modal --}}
<div id="sellModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sellModalLabel">手動銷貨</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @if($order->chkDirectShipment > 0)
            <form id="sellForm" action="{{ url('orders/multiProcess') }}" method="POST">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <input type="hidden" name="method" value="selected">
                <input type="hidden" name="cate" value="sell">
                <input type="hidden" name="type" value="">
                <input type="hidden" name="filename" value="手動銷貨">
                <input type="hidden" name="model" value="orders">
            <div class="modal-body">
                <p class="text-danger">注意! 手動銷貨只適用於倉庫出貨，廠商直寄商品無法使用。<br>已完成銷貨品項不顯示，若不手動銷貨請將數量填 0 即可。</p>
                <table class="table mb-0 table-sm">
                    <thead class="table-info">
                        <th width="12%" class="text-left align-middle text-sm">貨號</th>
                        <th width="28%" class="text-left align-middle text-sm">品名</th>
                        <th width="15%" class="text-left align-middle text-sm">出貨單號</th>
                        <th width="10%" class="text-left align-middle text-sm">出貨日期</th>
                        <th width="10%" class="text-right align-middle text-sm">數量</th>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        @if($item->is_del == 0 && $item->direct_shipment == 0)
                            @if(strstr($item->sku,'BOM'))
                                @foreach($item->package as $package)
                                @if(($package->quantity - $package->sell_quantity) > 0)
                                <tr>
                                    <td class="text-left align-middle text-sm">{{ $package->digiwin_no }}</td>
                                    <td class="text-left align-middle text-sm">{{ $package->product_name }}</td>
                                    <td class="text-left align-middle text-sm">
                                        <input type="text" class="form-control form-control-sm" name="items[{{ $loop->iteration - 1 }}][shippingNumber]" placeholder="填寫出貨單號" required>
                                    </td>
                                    <td class="text-left align-middle text-sm">
                                        <input type="text" class="form-control form-control-sm datepicker" name="items[{{ $loop->iteration - 1 }}][sellDate]" value="{{ date('Y-m-d') }}">
                                    </td>
                                    <td class="text-right align-middle text-sm">
                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][productName]" value="{{ $package->product_name }}">
                                        <input type="hidden" name="items[{{ $loop->iteration - 1 }}][gtin13]" value="{{ $package->gtin13 ?? $package->sku }}">
                                        <input type="number" class="form-control form-control-sm text-right" name="items[{{ $loop->iteration - 1 }}][quantity]" value="{{ $package->quantity - $package->sell_quantity }}">
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            @else
                            @if(($item->quantity - $item->sell_quantity) > 0)
                            <tr>
                                <td class="text-left align-middle text-sm">{{ $item->digiwin_no }}</td>
                                <td class="text-left align-middle text-sm">{{ $item->product_name }}</td>
                                <td class="text-left align-middle text-sm">
                                    <input type="text" class="form-control form-control-sm" name="items[{{ $loop->iteration - 1 }}][shippingNumber]" placeholder="填寫出貨單號" required>
                                </td>
                                <td class="text-left align-middle text-sm">
                                    <input type="text" class="form-control form-control-sm datepicker" name="items[{{ $loop->iteration - 1 }}][sellDate]" value="{{ date('Y-m-d') }}">
                                </td>
                                <td class="text-right align-middle text-sm">
                                    <input type="hidden" name="items[{{ $loop->iteration - 1 }}][productName]" value="{{ $item->product_name }}">
                                    <input type="hidden" name="items[{{ $loop->iteration - 1 }}][gtin13]" value="{{ $item->gtin13 ?? $item->sku }}">
                                    <input type="number" class="form-control form-control-sm text-right" name="items[{{ $loop->iteration - 1 }}][quantity]" value="{{ $item->quantity - $item->sell_quantity }}">
                                </td>
                            </tr>
                            @endif
                            @endif
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" id="sell-btn">確認送出</button></div>
            </form>
            @else
            <div class="modal-body">
                <p class="text-danger">注意! 查無可手動銷貨商品。</p>
            </div>
            @endif
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
                            order['asiamiles_account'] == '' || order['asiamiles_account'] == null ? order['asiamiles_account'] = '' : '';
                            order['asiamiles_name'] == '' || order['asiamiles_name'] == null ? order['asiamiles_name'] = '' : '';
                            order['asiamiles_lastname'] == '' || order['asiamiles_lastname'] == null ? order['asiamiles_lastname'] = '' : '';
                            order['user_memo'] == '' || order['user_memo'] == null ? order['user_memo'] = '' : '';
                            order['admin_memo'] == '' || order['admin_memo'] == null ? order['admin_memo'] = '' : '';
                            order['user_name'] == '' || order['user_name'] == null ? order['user_name'] = '' : '';
                            order['user_tel'] == '' || order['user_tel'] == null ? order['user_tel'] = '' : '';
                            order['user_email'] == '' || order['user_email'] == null ? order['user_email'] = '' : '';
                            order['asiamiles_account'] == '' || order['asiamiles_account'] == null ? order['asiamiles_account'] = '' : '';
                            order['asiamiles_name'] == '' || order['asiamiles_name'] == null ? order['asiamiles_name'] = '' : '';
                            order['asiamiles_lastname'] == '' || order['asiamiles_lastname'] == null ? order['asiamiles_lastname'] = '' : '';
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
                            let html = '<div class="row align-items-center"><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">訂購人ID :</span></div><input type="text" class="form-control" value="'+order['user_id']+'" disabled></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">收件人資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收件人</span></div><input type="text" class=" form-control" name="receiver_name" value="'+order['receiver_name']+'"></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">電話</span></div><input type="text" class=" form-control" name="receiver_tel" value="'+order['receiver_tel']+'"></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">E-Mail</span></div><input type="text" class=" form-control" name="receiver_email" value="'+order['receiver_email']+'"></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-plane"></i>班機號碼／<i class="fas fa-hotel"></i>旅店名稱</span></div><input type="text" class="form-control" name="receiver_keyword" value="'+order['receiver_keyword']+'"></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i>提貨時間</span></div><input type="datetime" class="form-control" id="receiver_key_time" name="receiver_key_time" value="'+order['receiver_key_time']+'"></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">郵遞區號</span></div><input type="text" class=" form-control" name="receiver_zip_code" value="'+order['receiver_zip_code']+'"></div></div><div class="col-5 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">地址</span></div><input type="text" class=" form-control" name="receiver_address" value="'+order['receiver_address']+'"></div></div><div class="col-5 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">賀卡留言</span></div><input type="text" class=" form-control" name="greeting_card" value="'+order['greeting_card']+'"></div></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">訂單備註</span></div><input type="text" class=" form-control" name="user_memo" value="'+order['user_memo']+'"></div></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">管理者備註</span></div><input type="text" class=" form-control" name="admin_memo" value="'+order['admin_memo']+'"></div></div><div class="col-12 offset-3 mb-2" id="data_datepicker" style="display:none"></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">亞洲萬里通資訊</span></div><div class="col-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Account</span></div><input type="text" class=" form-control mr-2" name="asiamiles_account" value="'+order['asiamiles_account']+'"><div class="input-group-prepend"><span class="input-group-text">Name</span></div><input type="text" class="form-control mr-2" name="asiamiles_name" value="'+order['asiamiles_name']+'"><div class="input-group-prepend"><span class="input-group-text">Last Name</span></div><input type="text" class="form-control mr-2" name="asiamiles_lastname" value="'+order['asiamiles_lastname']+'"></div></div><div class="card-primary card-outline col-12 mb-2"></div><div class="col-12 mb-2"><span class="text-bold">發票資訊</span></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票號碼</span></div><input type="text" class=" form-control" name="is_invoice_no" value="'+order['is_invoice_no']+'" ></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">類別</span></div><select class="form-control" id="invoice_sub_type" name="invoice_sub_type"><option value="1" '+invoice_sub_type1+'>發票捐贈:慈善基金會</option><option value="2" '+invoice_sub_type2+'>個人戶</option><option value="3" '+invoice_sub_type3+'>公司戶</option></select></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">愛心碼</span></div><input type="text" class=" form-control" id="love_code" name="love_code" value="'+order['love_code']+'" '+love_code+'></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">載具</span></div><select class="form-control" id="carrier_type" name="carrier_type"><option value="" '+carrier_type+' >不使用載具</option><option value="0" '+carrier_type0+'>手機條碼</option><option value="1" '+carrier_type1+'>自然人憑證條碼</option><option value="2" '+carrier_type2+'>智富寶載具</option></select></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">手機條碼/自然人憑證條碼</span></div><input type="text" class=" form-control" id="carrier_num" name="carrier_num" value="'+order['carrier_num']+'" '+carrier_num+'></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票聯式</span></div><select class="form-control" id="invoice_type" name="invoice_type"><option value="2" '+invoice_type2+'>二聯式</option><option value="3" '+invoice_type3+'>三聯式</option></select></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">統編</span></div><input type="text" class=" form-control" id="invoice_number" name="invoice_number" value="'+order['invoice_number']+'" '+invoice_number+'></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">抬頭</span></div><input type="text" class=" form-control" id="invoice_title" name="invoice_title" value="'+order['invoice_title']+'" '+invoice_title+'></div></div><div class="col-2 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">收受人真實姓名</span></div><input type="text" class=" form-control" id="buyer_name" name="buyer_name" value="'+order['buyer_name']+'" ></div></div><div class="col-3 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">發票收受人E-Mail</span></div><input type="text" class=" form-control" id="buyer_email" name="buyer_email" value="'+order['buyer_email']+'" ></div></div>';
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
            let adminMemo = $('#admin_memo').val();
            let shippingNumber = $('#shipping_number').val();
            let payTime = $('#pay_time').val();
            form.append($('<input type="hidden" class="formappend" name="status" value="'+status+'">'));
            form.append($('<input type="hidden" class="formappend" name="admin_memo" value="'+adminMemo+'">'));
            form.append($('<input type="hidden" class="formappend" name="shipping_number" value="'+shippingNumber+'">'));
            form.append($('<input type="hidden" class="formappend" name="pay_time" value="'+payTime+'">'));
            form.submit();
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

        $('#sell').click(function(){
            $('#sellModal').modal('show');
        });

        // 防止重複點擊
        $('#sell-btn').click(function() {
            let form = $('#sellForm');
            $('#sell-btn').attr('disabled',true);
            form.submit();
        });
    })(jQuery);

    function refund(id){
        if(confirm('請確認是否發送退款信件?')){
            if($('input[name=refund]').val() > 0){
                let token = '{{ csrf_token() }}';
                let refund = $('input[name=refund]').val();
                $.ajax({
                    type: "post",
                    url: 'refund',
                    data: { id: id, refund: refund , _token: token },
                    success: function(data) {
                        if(data == 'success'){
                            alert('退款信件已寄出');
                        }
                    }
                });
            }
        }
    }

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
            let chkShippingFeeModify = $('input[name=shippingFeeModify]:checked').val();
            if(chkShippingFeeModify == 'on'){
                form.append($('<input type="hidden" name="shippingFeeModify" value="1">'));
            }else{
                form.append($('<input type="hidden" name="shippingFeeModify" value="0">'));
            }
            let chkUsingPoint = $('input[name=usingPoint]:checked').val();
            if(chkUsingPoint == 'on'){
                form.append($('<input type="hidden" name="usingPoint" value="1">'));
            }else{
                form.append($('<input type="hidden" name="usingPoint" value="0">'));
            }
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
            let returnShippingFee = $('#returnShippingFee').val();
            if(returnShippingFee){
                $('input[name=shippingFeeModify]').attr('checked',false);
            }

            form.append($('<input type="hidden" name="returnQty" value="1">'));
            form.append($('<input type="hidden" name="returnDate" value="'+returnDate+'">'));
            let chkShipping = $('input[name=shippingFeeModify]:checked').val();
            if(chkShipping == 'on'){
                form.append($('<input type="hidden" name="shippingFeeModify" value="1">'));
                form.append($('<input type="hidden" name="returnShippingFee" value="">'));
            }else{
                form.append($('<input type="hidden" name="shippingFeeModify" value="0">'));
                form.append($('<input type="hidden" name="returnShippingFee" value="'+returnShippingFee+'">'));
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
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
            form.append( $('<input type="hidden" class="formappend" name="model" value="orders">') );
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
                form.append($('<input type="hidden" class="formappend" name="refund" value="'+refund+'">'))
                form.submit();
            }else{
                alert('金額必須大於0');
            }
        }
    }
</script>
@endsection
