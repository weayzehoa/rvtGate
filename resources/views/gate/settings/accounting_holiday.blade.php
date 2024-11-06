@extends('gate.layouts.master')

@section('title', '會計收款休假日管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>會計收款休假日管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('accountingHoliday') }}">會計收款休假日管理</a></li>
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
                        {{-- <div class="card-header">
                            <div class="row">
                                <div class="col-9">
                                    <div class="float-left d-flex align-items-center">
                                        <button id="showForm" class="btn btn-sm btn-success mr-2" title="隱藏欄位查詢">隱藏欄位查詢</button>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($holidays) ? number_format($holidays->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                        <div class="card-body">
                            <form id="searchForm" role="form" action="{{ route('gate.accountingHoliday.store') }}" method="POST">
                                @csrf
                                <input type="hidden" class="form-control" name="type" value="erpACRTCProcess">
                                <div class="mb-2 col-8 offset-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">休假日期</span>
                                        </div>
                                        <input type="text" class="datepicker form-control col-3" name="exclude_date" placeholder="填寫日期，ex: 2024-01-01" autocomplete="off" required>
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">備註</span>
                                        </div>
                                        <input type="text" class="order_shipping form-control" name="memo" placeholder="填寫備註，ex: 元旦假期" required>
                                        @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                        <div class="input-group-prepend">
                                            <button type="submit" class="btn btn-primary"><span>新增</span></button>
                                        </div>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-danger">注意! 周六及周日不需要設定，只需填寫周一至周五的日期即可。</p>
                                </div>
                            </form>
                            @if(count($holidays) > 0)
                            <div class="col-10 offset-1">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left align-middle" width="10%">休假日期</th>
                                        <th class="text-left align-middle" width="15%">備註</th>
                                        <th class="text-center align-middle" width="5%">刪除</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($holidays as $holiday)
                                    <tr>
                                        <td class="text-left align-middle">{{ $holiday->exclude_date }}</td>
                                        <td class="text-left align-middle">{{ $holiday->memo }}</td>
                                        <td class="text-center align-middle">
                                            @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                            <form action="{{ route('gate.accountingHoliday.destroy', $holiday->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
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
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($holidays) ? number_format($holidays->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(count($holidays) > 0)
                                @if(isset($appends))
                                {{ $holidays->appends($appends)->render() }}
                                @else
                                {{ $holidays->render() }}
                                @endif
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
{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">

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
        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#search').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
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
        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });
    })(jQuery);
</script>
@endsection
