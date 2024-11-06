@extends('gate.layouts.master')

@section('title', '採購單管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>採購單管理</b><span class="badge badge-success text-sm">{{ $purchase->order_number }}</span></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orders') }}">採購單管理</a></li>
                        <li class="breadcrumb-item active">修改</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        @if(isset($purchase))
        <form id="myform" action="{{ route('gate.purchases.update', $purchase->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
        @else
        <form id="myform" action="{{ route('gate.purchases.store') }}" method="POST">
        @endif
            @csrf
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title mr-1">採購單資料</h3><span class="text-warning">{{ $purchase->purchase_no }}</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">鼎新單別</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->type ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">鼎新單號</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->erp_purchase_no ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-4 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">商家</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->vendor_name ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">單品數量</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->quantity ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">金額</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->amount ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">稅金</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->tax ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">稅別</span>
                                            </div>
                                            @if($purchase->tax_type == 1)
                                            <input type="text" class=" form-control" value="應稅內含" disabled>
                                            @elseif($purchase->tax_type == 2)
                                            <input type="text" class=" form-control" value="應稅外加" disabled>
                                            @elseif($purchase->tax_type == 3)
                                            <input type="text" class=" form-control" value="零稅率" disabled>
                                            @elseif($purchase->tax_type == 4)
                                            <input type="text" class=" form-control" value="免稅" disabled>
                                            @elseif($purchase->tax_type == 9)
                                            <input type="text" class=" form-control" value="不計稅" disabled>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">總計</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ ($purchase->amount + $purchase->tax) ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">建立時間</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->created_at ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">同步時間</span>
                                            </div>
                                            <input type="datetime" class="form-control" value="{{ $purchase->synced_time ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    <div class="col-5 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">備註</span>
                                            </div>
                                            <input type="text" class=" form-control" name="memo" value="{{ $purchase->memo ?? null }}" {{ $purchase->status != -1 && $purchase->status != 3 ? '' : 'disabled' }}>
                                        </div>
                                    </div>
                                    <div class="col-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">採購日期</span>
                                            </div>
                                            <input type="text" class="datepicker form-control" {!! $purchase->status == 0 ? 'name="purchase_date"' : ''!!} value="{{ $purchase->purchase_date ?? explode(' ',$purchase->created_at)[0] }}" {{ $purchase->status != 0 ? 'disabled' : ''}}>
                                        </div>
                                    </div>
                                    <div class="col-2 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">狀態</span>
                                            </div>
                                            <input type="text" class=" form-control" value="{{ $purchase->status_text ?? '' }}" disabled>
                                        </div>
                                    </div>
                                    @if($purchase->status != -1 && $purchase->status != 3)
                                    @if(in_array($menuCode.'SY', explode(',',Auth::user()->power)))
                                    <div class="col-1 mb-2">
                                        <button type="button" value="{{ $purchase->id }}" class="btn-block btn btn-primary btn-sync">同步至鼎新</button>
                                    </div>
                                    @endif
                                    @endif
                                    @if($purchase->is_del == 0)
                                    @if(count($purchase->checkStockin) == 0)
                                    @if($purchase->status != -1 && $purchase->status != 3)
                                    @if(in_array($menuCode.'CO', explode(',',Auth::user()->power)))
                                    <div class="col-1 mb-2">
                                        <button type="button" value="{{ $purchase->id }}" class="btn-block btn btn-danger btn-cancel">取消採購單</button>
                                    </div>
                                    @endif
                                    @endif
                                    @endif
                                    @endif
                                    @if(count($purchase->checkStockin) > 0)
                                    <div class="col-1 mb-2">
                                    <a href="{{ route('gate.purchases.returnForm', $purchase->id) }}" value="{{ $purchase->id }}" class="btn btn-warning">退貨</a>
                                    </div>
                                    @endif

                                    <div class="card-primary card-outline col-12 mb-2"></div>
                                    @if(!empty($purchase->orders))
                                    <div class="col-12 mb-2">
                                        <span class="text-bold">iCarry 訂單：</span>
                                        @foreach($purchase->orders as $order)
                                        <span class="badge badge-warning"><a href="{{ route('gate.orders.show',$order->id) }}" class="mr-1" target="_blank">{{ $order->order_number }}</a> <span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeOrder({{ $order->id }})">X</span></span>
                                        @if($loop->iteration != count($purchase->orders))
                                        ｜
                                        @endif
                                        @endforeach
                                    </div>
                                    <div class="card-primary card-outline col-12 mb-2"></div>
                                    @endif
                                    <table class="table table-sm">
                                        <thead class="table-info">
                                            <th width="10%" class="text-left align-middle text-sm">廠商到貨日</th>
                                            <th width="15%" class="text-left align-middle text-sm">商家</th>
                                            <th width="15%" class="text-left align-middle text-sm">貨號</th>
                                            <th width="23%" class="text-left align-middle text-sm">品名</th>
                                            <th width="5%" class="text-center align-middle text-sm">單位</th>
                                            <th width="5%" class="text-right align-middle text-sm">採購量</th>
                                            <th width="5%" class="text-right align-middle text-sm">入庫量</th>
                                            <th width="5%" class="text-right align-middle text-sm">退貨量</th>
                                            <th width="7%" class="text-right align-middle text-sm">採購價</th>
                                            <th width="5%" class="text-right align-middle text-sm">總價</th>
                                            <th width="5%" class="text-right align-middle text-sm">指定結案</th>
                                        </thead>
                                        <tbody>
                                            @foreach($purchase->items as $item)
                                            {{-- @if(strstr($item->sku,'BOM')) --}}
                                            {{-- <tr style="background-color:rgb(254, 255, 223)"> --}}
                                            {{-- @else --}}
                                            <tr>
                                            {{-- @endif --}}
                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if($item->is_del == 0)
                                                    <input type="hidden" class="form-control form-control-sm text-right" name="data[{{ $loop->iteration }}][id]" value="{{ $item->id }}">
                                                        @if($item->is_close == 0)
                                                            @if(empty($item->stockin_date))
                                                            @if($item->is_lock==0)
                                                                {{-- 暫時測試用, 未來直寄不可以修改日期 --}}
                                                                @if(env('APP_ENV') == 'local')
                                                                    @if($item->direct_shipment == 0)
                                                                    <input type="text" class="form-control form-control-sm datepicker" name="data[{{ $loop->iteration }}][vendor_arrival_date]" value="{{ $item->vendor_arrival_date }}">
                                                                    @else
                                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }} date_modify_{{ $item->id }}">{{ $item->vendor_arrival_date }}</span>
                                                                    @if($item->is_del == 0)<button type="button" class="badge badge-sm badge-success btn-modify" value="{{ $item->id }}">修改日期</button>@endif
                                                                    @endif
                                                                @else
                                                                <input type="text" class="form-control form-control-sm datepicker" name="data[{{ $loop->iteration }}][vendor_arrival_date]" value="{{ $item->vendor_arrival_date }}">
                                                                @endif
                                                            @else
                                                            <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_arrival_date }}</span>
                                                            @endif
                                                            @else
                                                            <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_arrival_date }}</span>
                                                            @endif
                                                        @else
                                                            <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_arrival_date }}</span>
                                                        @endif
                                                    @else
                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_arrival_date }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-left align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->vendor_name }}</span>
                                                    @if(!empty($item->vendor_shipping_no))
                                                    <span class="text-danger" data-toggle="popover" data-content="<small>廠商出貨單號：{{ $item->vendor_shipping_no }}</small>"><i class="fas fa-tags" title="廠商出貨單"></i></span>
                                                    @endif
                                                    @if($item->direct_shipment == 1)
                                                    <span class="text-primary"><i class="fas fa-truck" title="廠商直寄"></i></span>
                                                    @endif
                                                </td>
                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->digiwin_no }}</span>
                                                    @if(!empty($item->stockin_date))
                                                    <span data-toggle="popover" class="text-primary stockin_{{ $item->id }}" data-content="
                                                        <small>
                                                            入庫單號：{{ $item->erp_stockin_no }}<br>
                                                            入庫日期：{{ $item->stockin_date }}
                                                            {{-- <button class='btn btn-outline-secondary btn-xs' onclick='purchaseCancel({{ $item->syncedOrderItem['id'] }})'>移除</button> --}}
                                                        </small>
                                                        "><i class="fas fa-store-alt"></i></span>
                                                    @endif
                                                </td>
                                                <td class="text-left align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->product_name }}</span>
                                                    @if(in_array($menuCode.'MK', explode(',',Auth::user()->power)))
                                                    @if(count($purchase->checkStockin) == 0)
                                                    @if($purchase->status != -1 && $purchase->status != 3)
                                                    <span class="text-primary" data-toggle="popover" title="商品備註(按Enter更新)" data-placement="top" id="item_memo_{{ $item->id }}" data-content="<textarea class='text-danger' onkeydown='itemmemo(event,{{ $item->id }})'>{{ $item->memo }}</textarea>"><i class="fas fa-info-circle"></i></span>
                                                    @endif
                                                    @else
                                                    <span class="text-primary" data-toggle="popover" title="商品備註" data-placement="top" id="item_memo_{{ $item->id }}" data-content="{{ $item->memo ?? '無' }}"><i class="fas fa-info-circle"></i></span>
                                                    @endif
                                                    @else
                                                    <span class="text-primary" data-toggle="popover" title="商品備註" data-placement="top" id="item_memo_{{ $item->id }}" data-content="{{ $item->memo ?? '無' }}"><i class="fas fa-info-circle"></i></span>
                                                    @endif
                                                    @if($item->is_del == 1)<span class="text-gray text-sm text-bold">(已取消)</span>@endif
                                                </td>
                                                <td class="text-center align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->unit_name }}</span></td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if($item->is_del == 0)
                                                        @if($item->is_close == 0)
                                                            @if($item->stockinQty == 0)
                                                            @if($item->is_lock == 0)
                                                                {{-- 暫時測試用, 未來直寄不可以修改數量 --}}
                                                                @if(env('APP_ENV') == 'local')
                                                                @if($item->direct_shipment == 1)
                                                                <span>{{ $item->quantity }}</span>
                                                                @else
                                                                <input type="number" class="form-control form-control-sm text-right" name="data[{{ $loop->iteration }}][quantity]" value="{{ $item->quantity }}">
                                                                @endif
                                                                @else
                                                                <input type="number" class="form-control form-control-sm text-right" name="data[{{ $loop->iteration }}][quantity]" value="{{ $item->quantity }}">
                                                                @endif
                                                            @else
                                                            <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ number_format($item->quantity) }}</span>
                                                            @endif
                                                            @else
                                                            <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ number_format($item->quantity) }}</span>
                                                            @endif
                                                        @else
                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ number_format($item->quantity) }}</span>
                                                        @endif
                                                    @else
                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ number_format($item->quantity) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">
                                                    @if(!empty($item->stockinQty) && $item->stockinQty > 0)
                                                    @if($item->is_lock == 0 && !empty($item->single))
                                                    <span class="text-primary" id="item_qty_{{ $item->id }}" onclick="stockinModify({{ $item->single['id'] }})">
                                                        <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }} new_item_qty_{{ $item->id }}"><u>{{ strstr($item->sku,'BOM') ? '' : $item->stockinQty }}</u></span>
                                                    </span>
                                                    @else
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ strstr($item->sku,'BOM') ? '' : $item->stockinQty }}</span>
                                                    @endif
                                                    @endif
                                                </td>
                                                <td class="text-right align-middle text-sm order_item_modify_{{ $item->id }}" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">
                                                    @if(count($item->returns) > 0)
                                                    <span class="text-danger" data-toggle="popover" title="退貨資訊" data-placement="right" data-content="
                                                        @foreach($item->returns as $return)
                                                        退貨單號：{{ $return->return_discount_no }}
                                                        退貨數量：{{ $return->quantity }}<br>
                                                        @endforeach
                                                        ">
                                                    @if(!empty($item->returnQty))
                                                    -{{ $item->returnQty }}
                                                    @endif
                                                    </span>
                                                    @endif
                                                </td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if($item->is_del == 0)
                                                    @if($item->is_close == 0)
                                                    @if($item->is_lock == 0)
                                                    <input type="text" class="form-control form-control-sm text-right" name="data[{{ $loop->iteration }}][purchase_price]" value="{{ $item->purchase_price }}">
                                                    @else
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span>
                                                    @endif
                                                    @else
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span>
                                                    @endif
                                                    @else
                                                    <span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}"><span class="{{ $item->is_del == 1 ? 'double-del-line' : '' }}">{{ $item->purchase_price * $item->quantity }}</span></td>
                                                <td class="text-right align-middle text-sm" style="{{ strstr($item->sku,'BOM') ? 'border-top:1px #000000 solid;' : 'border-top:1px #000000 solid;border-bottom:1px #000000 solid;' }}">
                                                    @if($item->is_close == 0)
                                                    @if($purchase->status != -1 && $purchase->status != 3)
                                                    @if($item->is_lock == 0)
                                                    @if(in_array($menuCode.'COL', explode(',',Auth::user()->power)))
                                                    <button type="button" class="badge badge-sm badge-primary btn-close" value="{{ $item->id }}">指定結案</button>
                                                    @endif
                                                    @endif
                                                    @endif
                                                    @else
                                                    <span class="badge badge-sm badge-danger">已結案</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if(strstr($item->sku,'BOM'))
                                                @if(count($item->package)>0)
                                                <tr class="item_package_{{ $item->id }} m-0 p-0">
                                                    <td colspan="11" class="text-sm p-0">
                                                        <table width="100%" class="table-sm m-0 p-0">
                                                            {{-- <thead style="background-color:rgb(221, 221, 221)"> --}}
                                                            <thead>
                                                                <tr>
                                                                    <th width="10%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none"></th>
                                                                    <th width="15%" class="text-left align-middle text-sm" style="border: none; outline: none">單品貨號</th>
                                                                    <th width="23%" class="text-left align-middle text-sm" style="border: none; outline: none">品名</th>
                                                                    <th width="5%" class="text-center align-middle text-sm" style="border: none; outline: none">單位</th>
                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">採購量</th>
                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">入庫量</th>
                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none">退貨量</th>
                                                                    <th width="7%" class="text-right align-middle text-sm" style="border: none; outline: none">採購價</th>
                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                    <th width="5%" class="text-right align-middle text-sm" style="border: none; outline: none"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($item->package as $packageItem)
                                                                <tr>
                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                    <td class="text-left align-middle text-sm" ></td>
                                                                    <td class="text-left align-middle text-sm" >
                                                                        <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['digiwin_no'] }}</span>
                                                                        @if(!empty($packageItem->stockin_date))
                                                                        <span data-toggle="popover" class="text-primary stockin_{{ $item->id }}" data-content="
                                                                            <small>
                                                                                入庫單號：{{ $packageItem->erp_stockin_no }}<br>
                                                                                入庫日期：{{ $packageItem->stockin_date }}
                                                                                {{-- <button class='btn btn-outline-secondary btn-xs' onclick='purchaseCancel({{ $item->syncedOrderItem['id'] }})'>移除</button> --}}
                                                                            </small>
                                                                            "><i class="fas fa-store-alt"></i></span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-left align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['product_name'] }}</span>@if($packageItem['is_del'] == 1)<span class="text-gray text-sm text-bold">(已取消)</span>@endif</td>
                                                                    <td class="text-center align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['unit_name'] }}</span></td>
                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['quantity'] }}</span></td>
                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">
                                                                        @if(!empty($packageItem['stockinQty']))
                                                                        @if($item->is_lock == 0)
                                                                        <span class="text-primary" id="package_qty_{{ $packageItem['id'] }}" onclick="stockinModify({{ $packageItem['single']['id'] }})">
                                                                            <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }} new_package_qty_{{ $packageItem['id'] }}"><u>{{ $packageItem['stockinQty'] == 0 ? null : $packageItem['stockinQty'].' ' }}</u></span>
                                                                        </span>
                                                                        @else
                                                                        <span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['stockinQty'] == 0 ? null : $packageItem['stockinQty'].' ' }}</span>
                                                                        @endif
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">
                                                                        @if(count($packageItem->returns) > 0)
                                                                        <span class="text-danger" data-toggle="popover" title="退貨資訊" data-placement="right" data-content="
                                                                            @foreach($packageItem->returns as $return)
                                                                            退貨單號：{{ $return->return_discount_no }}
                                                                            退貨數量：{{ $return->quantity }}<br>
                                                                            @endforeach
                                                                            ">
                                                                        -{{ $packageItem['returnQty'] }}
                                                                        </span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-right align-middle text-sm" ><span class="{{ $packageItem['is_del'] == 1 ? 'double-del-line' : '' }}">{{ $packageItem['purchase_price'] }}</span></td>
                                                                    <td class="text-right align-middle text-sm" ></td>
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
                                        </tbody>
                                    </table>
                                    @if($purchase->status != -1 && $purchase->status != 3)
                                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary">確認修改</button>
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
    <form id="syncForm" action="{{ url('purchases/multiProcess') }}" method="POST">
        @csrf
    </form>
    <form id="cancelForm" action="{{ url('purchases/cancel') }}" method="POST">
        @csrf
    </form>
    <form id="closeForm" action="{{ url('purchases/close') }}" method="POST">
        @csrf
    </form>
    <form id="removeOrderForm" action="{{ url('purchases/removeOrder') }}" method="POST">
        @csrf
        <input type="hidden" name="id" value="{{ $purchase->id }}">
    </form>
    <form id="updateForm" action="{{ route('gate.purchases.update', $purchase->id) }}" method="POST">
        @csrf
        <input type="hidden" name="_method" value="PATCH">
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

