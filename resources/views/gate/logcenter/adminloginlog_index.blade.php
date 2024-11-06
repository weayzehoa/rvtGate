@extends('gate.layouts.master')

@section('title', '登入登出紀錄')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>管理者登入登出紀錄</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">後台管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('adminLoginLog') }}">登入登出紀錄</a></li>
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
                                <form action="{{ url('adminLoginLog') }}" method="GET" class="form-inline" role="search">
                                    {{-- <span class="right badge badge-primary mr-1">{{ $keyword ? '搜尋到' : '全部共' }} {{ $adminLogs->total() ?? 0 }} 筆</span> 選擇： --}}
                                    <div class="form-group-sm">
                                        <select class="form-control form-control-sm" name="admin_id" onchange="submit(this)">
                                            <option value="" {{ empty($admin_id) ? 'selected' : '' }}>全部管理員</option>
                                            @foreach($admins as $admin)
                                            <option value="{{ $admin->id }}" {{ !empty($admin_id) && $admin_id == $admin->id ? 'selected' : '' }}>{{ $admin->name }}{{ $admin->lock_on >= 3 ? '[鎖定]' : '' }}{{ $admin->is_on == 0 ? '[停用]' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <select class="form-control form-control-sm" name="result" onchange="submit(this)">
                                            <option value="" {{ empty($result) ? 'selected' : '' }}>全部結果</option>
                                            <option value="解鎖成功" {{ !empty($result) && $result == '解鎖成功' ? 'selected' : '' }}>解鎖成功</option>
                                            <option value="登入失敗" {{ !empty($result) && $result == '登入失敗' ? 'selected' : '' }}>登入失敗</option>
                                            <option value="登入成功" {{ !empty($result) && $result == '登入成功' ? 'selected' : '' }}>登入成功</option>
                                        </select>
                                        <select class="form-control form-control-sm" name="list" onchange="submit(this)">
                                            <option value="15" {{ $list == 15 ? 'selected' : '' }}>每頁 15 筆</option>
                                            <option value="30" {{ $list == 30 ? 'selected' : '' }}>每頁 30 筆</option>
                                            <option value="50" {{ $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                            <option value="100" {{ $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-info" title="搜尋所有欄位">
                                            <i class="fas fa-search"></i>
                                            搜尋
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left" width="20%">紀錄時間</th>
                                        <th class="text-left" width="20%">帳號</th>
                                        <th class="text-left" width="20%">姓名</th>
                                        <th class="text-left" width="20%">結果</th>
                                        <th class="text-left" width="10%">來源網站</th>
                                        <th class="text-left" width="10%">來源IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($adminLogs as $log)
                                    <tr>
                                        <td class="text-left align-middle">{{ $log->created_at }}</td>
                                        <td class="text-left align-middle">
                                            @if(!empty($log->account))
                                            {{ $log->account }}
                                            @else
                                            {{ $log->admin_account }}
                                            @endif
                                        </td>
                                        <td class="text-left align-middle">{{ $log->admin_name }}</td>
                                        <td class="text-left align-middle">{{ $log->result }}</td>
                                        <td class="text-left align-middle">{{ $log->site }}</td>
                                        <td class="text-left align-middle">{{ $log->ip }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ $adminLogs->appends($appends)->render() }}
                                @else
                                {{ $adminLogs->render() }}
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
