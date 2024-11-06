@extends('gate.layouts.master')

@section('title', '管理員管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>IP管制列表管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('ipSettings') }}">IP管制列表管理</a></li>
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
                                @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                <button type="button" class="btn btn-sm bg-success edit-btn mr-2" value="product_data_add">新增</button>
                                @endif
                            </div>
                            <div class="float-right">
                                <div class="input-group input-group-sm align-middle align-items-middle">
                                    <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($ips) ? number_format($ips->total()) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="product_data_add" style="display:none">
                                <form class="ProductForm" action="{{ route('gate.ipSettings.store') }}" method="POST">
                                    @csrf
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-12">
                                                <div class="row">
                                                    <div class="form-group col-3">
                                                        <label for="ip">IP</label><br>
                                                        <input type="text" class="form-control" id="ip" name="ip" placeholder="請輸入IP Address，ex: 192.168.50.1" required>
                                                    </div>
                                                    <div class="form-group col-2">
                                                        <label for="">使用者</label>
                                                        <select name="admin_id" class="form-control">
                                                            <option value="0">系統預設</option>
                                                            @foreach($admins as $admin)
                                                            <option value="{{ $admin->id }}">{{ $admin->name }} {{ $admin->is_on == 0 ? '[已停用]' : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-4">
                                                        <label for="memo">備註</label><br>
                                                        <input type="text" class="form-control" id="memo" name="memo" placeholder="請輸入備註說明，ex:公司內部IP">
                                                    </div>
                                                    <div class="form-group col-1 align-middle">
                                                        <label for="">忽略簡訊認證</label>
                                                        <div class="form-group clearfix">
                                                            <div class="icheck-success d-inline mr-2">
                                                              <input type="radio" id="radio1" name="disable" value="1">
                                                              <label for="radio1">是</label>
                                                            </div>
                                                            <div class="icheck-secondary d-inline">
                                                              <input type="radio" id="radio2" name="disable" value="0" checked>
                                                              <label for="radio2">否</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-2">
                                                        <label for="">　</label>
                                                        <div>
                                                            @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                                            <button type="submit" class="btn btn-success btn-block">新增</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @if(!empty($ips))
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="20%">IP Address</th>
                                        <th class="text-left text-sm" width="20%">使用者</th>
                                        <th class="text-left text-sm" width="24%">備註</th>
                                        <th class="text-center" width="10%">忽略簡訊認證</th>
                                        @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="8%">修改</th>
                                        <th class="text-center" width="10%">啟用</th>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="8%">刪除</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ips as $ip)
                                    <tr>
                                        <form action="{{ route('gate.ipSettings.update', $ip->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="_method" value="PATCH">
                                        <td class="text-left text-sm align-middle"><input type="text" class="form-control form-control-sm" name="ip" value="{{ $ip->ip }}" {{ in_array($ip->ip,$disable) ? 'disabled' : '' }}></td>
                                        <td class="text-left text-sm align-middle">
                                            <select name="admin_id" class="form-control form-control-sm" {{ in_array($ip->ip,$disable) ? 'disabled' : '' }}>
                                                <option value="0" {{ $ip->admin_id == 0 ? 'selected' : '' }}>系統預設</option>
                                                @foreach($admins as $admin)
                                                <option value="{{ $admin->id }}" {{ $ip->admin_id == $admin->id ? 'selected' : '' }}>{{ $admin->name }} {{ $admin->is_on == 0 ? '[已停用]' : '' }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-left text-sm align-middle">
                                            <input type="text" class="form-control form-control-sm" name="memo" value="{{ $ip->memo }}" {{ in_array($ip->ip,$disable) ? 'disabled' : '' }}>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-group clearfix">
                                                <div class="icheck-success d-inline mr-2">
                                                  <input type="radio" id="radio1-{{ $ip->id }}" name="disable" value="1" {{ $ip->disable == 1 ? 'checked' : '' }} {{ in_array($ip->ip,$disable) ? 'disabled' : '' }}>
                                                  <label for="radio1-{{ $ip->id }}">是</label>
                                                </div>
                                                <div class="icheck-secondary d-inline">
                                                  <input type="radio" id="radio2-{{ $ip->id }}" name="disable" value="0" {{ $ip->disable == 0 ? 'checked' : '' }} {{ in_array($ip->ip,$disable) ? 'disabled' : '' }}>
                                                  <label for="radio2-{{ $ip->id }}">否</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                            @if(!in_array($ip->ip,$disable))
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endif
                                            @endif
                                        </td>
                                        </form>
                                        <td class="text-center align-middle">
                                        @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                        @if(!in_array($ip->ip,$disable))
                                            <form action="{{ url('ipSettings/active/' . $ip->id) }}" method="POST">
                                                @csrf
                                                <input type="checkbox" name="is_on" value="{{ $ip->is_on == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="啟用" data-off-text="停用" data-off-color="secondary" data-on-color="primary" {{ isset($ip) ? $ip->is_on == 1 ? 'checked' : '' : '' }}>
                                            </form>
                                        @endif
                                        @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                            @if(!in_array($ip->ip,$disable))
                                            <form action="{{ route('gate.ipSettings.destroy', $ip->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($ips) ? number_format($ips->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                {{-- @if(isset($appends))
                                {{ $ips->appends($appends)->render() }}
                                @else
                                {{ $ips->render() }}
                                @endif --}}
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
{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">
{{-- Ekko Lightbox --}}
<link rel="stylesheet" href="{{ asset('vendor/ekko-lightbox/dist/ekko-lightbox.css') }}">
{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css') }}">
@endsection

@section('script')
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
{{-- 顏色選擇器 --}}
<script src="{{ asset('vendor/vanilla-picker/dist/vanilla-picker.min.js') }}"></script>
{{-- Ekko Lightbox --}}
<script src="{{ asset('vendor/ekko-lightbox/dist/ekko-lightbox.min.js') }}"></script>
{{-- multiselect --}}
<script src="{{ asset('vendor/multiselect/dist/js/multiselect.min.js') }}"></script>
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
{{-- Ckeditor 4.x --}}
<script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
        });

        $('.select2').select2();

        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });

        $('.edit-btn').click(function(e){
            $('.'+$(this).val()).toggle();
            if($(this).html() == '選擇商品'){
                $(this).html('取消');
            }else if($(this).html() == '取消'){
                $(this).html('選擇商品');
            }
        });

        $(document).ready(function($) {
            $('#productSelect').multiselect({
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

        $('#selectByCategory').change(function(){
            $('input[name=keyword]').val('');
            $('#selectByVendor').find('option:not(:first)').prop('selected',false);
            if($(this).val()){
                search($(this).val(),'');
            }
        });

        $('#selectByVendor').change(function(){
            $('#selectByCategory').find('option:not(:first)').prop('selected',false);
            $('input[name=keyword]').val('');
            if($(this).val()){
                search('','',$(this).val());
            }
        });

        $('.search-btn').click(function(){
            $('#selectByVendor').find('option:not(:first)').prop('selected',false);
            $('#selectByCategory').find('option:not(:first)').prop('selected',false);
            var keyword = $('#keyword').val();
            if(keyword){
                search('',keyword,'');
            }
        });

        $('input[name=keyword]').keyup(function(){
            // $('#selectByVendor').val(null).trigger('selected');
            $('#selectByVendor').find('option:not(:first)').prop('selected',false);
            $('#selectByCategory').find('option:not(:first)').prop('selected',false);
            if($(this).val()){
                search('',$(this).val(),'');
            }
        });

        function search(category,keyword,vendor){
        let token = '{{ csrf_token() }}';
        // let id = '{{ isset($curation) ? $curation->id : '' }}';
        let selected = $('#productSelect_to').find('option');
        let ids = [];
        for(let x=0;x<selected.length;x++){
            ids[x] = selected[x].value;
        }
        $.ajax({
            type: "post",
            url: 'excludeProducts/getProducts',
            data: {ids: ids, category: category, keyword: keyword, vendor: vendor, _token: token },
            success: function(data) {
                var options = '';
                for(let i=0;i<data.length;i++){
                    options +='<option value="'+data[i]['id']+'">'+data[i]['vendor_name']+'___'+data[i]['name']+'</option>';
                }
                $('#productSelect').html(options);
            }
        });
    }

    })(jQuery);
</script>
@endsection