{{-- 修改直寄廠商到貨日 Modal --}}
<div id="dateModifyModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateModifyModalLabel">修改廠商到貨日</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="dateModifyForm" action="{{ url('purchases/dateModify') }}" method="POST" class="float-right">
                @csrf
                <div class="modal-body">
                    <div>
                        <h6>修改規則:</h6>
                        <ul>
                            <li><span class="text-blod text-primary">採購單尚未同步時</span>，被修改的商品與日期已在其他商品列時，數量會被合併，若無則會新增出一列。被修改的商品若剩餘數量為0時，原商品列則會被刪除，大於0時，則保留該商品列，數量則為剩餘數量。</li>
                            <li><span class="text-blod text-danger">採購單已同步時</span>，被修改的商品列數量將會被設定為0並將相關的訂單移出此採購單，需另外為其建立採購單，剩餘的部分將會自動處理。</li>
                        </ul>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-append col-3">
                                <input class="form-control datepicker" type="text" id="date" nam="date" placeholder="輸入要修改的日期" autocomplete="off" required>
                            </div>
                            <div class="input-group-append col-3">
                                <input class="form-control" type="text" id="orderNumber" nam="orderNumber" placeholder="輸入要修改的訂單號碼" autocomplete="off" required>
                            </div>
                            <button type="button" class="btn btn-primary btn-dateModify">確定修改</button>
                        </div>
                    </div>
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

        $('.btn-cancel').click(function (e) {
            let id = $(this).val();
            let form = $('#cancelForm');
            if(confirm('請確認是否要取消這筆採購單?')){
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
        });

        $('.btn-close').click(function (e) {
            let id = $(this).val();
            let form = $('#closeForm');
            if(confirm('請確認是否要指定結案這筆採購單商品? (注意!! 確認後將直接同步鼎新，無法復原!!)')){
                form.append('<input type="hidden" class="formappend" name="id" value="'+id+'">')
                form.submit();
                $('.formappend').remove();
            };
        });

        $('.btn-sync').click(function (e) {
            let id = $(this).val();
            let form = $('#syncForm');
            if(confirm('請確認是否要同步這筆採購單?')){
                form.append('<input type="hidden" class="formappend" name="id[]" value="'+id+'">')
                form.append('<input type="hidden" class="formappend" name="method" value="selected">')
                form.append('<input type="hidden" class="formappend" name="cate" value="SyncToDigiwin">')
                form.append('<input type="hidden" class="formappend" name="type" value="undefined">')
                form.append('<input type="hidden" class="formappend" name="filename" value="中繼同步至鼎新">')
                form.append('<input type="hidden" class="formappend" name="model" value="purchase">')
                form.submit();
                $('.formappend').remove();
            };
        });

        $('.btn-stockinModify').click(function(){
            let url = window.location.href;
            let form = $('#stockinModifyForm');
            form.append($('<input type="hidden" class="formappend" name="url" value="'+url+'">'));
            form.submit();
        });

        $('.btn-modify').click(function (e) {
            $('.formappend').remove();
            $('#dateModifyModal').modal('show');
            let id = $(this).val();
            // let date = $('.date_modify_'+id).html();
            // $('#date').val(date);
            let form = $('#dateModifyForm');
            form.append('<input type="hidden" class="formappend" id="id" name="id" value="'+id+'">');
        });

        $('.btn-dateModify').click(function(){
            if(confirm('請確認是否要修改這筆廠商到貨日? (注意!! 修改後將無法復原!!)')){
                let form = $('#dateModifyForm');
                let id = $('#id').val();
                let date = $('.date_modify_'+id).html();
                let newDate = $('#date').val();
                let orderNumber = $('#orderNumber').val();
                if(newDate){
                    if(newDate == date){
                        alert('日期並未改變');
                        return;
                    }else{
                        if(orderNumber){
                            form.append('<input type="hidden" class="formappend" name="date" value="'+newDate+'">');
                            form.append('<input type="hidden" class="formappend" name="orderNumber" value="'+orderNumber+'">');
                            form.submit();
                        }else{
                            alert('請填寫要修改的訂單號碼');
                            return;
                        }
                    }
                }else{
                    alert('請填寫要修改的日期');
                    return;
                }
            }
        });

    })(jQuery);

    function itemQtyModify(e){
        let html = '';
        let orderStatus = {{ $purchase->status }};
        if(orderStatus == -1){
                html = '此採購單已被取消，無法修改數量';
        }
        $('#item_modify_info').html(html);
        $('#item_modify_info').toggle('display');
        orderStatus != -1 ? $('.itemqtymodify').toggle('display') : '';
        $(e).html() == '修改數量' ? $(e).html('取消修改') : $(e).html('修改數量');
    }

    function itemPriceModify(e){
        let html = '';
        let orderStatus = {{ $purchase->status }};
        if(orderStatus == -1){
                html = '此採購單已被取消，無法修改數量';
        }
        $('#item_modify_info').html(html);
        $('#item_modify_info').toggle('display');
        orderStatus != -1 ? $('.itempricemodify').toggle('display') : '';
        $(e).html() == '修改金額' ? $(e).html('取消修改') : $(e).html('修改金額');
    }

    function itemQtyModifySend(id, e){
        if(confirm('請確認採購單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
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

    function itemPriceModifySend(id, e){
        if(confirm('請確認採購單內容的變更，因為影響的資料將非常多，不可回溯。')){
            let form = $('#updateForm');
            let itemIds = $('.itempricemodify').serializeArray().map( item => item.name );
            let itemPrices = $('.itempricemodify').serializeArray().map( item => item.value );
            for(let i=0; i<itemIds.length;i++){
                if(itemPrices[i] <= 0){
                    alert('新的金額不可小於等於0');
                    return;
                }else{
                    let tmp1 = $('<input type="hidden" class="formappend" name="price['+i+'][id]" value="'+itemIds[i]+'">');
                    let tmp2 = $('<input type="hidden" class="formappend" name="price['+i+'][price]" value="'+itemPrices[i]+'">');
                    form.append(tmp1);
                    form.append(tmp2);
                }
            }
            form.submit();
        }
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
                        // if(qty == 0){
                        //     $("#item_qty_"+id).popover('hide');
                        //     $("#item_qty_"+id).remove();
                        //     $(".stockin_item_"+id).html('');
                        //     $(".stockin_item_"+id).attr("data-content",'');
                        //     $(".item_qty_"+id).html('');
                        // }else{
                            $("#item_qty_"+id).popover('hide');
                            $("#item_qty_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="itemQty(event,'+id+');">'+qty+'</textarea>');
                            $("#item_qty_"+id).html('<span class="new_item_qty_'+id+'"></span>');
                            $(".new_item_qty_"+id).html('<u>'+qty+'</u>');
                        // }
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
                        // if(qty == 0){
                        //     $("#package_qty_"+id).popover('hide');
                        //     $("#package_qty_"+id).remove();
                        //     $(".stockin_package_"+id).html('');
                        //     $(".stockin_package_"+id).attr("data-content",'');
                        //     $(".package_qty_"+id).html('');
                        // }else{
                            $("#package_qty_"+id).attr("data-content",'<textarea class="text-danger" onkeydown="packageQty(event,'+id+');">'+qty+'</textarea>');
                            $("#package_qty_"+id).html('<span class="new_package_qty_'+id+'"></span>');
                            $("#package_qty_"+id).popover('hide');
                            $(".new_package_qty_"+id).html('<u>'+qty+'</u>');
                        // }
                    }
                }
            });
        }
    }

    function removeOrder(orderId)
    {
        if(confirm('請確認是否移除該訂單號碼?? 移除後將無法找到正確的訂單資料，不可回溯。')){
            let form = $('#removeOrderForm');
            let tmp = $('<input type="hidden" class="formappend" name="orderId" value="'+orderId+'">');
            form.append(tmp);
            form.submit();
        }
    }


    function stockinModify(poisId){
        $('#stockinModifyRecord').html('');
        let token = '{{ csrf_token() }}';
            $.ajax({
                type: "post",
                url: 'getStockin',
                data: { poisId: poisId, _token: token },
                success: function(data) {
                    if(data){
                        console.log(data);
                        let label = '採購單號：' + data[0]['purchase_no'] + '，鼎新單號：'+ data[0]['erp_purchase_no'] + ' 入庫單數量修改 <br><span class="text-sm text-primary">' + data[0]['sku'] + ' ' + data[0]['product_name'] + '</span>';
                        let purchaseQty = '<span class="text-primary text-bold">總採購數量：'+data[0]['quantity']+'</span>';
                        for(let i=0; i<data.length; i++){
                            let record = '<tr><td class="text-center">'+(i+1)+'</td><td class="text-left">'+data[i]['erp_stockin_no']+'</td><td class="text-left">'+data[i]['erp_stockin_sno']+'</td><td class="text-left">'+data[i]['product_name']+'</td><td class="text-right">'+data[i]['purchase_price']+'</td><td class="text-right"><input type="hidden" class="form-control form-control-sm text-right" name="data['+i+'][id]" value="'+data[i]['id']+'"><input type="number" class="form-control form-control-sm text-right" name="data['+i+'][qty]" placeholder="輸入修改數量" value="'+data[i]['stockin_quantity']+'"></td><td class="text-right">'+data[i]['stockin_date']+'</td></tr>';
                            $('#stockinModifyRecord').append(record);
                        }
                        $('#stockinModifyModalLabel').html(label);
                        $('#purchaseQty').html(purchaseQty);
                    }
                }
            });
        $('#stockinModifyModal').modal('show');
    }
</script>
@endsection
