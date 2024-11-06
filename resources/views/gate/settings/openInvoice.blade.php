@extends('gate.layouts.master')

@section('title', '開立發票設定')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>開立發票設定</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('openInvoiceSetting') }}">開立發票設定</a></li>
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
                            </div>
                            <div class="float-right">
                                <form action="{{ url('openInvoiceSetting') }}" method="GET" class="form-inline" role="search">
                                    選擇：
                                    <div class="form-group-sm">
                                        <select class="form-control form-control-sm" name="is_invoice" onchange="submit(this)">
                                            <option value="2" {{ $is_invoice == 2 ? 'selected' : '' }}>所有狀態</option>
                                            <option value="1" {{ $is_invoice == 1 ? 'selected' : '' }}>啟用 ({{ $totalEnable }})</option>
                                            <option value="0" {{ $is_invoice == 0 ? 'selected' : '' }}>停用 ({{ $totalDisable }})</option>
                                        </select>
                                        <select class="form-control form-control-sm" name="list" onchange="submit(this)">
                                            <option value="15" {{ $list == 15 ? 'selected' : '' }}>每頁 15 筆</option>
                                            <option value="30" {{ $list == 30 ? 'selected' : '' }}>每頁 30 筆</option>
                                            <option value="50" {{ $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                            <option value="100" {{ $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                        </select>
                                        <input type="search" class="form-control form-control-sm" name="keyword" value="{{ isset($keyword) ? $keyword : '' }}" placeholder="輸入關鍵字搜尋" title="搜尋姓名、帳號及Email" aria-label="Search">
                                        <button type="submit" class="btn btn-sm btn-info" title="搜尋代號、金流名稱">
                                            <i class="fas fa-search"></i>
                                            搜尋
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="10%">代號</th>
                                        <th class="text-left" width="15%">金流名稱</th>
                                        <th class="text-left" width="15%">create_type</th>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="12%">啟用狀態</th>
                                        <th class="text-center" width="12%">使用預收結帳單</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($departments as $department)
                                    <tr>
                                        <td class="text-center align-middle">{{ $department->customer_no }}</td>
                                        <td class="text-left align-middle">{{ $department->customer_name }}</td>
                                        <td class="text-left align-middle">{{ $department->create_type }}</td>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.openInvoiceSetting.activeInvoice') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="customer_no" value="{{ $department->customer_no }}">
                                                <input type="checkbox" name="is_invoice" value="{{ $department->is_invoice == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="啟用" data-off-text="停用" data-off-color="secondary" data-on-color="primary" {{ isset($department) ? $department->is_invoice == 1 ? 'checked' : '' : '' }}>
                                            </form>
                                        </td>
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.openInvoiceSetting.activeAcrtb') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="customer_no" value="{{ $department->customer_no }}">
                                                <input type="checkbox" name="is_acrtb" value="{{ $department->is_acrtb == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="啟用" data-off-text="停用" data-off-color="secondary" data-on-color="primary" {{ isset($department) ? $department->is_acrtb == 1 ? 'checked' : '' : '' }}>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <div class="form-group">
                                    <form action="{{ url('admins') }}" method="GET" class="form-inline" role="search">
                                        <input type="hidden" name="is_invoice" value="{{ $is_invoice ?? '' }}">
                                        <select class="form-control" name="list" onchange="submit(this)">
                                            <option value="15" {{ $list == 15 ? 'selected' : '' }}>每頁 15 筆</option>
                                            <option value="30" {{ $list == 30 ? 'selected' : '' }}>每頁 30 筆</option>
                                            <option value="50" {{ $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                            <option value="100" {{ $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                        </select>
                                        <input type="hidden" name="keyword" value="{{ $keyword ?? '' }}">
                                    </form>
                                </div>
                            </div>
                            <div class="float-right">
                                @if(isset($list) || isset($keyword))
                                @isset($list)
                                @isset($keyword)
                                {{ $departments->appends(['keyword' => $keyword, 'list' => $list, 'is_invoice' => $is_invoice])->render() }}
                                @else
                                {{ $departments->appends(['list' => $list, 'is_invoice' => $is_invoice])->render() }}
                                @endisset
                                @else
                                {{ $departments->appends(['keyword' => $keyword])->render() }}
                                @endisset
                                @else
                                {{ $departments->render() }}
                                @endisset
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
@endsection

@section('script')
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
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

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });
    })(jQuery);
</script>
@endsection
