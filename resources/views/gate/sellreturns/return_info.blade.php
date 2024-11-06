@extends('gate.layouts.master')

@section('title', '銷退單品庫存提示')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>銷退單品庫存提示</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('sellReturnInfo') }}">銷退單品庫存提示</a></li>
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
                                <div class="col-9">
                                    <div class="float-left d-flex align-items-center">
                                        <button id="showForm" class="btn btn-sm btn-success mr-2" title="使用欄位查詢">使用欄位查詢</button>
                                        @if(in_array($menuCode.'IM', explode(',',Auth::user()->power)))
                                        <button class="btn btn-sm btn-primary mr-2" id="stockinImport">退貨入庫單匯入</button>
                                        @endif
                                        <button class="btn btn-sm btn-info mr-2" id="multiProcess" value="process" disabled><span>多筆處理</span></button>
                                        <div class="icheck-primary d-inline mr-2">
                                            <input type="radio" id="selectorder" name="multiProcess" value="selected">
                                            <label for="selectorder">自行勾選 <span id="chkallbox_text"></span></label>
                                        </div>
                                        <div class="icheck-primary d-inline mr-2">
                                            <input type="radio" id="chkallbox" name="multiProcess" value="allOnPage">
                                            <label for="chkallbox">目前頁面全選</label>
                                        </div>
                                        <form id="multiProcessForm" action="{{ route('gate.sellReturnInfo.multiProcess') }}" method="POST">
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($returns) ? number_format($returns->total()) : 0 }}</span>
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
                                <form id="searchForm" role="form" action="{{ url('sellReturnInfo') }}" method="get">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="return_no">銷退單號:</label>
                                                <input type="text" class="form-control" id="return_no" name="return_no" placeholder="填寫銷退單號" value="{{ isset($return_no) ? $return_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="erp_return_no">鼎新銷退單號:</label>
                                                <input type="text" class="form-control" id="erp_return_no" name="erp_return_no" placeholder="填寫鼎新銷退單號" value="{{ isset($erp_return_no) ? $erp_return_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="order_number">iCarry訂單編號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="order_number" name="order_number" placeholder="iCarry訂單單號" value="{{ isset($order_number) && $order_number ? $order_number : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="erp_requisition_no">鼎新調撥單號:</label>
                                                <input type="number" inputmode="numeric" class="form-control" id="erp_requisition_no" name="erp_requisition_no" placeholder="鼎新調撥單單號" value="{{ isset($erp_requisition_no) && $erp_requisition_no ? $erp_requisition_no : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="digiwin_no">鼎新貨號:</label>
                                                <input type="text" class="form-control" id="digiwin_no" name="digiwin_no" placeholder="填寫鼎新品號: 5TWXXXXXXX" value="{{ isset($digiwin_no) ? $digiwin_no ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="vendor_name">商家名稱:</label>
                                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="填寫商家名稱: 佳德" value="{{ isset($vendor_name) ? $vendor_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="product_name">商品品名:</label>
                                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="填寫商品品名: 鳳梨酥" value="{{ isset($product_name) ? $product_name ?? '' : '' }}" autocomplete="off" />
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label class="control-label" for="return_date">銷退日期區間:</label>
                                                <div class="input-group">
                                                    <input type="datetime" class="form-control datepicker" id="return_date" name="return_date" placeholder="格式：2016-06-06" value="{{ isset($return_date) ? $return_date ?? '' : '' }}" autocomplete="off" />
                                                    <span class="input-group-addon bg-primary">~</span>
                                                    <input type="datetime" class="form-control datepicker" id="return_date_end" name="return_date_end" placeholder="格式：2016-06-06" value="{{ isset($return_date_end) ? $return_date_end ?? '' : '' }}" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="is_chk">處理狀態:</label>
                                                <select class="form-control" id="is_chk" name="is_chk">
                                                    <option value=""  {{ isset($is_chk) ? $is_chk == "" ? 'selected' : '' : 'selected' }} class="text-secondary">不拘</option>
                                                    <option value="0"  {{ isset($is_chk) ? $is_chk == 0 ? 'selected' : '' : '' }} class="text-danger">未處理</option>
                                                    <option value="1"  {{ isset($is_chk) ? $is_chk == 1 ? 'selected' : '' : '' }} class="text-primary">已處理</option>
                                                </select>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="is_stockin">入庫狀態:</label>
                                                <select class="form-control" id="is_stockin" name="is_stockin">
                                                    <option value=""  {{ isset($is_stockin) ? $is_stockin == "" ? 'selected' : '' : 'selected' }} class="text-secondary">不拘</option>
                                                    <option value="0"  {{ isset($is_stockin) ? $is_stockin == 0 ? 'selected' : '' : '' }} class="text-danger">未入庫</option>
                                                    <option value="1"  {{ isset($is_stockin) ? $is_stockin == 1 ? 'selected' : '' : '' }} class="text-primary">已入庫</option>
                                                </select>
                                            </div>
                                            <div class="col-3 mt-2">
                                                <label for="is_confirm">驗收狀態:</label>
                                                <select class="form-control" id="is_confirm" name="is_confirm">
                                                    <option value=""  {{ isset($is_confirm) ? $is_confirm == "" ? 'selected' : '' : 'selected' }} class="text-secondary">不拘</option>
                                                    <option value="0"  {{ isset($is_confirm) ? $is_confirm == 0 ? 'selected' : '' : '' }} class="text-danger">未驗收</option>
                                                    <option value="1"  {{ isset($is_confirm) ? $is_confirm == 1 ? 'selected' : '' : '' }} class="text-primary">已驗收</option>
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
                                        <button type="button" onclick="formSearch()" class="btn btn-primary">查詢</button>
                                        <input type="reset" class="btn btn-default" value="清空">
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                    </div>
                                </form>
                            </div>
                            @if(count($returns) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="2%"></th>
                                        <th class="text-left text-sm" width="8%">銷退號碼</th>
                                        <th class="text-left text-sm" width="8%">訂單號碼</th>
                                        <th class="text-left text-sm" width="8%">訂單鼎新貨號<br>採購單鼎新號</th>
                                        <th class="text-center text-sm" width="8%">銷退日期<br>銷退者</th>
                                        <th class="text-center text-sm" width="5%">銷退<br>數量</th>
                                        <th class="text-center text-sm" width="10%">入庫狀況<br>鼎新調撥單號</th>
                                        <th class="text-left text-sm" width="8%">商品效期</th>
                                        <th class="text-left text-sm" width="20%">備註</th>
                                        <th class="text-center text-sm" width="8%">處理日期<br>處理者</th>
                                        <th class="text-left text-sm" width="6%">處理</th>
                                        <th class="text-center text-sm" width="3%">驗收</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($returns as $return)
                                    <tr>
                                        <form action="{{ route('gate.sellReturnInfo.update', $return->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="_method" value="PATCH">
                                        <td class="text-left text-sm align-middle">
                                            <input type="checkbox" class="chk_box_{{ $return->id }}" name="chk_box" value="{{ $return->id }}">
                                        </td>
                                        <td class="text-left text-sm align-middle">{{ $return->return_no }}</td>
                                        <td class="text-left text-sm align-middle"><a href="{{ route('gate.orders.show',$return->order_id) }}" target="_blank">{{ $return->order_number }}</a></td>
                                        <td class="text-left text-sm align-middle">{{ $return->order_digiwin_no }}<br>{{ $return->origin_digiwin_no }}</td>
                                        <td class="text-center text-sm align-middle">{{ $return->return_date }}<br>{{ $return->return_admin_name }}</td>
                                        <td class="text-center text-sm align-middle">{{ $return->quantity }}</td>
                                        <td class="text-center text-sm align-middle">
                                        @if($return->is_stockin == 1)
                                        <span class="text-sm text-primary">已入庫 @if(!empty($return->stockin_admin_name))({{ $return->stockin_admin_name }})@endif</span>
                                        @if(!empty($return->erp_requisition_no ))
                                        <br><span class="text-sm">{{ $return->erp_requisition_no.'-'.$return->erp_requisition_sno }}</span>
                                        @endif
                                        @else
                                        <span class="text-sm text-danger">尚未入庫</span>
                                        @endif
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            <input type="text" class="form-control form-control-sm" name="expiry_date" value="{{ $return->expiry_date }}">
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm" name="item_memo" value="{{ $return->item_memo }}">
                                                <button type="submit" class="btn btn-sm btn-danger">修改</button>
                                            </div>
                                        </td>
                                        </form>
                                        <td class="text-center text-sm align-middle">
                                            <span class="text-sm">{{ explode(' ',$return->chk_date)[0] }}</span><br><span class="text-sm">{{ $return->admin_name }}</span>
                                        </td>
                                        <td class="text-left align-middle">
                                            @if($return->is_chk == 0)
                                            <form action="{{ route('gate.sellReturnInfo.update', $return->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="PATCH">
                                                <input type="hidden" name="is_chk" value="1">
                                                <button type="submit" class="chk-btn btn btn-sm btn-primary">處理</button>
                                            </form>
                                            @else
                                            <span class="text-sm text-success">已處理</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-sm align-middle">
                                            <input type="checkbox" name="is_confirm" value="{{ $return->id }}" {{ $return->is_confirm == 1 ? 'checked' : ''}}>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-left text-sm"></td>
                                        <td colspan="12" class="text-left text-sm">{{ $return->product_name }}</td>
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
        <form id="confirmForm" role="form" action="{{ url('sellReturnInfo/confirm') }}" method="POST">
            @csrf
        </form>
    </section>
</div>
@endsection

@section('modal')
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
                <form  id="importForm" action="{{ url('sellReturnInfo/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="cate" value="stockin">
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
                    <span class="text-danger">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/退貨入庫報表範本.xls" target="_blank">退貨入庫報表範本</a> ，製作正確的檔案。</span>
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
                <div class="mb-2">
                    @if(in_array($menuCode.'M', explode(',',Auth::user()->power)))
                    <button class="btn btn-sm btn-danger isStockin mr-2" value="unchk" disabled>標示為未處理</button>
                    <button class="btn btn-sm btn-info isStockin mr-2" value="stockin" disabled><span>標示為已入庫與已處理</span></button>
                    <button class="btn btn-sm btn-danger isStockin mr-2" value="unconfirm" disabled>標示為未驗收</button>
                    <button class="btn btn-sm btn-info isStockin mr-2" value="confirm" disabled><span>標示為已驗收</span></button>
                    <button class="btn btn-sm btn-success isStockin mr-2" value="memoModify" disabled><span>修改備註</span></button>
                    <button class="btn btn-sm btn-danger isStockin mr-2" value="memoCancel" disabled><span>取消備註</span></button>
                    @endif
                </div>
                <input type="text" class="form-control" id="memo" name="memo" placeholder="請輸入備註(只適用於修改備註按鈕)">
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
        $('.datepicker').datepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });

        $('#delete').click(function (e) {
            if(confirm('請確認是否要一次刪除未處理及異常資料?')){
                $('#deleteForm').submit();
            };
        });

        $('.execute').click(function (e) {
            let id = $(this).val();
            let form = $('#executeForm');
            form.append($('<input type="hidden" name="id" value="'+id+'">'));
            if(confirm('請確認是否要處理這筆資料?\n注意! 處理這筆資料同時會將相同的訂單資料一併處理，\n若有資料狀態異常時該筆資料將會被忽略不做處理。')){
                form.submit();
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
                $('.isStockin').prop("disabled",true);
                $('#multiProcess').prop("disabled",true);
            }else if(num > 0){
                $("#selectorder").prop("checked",true)
                $('.isStockin').prop("disabled",false);
                $('#multiProcess').prop("disabled",false);
            }else if(num == num_all){
                $("#chkallbox").prop("checked",true);
                $('.isStockin').prop("disabled",false);
                $('#multiProcess').prop("disabled",false);
            }
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
        });

        $('#multiProcess').click(function(){
            if($('input[name="multiProcess"]:checked').val() == 'selected'){
                let num = $('input[name="chk_box"]:checked').length;
                if(num == 0){
                    alert('尚未選擇資料');
                    return;
                }
            }
            $('#multiModal').modal('show');
        });

        $('input[name="multiProcess"]').click(function(){
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            if($(this).val() == 'allOnPage'){
                $('input[name="chk_box"]').prop("checked",true);
                $('input[name="multiProcess"]').prop("checked",true);
                $('.isStockin').prop("disabled",false);
                $('#multiProcess').prop("disabled",false);
            }else if($(this).val() == 'selected'){
                if(num == 0){
                    $('#chkallbox').prop("checked",false);
                    $('.isStockin').prop("disabled",true);
                    $('#multiProcess').prop("disabled",true);
                }else if(num > 0){
                    $("#selectorder").prop("checked",true)
                    $('.isStockin').prop("disabled",false);
                    $('#multiProcess').prop("disabled",false);
                }else if(num == num_all){
                    $("#chkallbox").prop("checked",true);
                    $('.isStockin').prop("disabled",false);
                    $('#multiProcess').prop("disabled",false);
                }
            }else if($(this).val() == 'byQuery'){
                $('input[name="chk_box"]').prop("checked",false);
                $('.isStockin').prop("disabled",false);
            }else{
                $('.isStockin').prop("disabled",true);
            }
            var num_all = $('input[name="chk_box"]').length;
            var num = $('input[name="chk_box"]:checked').length;
            $("#chkallbox_text").text("("+num+"/"+num_all+")");
            $('#orderSearchForm').hide();
            $('#showForm').html('使用欄位查詢');
        });

        $('.isStockin').click(function (e){
            let method = $(this).val();
            let form = $('#multiProcessForm');
            let ids = $('input[name="chk_box"]:checked').serializeArray().map( item => item.value );
            if(ids.length > 0){
                for(let i=0;i<ids.length;i++){
                        form.append($('<input type="hidden" class="formappend" name="ids['+i+']">').val(ids[i]));
                    }
                form.append($('<input type="hidden" class="formappend" name="method">').val(method));
                if(method == 'memoModify'){
                    let memo = $('#memo').val();
                    if(memo){
                        form.append($('<input type="hidden" class="formappend" name="memo">').val(memo));
                    }else{
                        alert('請填寫備註');
                        return;
                    }
                }else if(method == 'memoCancel'){
                    if(confirm("請確認是否清除所有備註資料??")){
                        // 確認
                    }else{
                        return;
                    }
                }
                form.submit();
            }else{
                alert('請先選擇要處理的資料');
            }
        });

        $('input[name=is_confirm]').change(function(){
            let form = $('#confirmForm');
            let id = $(this).val();
            let isConfirm = 0;
            if ($(this).is(":checked"))
            {
                isConfirm = 1;
            }
            form.append($('<input type="hidden" class="formappend" name="id" value="'+id+'">'));
            form.append($('<input type="hidden" class="formappend" name="is_confirm" value="'+isConfirm+'">'));
            form.submit();
        });

        $('#stockinImport').click(function(){
            $('#importModal').modal('show');
        });

        $('#importBtn').click(function(){
            let form = $('#importForm');
            $('#importBtn').attr('disabled',true);
            form.submit();
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
