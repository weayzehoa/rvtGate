@extends('gate.layouts.master')

@section('title', '匯出中心')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>匯出中心</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('exportCenter') }}">匯出中心</a></li>
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
                            <div class="float-left">
                                <form action="{{ url('exportCenter') }}" method="GET" class="form-inline" role="search">
                                    <div class="form-group row">
                                        <div class="form-group-sm">
                                            <span class="mr-2">匯出者：{{ auth('gate')->user()->name }}</span>
                                        </div>
                                        <div class="form-group-sm">
                                        <span class="badge badge-primary text-sm mr-2">快搜 <i class="fas fa-hand-point-right"></i></span>
                                        </div>
                                        <div class="form-group-sm">
                                            <select class="form-control form-control-sm" name="cate" onchange="submit(this)">
                                                <option value="">類別</option>
                                                @for($i=0; $i<count($cates);$i++)
                                                <option value="{{ $cates[$i]['value'] }}" {{ !empty($cate) && $cate == $cates[$i]['value'] ? 'selected' : '' }}>{{ $cates[$i]['name'] }}</option>
                                                @endfor
                                            </select>
                                            <select class="form-control form-control-sm" name="list" onchange="submit(this)">
                                                <option value="15" {{ $list == 15 ? 'selected' : '' }}>每頁 15 筆</option>
                                                <option value="30" {{ $list == 30 ? 'selected' : '' }}>每頁 30 筆</option>
                                                <option value="50" {{ $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                                <option value="100" {{ $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                            </select>
                                        </div>
                                        <div class="form-group-sm ml-2">
                                            <div class="icheck-primary">
                                                <input type="checkbox" class="mr-1" name="chkall" id="chkall">
                                                <label for="chkall" id="chkall_text"></label>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="float-right">
                                <div class="input-group input-group-sm align-middle align-items-middle">
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ number_format($exports->total()) ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-2"><span class="text-danger text-bold">注意！匯出的檔案可能因選擇的資料過多而未完成，請過一段時間再重新整理頁面。</span></div>
                            <div class="mb-2"><span class="text-danger text-bold">注意！匯出中心僅能看到匯出者自己的資料，資料僅保留三天，若未手動刪除，系統將自動清除超過三天前的資料。</span></div>
                            @if(count($exports) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left" width="2%">
                                            <form id="delForm" action="{{ url('exportCenter/0') }}" method="POST" class="form-inline">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="ids" value="">
                                                <button type="button" class="btn btn-sm btn-danger" title="刪除已選擇" id="del-btn" style="display:none">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </th>
                                        <th class="text-left" width="12%">匯出時間</th>
                                        <th class="text-left" width="10%">工作單號</th>
                                        <th class="text-left" width="8%">類別</th>
                                        <th class="text-left" width="18%">名稱</th>
                                        @if(auth('gate')->user()->id == 40)
                                        <th class="text-left" width="37%">條件</th>
                                        <th class="text-left" width="5%">匯出者</th>
                                        @else
                                        <th class="text-left" width="42%">條件</th>
                                        @endif
                                        <th class="text-center" width="4%">下載</th>
                                        <th class="text-center" width="4%">刪除</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($exports as $export)
                                    @if(file_exists($export->filePath.$export->filename))
                                    <tr>
                                        <td class="text-left align-middle">
                                            <div class="form-group-sm ml-2">
                                                <div class="icheck-primary">
                                                    <input type="checkbox" class="chkbox" id="chkbox_{{ $export->id }}" name="chkbox" value="{{ $export->id }}">
                                                    <label for="chkbox_{{ $export->id }}" id="chkbox_{{ $export->id }}_text"></label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-left align-middle text-warp">{{ $export->start_time }}</td>
                                        <td class="text-left align-middle">{{ $export->export_no }}</td>
                                        <td class="text-left align-middle">{{ $export->cate }}</td>
                                        <td class="text-left align-middle">{{ $export->name }}</td>
                                        <td class="text-left align-middle text-warp">
                                            @if($export->condition['method'] == 'allData')
                                                <span class="text-bold text-primary">全部資料</span>
                                            @elseif(!empty($export->condition['id']))
                                                @if($export->condition['model'] == 'products')
                                                <span>商品貨號：
                                                    @foreach($export->skus as $product)
                                                    <span class="badge badge-info badge-sm">{{ $product->sku }}</span>
                                                    @endforeach
                                                </span>
                                                @elseif($export->condition['model'] == 'orders')
                                                <span>訂單編號：
                                                    @foreach($export->orderNumbers as $order)
                                                    <span class="badge badge-info badge-sm">{{ $order->order_number }}</span>
                                                    @endforeach
                                                </span>
                                                @elseif($export->condition['model'] == 'purchase')
                                                <span>採購單編號：
                                                    @foreach($export->purchaseNumbers as $purchase)
                                                    <span class="badge badge-info badge-sm">{{ $purchase->purchase_no }}</span>
                                                    @endforeach
                                                </span>
                                                @elseif($export->condition['model'] == 'statement')
                                                <span>對帳單編號：
                                                    @foreach($export->statementNumbers as $statement)
                                                    <span class="badge badge-info badge-sm">{{ $statement->statement_no }}</span>
                                                    @endforeach
                                                </span>
                                                @endif
                                            @elseif(!empty($export->condition['con']))
                                                @if($export->condition['model'] == 'purchase')
                                                    <span class="badge badge-info mr-1">
                                                        採購單狀態：
                                                        @if(empty($export->cons['status']))全部@else
                                                        @if($export->cons['status'] == '-1,0,1,2')全部@else
                                                        @if(in_array(-1,explode(',',$export->cons['status'])))已取消,@endif
                                                        @if(in_array(0,explode(',',$export->cons['status'])))尚未採購,@endif
                                                        @if(in_array(1,explode(',',$export->cons['status'])))已採購,@endif
                                                        @if(in_array(2,explode(',',$export->cons['status'])))已入庫,@endif
                                                        @endif
                                                        @endif
                                                    </span>
                                                    @if(!empty($export->cons['created_at']) || !empty($export->cons['created_at_end']))
                                                    <span class="badge badge-info mr-1">建立時間區間：
                                                        @if(!empty($export->cons['created_at'])){{ $export->cons['created_at'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['created_at_end'])){{ '至 '.$export->cons['created_at_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['vendor_arrival_date']) || !empty($export->cons['vendor_arrival_date_end']))
                                                    <span class="badge badge-info mr-1">廠商到貨日區間：
                                                        @if(!empty($export->cons['vendor_arrival_date'])){{ $export->cons['vendor_arrival_date'].' ' }}@else{{ '2021-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['vendor_arrival_date_end'])){{ '至 '.$export->cons['vendor_arrival_date_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['book_shipping_date']) || !empty($export->cons['book_shipping_date_end']))
                                                    <span class="badge badge-info mr-1">預定出貨日區間：
                                                        @if(!empty($export->cons['book_shipping_date'])){{ $export->cons['book_shipping_date'].' ' }}@else{{ '2021-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['book_shipping_date_end'])){{ '至 '.$export->cons['book_shipping_date_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['product_name']))<span class="badge badge-info mr-1">產品名稱：{{ $export->cons['product_name'] }}</span>@endif
                                                    @if(!empty($export->cons['vendor_name']))<span class="badge badge-info mr-1">商家名稱：{{ $export->cons['vendor_name'] }}</span>@endif
                                                    @if(!empty($export->cons['erp_purchase_no']))<span class="badge badge-info mr-1">鼎新採購單號：{{ $export->cons['erp_purchase_no'] }}</span>@endif
                                                    @if(!empty($export->cons['order_number']))<span class="badge badge-info mr-1">訂單號碼：{{ $export->cons['order_number'] }}</span>@endif
                                                @elseif($export->condition['model'] == 'statement')
                                                @if(!empty($export->cons['start_date']) || !empty($export->cons['end_date']))
                                                <span class="badge badge-info mr-1">對帳日期區間：
                                                    @if(!empty($export->cons['start_date'])){{ $export->cons['start_date'].' ' }}@else{{ '啟用日' }}@endif
                                                    @if(!empty($export->cons['end_date'])){{ '至 '.$export->cons['end_date'].' ' }}@else{{ '至 現在' }}@endif
                                                </span>
                                                @endif
                                                @if(!empty($export->cons['payment_com']))<span class="badge badge-info mr-1">付款條件：{{ $export->cons['payment_com'] }}</span>@endif
                                                @if(!empty($export->cons['statement_no']))<span class="badge badge-info mr-1">對帳單編號：{{ $export->cons['statement_no'] }}</span>@endif
                                                @if(!empty($export->cons['vendor_name']))<span class="badge badge-info mr-1">商家名稱：{{ $export->cons['vendor_name'] }}</span>@endif
                                                @if(!empty($export->cons['erp_purchase_no']))<span class="badge badge-info mr-1">鼎新採購單號：{{ $export->cons['erp_purchase_no'] }}</span>@endif
                                                @if(!empty($export->cons['purchase_no']))<span class="badge badge-info mr-1">iCarry採購單號：{{ $export->cons['purchase_no'] }}</span>@endif
                                                @if(!empty($export->cons['return_discount_no']))<span class="badge badge-info mr-1">退貨折抵單號：{{ $export->cons['return_discount_no'] }}</span>@endif
                                                @elseif($export->condition['model'] == 'products')
                                                    <span class="badge badge-info mr-1">
                                                        商品狀態：
                                                        @if(empty($export->cons['status']))全部@else
                                                        @if($export->cons['status'] == '1,2,-1,-2,-3,-9')全部@else
                                                        @if(in_array(-1,explode(',',$export->cons['status'])))未送審(草稿),@endif
                                                        @if(in_array(-2,explode(',',$export->cons['status'])))審核不通過,@endif
                                                        @if(in_array(-3,explode(',',$export->cons['status'])))暫停銷售,@endif
                                                        @if(in_array(-9,explode(',',$export->cons['status'])))已下架,@endif
                                                        @if(in_array(1,explode(',',$export->cons['status'])))上架中,@endif
                                                        @if(in_array(2,explode(',',$export->cons['status'])))待審核,@endif
                                                        @endif
                                                        @endif
                                                    </span>
                                                    <span class="badge badge-info mr-1">
                                                        物流方式：
                                                        @if(empty($export->cons['shipping_method']))全部@else
                                                        @if($export->cons['shipping_method'] == '1,2,3,4,5,6')全部@else
                                                        @if(in_array(1,explode(',',$export->cons['shipping_method'])))機場提貨,@endif
                                                        @if(in_array(2,explode(',',$export->cons['shipping_method'])))旅店提貨,@endif
                                                        @if(in_array(3,explode(',',$export->cons['shipping_method'])))現場提貨,@endif
                                                        @if(in_array(4,explode(',',$export->cons['shipping_method'])))寄送海外,@endif
                                                        @if(in_array(5,explode(',',$export->cons['shipping_method'])))寄送台灣,@endif
                                                        @if(in_array(6,explode(',',$export->cons['shipping_method'])))寄送當地@endif
                                                        @endif
                                                        @endif
                                                    </span>
                                                    @if(!empty($export->cons['created_at']) || !empty($export->cons['created_at_end']))
                                                    <span class="badge badge-info mr-1">上架時間區間：
                                                        @if(!empty($export->cons['created_at'])){{ $export->cons['created_at'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['created_at_end'])){{ '至 '.$export->cons['created_at_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['pass_time']) || !empty($export->cons['pass_time_end']))
                                                    <span class="badge badge-info mr-1">送審通過區間：
                                                        @if(!empty($export->cons['pass_time'])){{ $export->cons['pass_time'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['pass_time_end'])){{ '至 '.$export->cons['pass_time_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['category_id']))
                                                    <span class="badge badge-info mr-1">產品分類：
                                                    @foreach($categories as $category)
                                                        @if($category->id == $export->cons['category_id'])
                                                        {{ $category->name }}
                                                        @endif
                                                    @endforeach
                                                    </span>@endif
                                                    @if(!empty($export->cons['vendor_id']))
                                                    <span class="badge badge-info mr-1">商家：
                                                    @foreach($vendors as $vendor)
                                                        @if($vendor->id == $export->cons['vendor_id'])
                                                        {{ $vendor->name }}
                                                        @endif
                                                    @endforeach
                                                    </span>@endif
                                                    @if(!empty($export->cons['product_name']))<span class="badge badge-info mr-1">產品名稱：{{ $export->cons['product_name'] }}</span>@endif
                                                    @if(!empty($export->cons['vendor_name']))<span class="badge badge-info mr-1">商家名稱：{{ $export->cons['vendor_name'] }}</span>@endif
                                                    @if(!empty($export->cons['sku']))<span class="badge badge-info mr-1">產品貨號：{{ $export->cons['sku'] }}</span>@endif
                                                    @if(!empty($export->cons['digiwin_no']))<span class="badge badge-info mr-1">鼎新品號：{{ $export->cons['digiwin_no'] }}</span>@endif
                                                    @if(!empty($export->cons['low_quantity']))<span class="badge badge-info mr-1">低於安全庫存</span>@endif
                                                    @if(!empty($export->cons['zero_quantity']))<span class="badge badge-info mr-1">庫存小於等於0</span>@endif
                                                @elseif($export->condition['model'] == 'orders')
                                                    @if(!empty($export->cons['order_number']))<span>{{ $export->cons['order_number'] }}</span>@endif
                                                    <span class="badge badge-info mr-1">
                                                        商品狀態：
                                                        @if(empty($export->cons['status']))全部@else
                                                        @if($export->cons['status'] == '-1,0,1,2,3,4')全部@else
                                                        @if(in_array(-1,explode(',',$export->cons['status'])))已取消,@endif
                                                        @if(in_array(0,explode(',',$export->cons['status'])))未付款,@endif
                                                        @if(in_array(1,explode(',',$export->cons['status'])))待出貨,@endif
                                                        @if(in_array(2,explode(',',$export->cons['status'])))集貨中,@endif
                                                        @if(in_array(3,explode(',',$export->cons['status'])))已出貨,@endif
                                                        @if(in_array(4,explode(',',$export->cons['status'])))已完成,@endif
                                                        @endif
                                                        @endif
                                                    </span>
                                                    <span class="badge badge-info mr-1">
                                                        物流方式：
                                                        @if(empty($export->cons['shipping_method']))全部@else
                                                        @if($export->cons['shipping_method'] == '1,2,3,4,5,6')全部@else
                                                        @if(in_array(1,explode(',',$export->cons['shipping_method'])))機場提貨,@endif
                                                        @if(in_array(2,explode(',',$export->cons['shipping_method'])))旅店提貨,@endif
                                                        @if(in_array(3,explode(',',$export->cons['shipping_method'])))現場提貨,@endif
                                                        @if(in_array(4,explode(',',$export->cons['shipping_method'])))寄送海外,@endif
                                                        @if(in_array(5,explode(',',$export->cons['shipping_method'])))寄送台灣,@endif
                                                        @if(in_array(6,explode(',',$export->cons['shipping_method'])))寄送當地@endif
                                                        @endif
                                                        @endif
                                                    </span>
                                                    @if(!empty($export->cons['origin_country']))<span class="badge badge-info mr-1">發貨地：{{ $export->cons['origin_country'] }}</span>@endif
                                                    @if(!empty($export->cons['all_is_call']))
                                                        <span class="badge badge-info mr-1">訂單-已叫貨註記：有註記</span>
                                                    @else
                                                        @if(!empty($export->cons['is_call']))<span class="badge badge-info mr-1">訂單-已叫貨註記：{{ $export->cons['is_call'] == 'X' ? '尚無註記' : $export->cons['is_call'] }}</span>@endif
                                                    @endif
                                                    @if(!empty($export->cons['all_item_is_call']))
                                                        <span class="badge badge-info mr-1">商品-已叫貨註記：有註記</span>
                                                    @else
                                                        @if(!empty($export->cons['item_is_call']))<span class="badge badge-info mr-1">商品-已叫貨註記：{{ $export->cons['item_is_call'] == 'X' ? '尚無註記' : $export->cons['item_is_call'] }}</span>@endif
                                                    @endif
                                                    @if(!empty($export->cons['all_is_print']))
                                                        <span class="badge badge-info mr-1">列印註記：有註記</span>
                                                    @else
                                                        @if(!empty($export->cons['is_print']))<span class="badge badge-info mr-1">列印註記：{{ $export->cons['is_print'] == 'X' ? '尚無註記' : $export->cons['is_print'] }}</span>@endif
                                                    @endif
                                                    @if(!empty($export->cons['created_at']) || !empty($export->cons['created_at_end']))
                                                    <span class="badge badge-info mr-1">建單時間區間：
                                                        @if(!empty($export->cons['created_at'])){{ $export->cons['created_at'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['created_at_end'])){{ '至 '.$export->cons['created_at_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['shipping_time']) || !empty($export->cons['shipping_time_end']))
                                                    <span class="badge badge-info mr-1">出貨時間區間：
                                                        @if(!empty($export->cons['shipping_time'])){{ $export->cons['shipping_time'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['shipping_time_end'])){{ '至 '.$export->cons['shipping_time_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['invoice_time']) || !empty($export->cons['invoice_time_end']))
                                                    <span class="badge badge-info mr-1">發票開立時間區間：
                                                        @if(!empty($export->cons['invoice_time'])){{ $export->cons['invoice_time'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['invoice_time_end'])){{ '至 '.$export->cons['invoice_time_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['spend_point']))<span class="badge badge-info mr-1">使用購物金：{{ $export->cons['spend_point'] == 1 ? '是' : '否' }}</span>@endif
                                                    @if(!empty($export->cons['is_discount']))<span class="badge badge-info mr-1">折扣：{{ $export->cons['is_discount'] == 1 ? '有' : '無' }}</span>@endif
                                                    @if(!empty($export->cons['is_asiamiles']))<span class="badge badge-info mr-1">Asiamiles訂單：{{ $export->cons['is_asiamiles'] == 1 ? '是' : '否' }}</span>@endif
                                                    @if(!empty($export->cons['is_shopcom']))<span class="badge badge-info mr-1">美安訂單：{{ $export->cons['is_asiamiles'] == 1 ? '是' : '否' }}</span>@endif
                                                    @if(!empty($export->cons['promotion_code']))<span class="badge badge-info mr-1">折扣代碼：{{ $export->cons['promotion_code'] ?? ''}}</span>@endif
                                                    @if(!empty($export->cons['channel_order']))<span class="badge badge-info mr-1">渠道訂單：{{ $export->cons['channel_order'] }}</span>@endif
                                                    @if(!empty($export->cons['domain']))<span class="badge badge-info mr-1">購買網址：{{ $export->cons['domain'] }}</span>@endif
                                                    @if(!empty($export->cons['shipping_vendor_name']))<span class="badge badge-info mr-1">物流商：{{ $export->cons['shipping_vendor_name'] }}</span>@endif
                                                    @if(!empty($export->cons['book_shipping_date_not_fill']))
                                                    <span class="badge badge-info mr-1">預定出貨日區間：未預定</span>
                                                    @else
                                                        @if(!empty($export->cons['book_shipping_date']) || !empty($export->cons['book_shipping_date_end']))
                                                        <span class="badge badge-info mr-1">預定出貨日區間：
                                                            @if(!empty($export->cons['book_shipping_date'])){{ $export->cons['book_shipping_date'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                            @if(!empty($export->cons['book_shipping_date_end'])){{ '至 '.$export->cons['book_shipping_date'].' ' }}@else{{ '至 現在' }}@endif
                                                        </span>
                                                        @endif
                                                    @endif
                                                    @if(!empty($export->cons['pay_method']) && $export->cons['pay_method'] != '全部')<span class="badge badge-info mr-1" title="{{ $export->cons['pay_method'] == '全部' ? '全部' : str_replace('全部,','',$export->cons['pay_method']) }}">付款方式：{{ $export->cons['pay_method'] == '全部' ? '全部' : mb_substr(str_replace('全部,','',$export->cons['pay_method']),0,10).'...' }}</span>@endif
                                                    @if(!empty($export->cons['order_number']))<span class="badge badge-info mr-1">訂單編號：{{ $export->cons['order_number'] }}</span>@endif
                                                    @if(!empty($export->cons['partner_order_number']))<span class="badge badge-info mr-1"><span class="text-danger text-bold delete-btn" style="cursor:pointer" onclick="removeCondition('partner_order_number')">X</span> 合作廠商訂單號：{{ $export->cons['partner_order_number'] }}</span>@endif
                                                    @if(!empty($export->cons['pay_time']) || !empty($export->cons['pay_time_end']))
                                                    <span class="badge badge-info mr-1">付款時間區間：
                                                        @if(!empty($export->cons['pay_time'])){{ $export->cons['pay_time'].' ' }}@else{{ '2015-01-01 00:00:00' }}@endif
                                                        @if(!empty($export->cons['pay_time_end'])){{ '至 '.$export->cons['pay_time_end'].' ' }}@else{{ '至 現在' }}@endif
                                                    </span>
                                                    @endif
                                                    @if(!empty($export->cons['shipping_number']))<span class="badge badge-info mr-1">物流單號：{{ $export->cons['shipping_number'] }}</span>@endif
                                                    @if(!empty($export->cons['user_id']))<span class="badge badge-info mr-1">購買者ID：{{ $export->cons['user_id'] }}</span>@endif
                                                    @if(!empty($export->cons['buyer_name']))<span class="badge badge-info mr-1">購買者姓名：{{ $export->cons['buyer_name'] }}</span>@endif
                                                    @if(!empty($export->cons['buyer_phone']))<span class="badge badge-info mr-1">購買者電話：{{ $export->cons['buyer_phone'] }}</span>@endif
                                                    @if(!empty($export->cons['receiver_name']))<span class="badge badge-info mr-1">收件人姓名：{{ $export->cons['receiver_name'] }}</span>@endif
                                                    @if(!empty($export->cons['receiver_tel']))<span class="badge badge-info mr-1">收件人電話：{{ $export->cons['receiver_tel'] }}</span>@endif
                                                    @if(!empty($export->cons['receiver_address']))<span class="badge badge-info mr-1">收件人地址：{{ $export->cons['receiver_address'] }}</span>@endif
                                                    @if(!empty($export->cons['vendor_name']))<span class="badge badge-info mr-1">商家名稱：{{ $export->cons['vendor_name'] }}</span>@endif
                                                    @if(!empty($export->cons['product_name']))<span class="badge badge-info mr-1">商品名稱：{{ $export->cons['product_name'] }}</span>@endif
                                                @endif
                                            @endif
                                        </td>
                                        @if(auth('gate')->user()->id == 40)
                                        <td class="text-left align-middle">
                                            {{ $export->exportor }}
                                        </td>
                                        @endif
                                        <td class="text-center align-middle">
                                            <form id="downloadForm_{{ $export->id }}" action="{{ route('gate.exportCenter.download') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $export->id }}">
                                                <button type="button" class="btn btn-sm btn-success chkPwd-btn" value="{{ $export->id }}">
                                                    @if(strstr($export->filename,'pdf'))
                                                    <i class="fas fa-file-pdf"></i>
                                                    @elseif(strstr($export->filename,'xls'))
                                                    <i class="fas fa-file-excel"></i>
                                                    @elseif(strstr($export->filename,'zip'))
                                                    <i class="fas fa-file-archive"></i>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.exportCenter.destroy', $export->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <h3>尚無資料</h3>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-purple text-lg mr-2">總筆數：{{ number_format($exports->total()) ?? 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $exports->appends($appends)->render() }}
                                @else
                                {{ $exports->render() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css') }}">
@endsection

@section('script')
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";

        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
        });

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });

        var num_all = $('input[name="chkbox"]').length;
        var num = $('input[name="chkbox"]:checked').length;
        $("#chkall_text").html("全選("+num+"/"+num_all+")");

        $('#chkall').change(function(){
            if($("#chkall").prop("checked") == true){
                $('input[name="chkbox"]').prop("checked",true);
                $("#del-btn>button").attr("disabled",false);
            }else{
                $('input[name="chkbox"]').prop("checked",false);
                $("#del-btn>button").attr("disabled",true);
            }
            var num_all = $('input[name="chkbox"]').length;
            var num = $('input[name="chkbox"]:checked').length;
            $("#chkall_text").text("全選("+num+"/"+num_all+")");
            if(num > 0){
                $('#del-btn').show();
            }else{
                $('#del-btn').hide()
            }
        });

        $('input[name="chkbox"]').change(function(){
            var num_all = $('input[name="chkbox"]').length;
            var num = $('input[name="chkbox"]:checked').length;
            num_all != num ? $("#check_all").prop("checked",false) : $("#check_all").prop("checked",true);
            $("#chkall_text").html("全選("+num+"/"+num_all+")");
            if(num > 0){
                $('#del-btn').show();
            }else{
                $('#del-btn').hide()
            }
        });

        $('#del-btn').click(function(){
            let orderIds = $('input[name="chkbox"]:checked').serializeArray().map( item => item.value );
            $('input[name=ids]').val(orderIds);
            $('#delForm').submit();
        });

        $('.chkPwd-btn').click(function(){
            let token = '{{ csrf_token() }}';
            let pwd = prompt("請輸入密碼，輸入錯誤超過三次帳號將會被鎖住。");
            let id = $(this).val();
            let form = $(this).parent('form');
            if(pwd != null){
                form.append( $('<input type="hidden" class="formappend" name="pwd" value="'+pwd+'">') );
                form.submit();
            }
        });
    })(jQuery);
</script>
@endsection
