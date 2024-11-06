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
                    <h1 class="m-0 text-dark"><b>管理員帳號管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('admins') }}">管理員帳號管理</a></li>
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
                                <div class="input-group">
                                    <div class="input-group-append">
                                        @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                        <a href="{{ route('gate.admins.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i>新增</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="float-right">
                                <form action="{{ url('admins') }}" method="GET" class="form-inline" role="search">
                                    選擇：
                                    <div class="form-group-sm">
                                        <select class="form-control form-control-sm" name="is_on" onchange="submit(this)">
                                            <option value="2" {{ $is_on == 2 ? 'selected' : '' }}>所有狀態 ({{ $totalAdmins }})</option>
                                            <option value="1" {{ $is_on == 1 ? 'selected' : '' }}>啟用 ({{ $totalEnable }})</option>
                                            <option value="0" {{ $is_on == 0 ? 'selected' : '' }}>停用 ({{ $totalDisable }})</option>
                                        </select>
                                        <select class="form-control form-control-sm" name="list" onchange="submit(this)">
                                            <option value="15" {{ $list == 15 ? 'selected' : '' }}>每頁 15 筆</option>
                                            <option value="30" {{ $list == 30 ? 'selected' : '' }}>每頁 30 筆</option>
                                            <option value="50" {{ $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                            <option value="100" {{ $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                        </select>
                                        <input type="search" class="form-control form-control-sm" name="keyword" value="{{ isset($keyword) ? $keyword : '' }}" placeholder="輸入關鍵字搜尋" title="搜尋姓名、帳號及Email" aria-label="Search">
                                        <button type="submit" class="btn btn-sm btn-info" title="搜尋姓名、帳號及Email">
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
                                        <th class="text-center" width="5%">序號</th>
                                        <th class="text-left" width="15%">姓名</th>
                                        <th class="text-left" width="15%">帳號</th>
                                        <th class="text-left" width="20%">電子郵件</th>
                                        <th class="text-left" width="13%">建立日期</th>
                                        <th class="text-left" width="13%">停用日期</th>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="12%">啟用狀態</th>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="8%">刪除</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($admins as $admin)
                                    <tr>
                                        <td class="text-center align-middle">{{ $admin->id }}</td>
                                        <td class="text-left align-middle">
                                            <div class="user-panel d-flex">
                                                <div class="info">
                                                    <span class="username"><a href="{{ route('gate.admins.show', $admin->id ) }}">{{ $admin->name }}</a></span>
                                                </div>
                                                @if(in_array($menuCode.'M',explode(',',Auth::user()->power)))
                                                @if($admin->lock_on == 3)
                                                <form class="d-inline" action="{{ route('gate.admins.unlock', $admin->id) }}" method="POST">
                                                    @csrf
                                                    <span>(已鎖定)</span>
                                                    <button type="submit" class="btn btn-sm btn-success unlock-btn">
                                                        <i class="fas fa-unlock-alt"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-left align-middle">{{ $admin->account }}</td>
                                        <td class="text-left align-middle">{{ $admin->email }}</td>
                                        <td class="text-left align-middle">{{ $admin->created_at }}</td>
                                        <td class="text-left align-middle">@if($admin->is_on == 0){{ $admin->updated_at }}@endif</td>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ url('admins/active/' . $admin->id) }}" method="POST">
                                                @csrf
                                                <input type="checkbox" name="is_on" value="{{ $admin->is_on == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="啟用" data-off-text="停權" data-off-color="secondary" data-on-color="primary" {{ isset($admin) ? $admin->is_on == 1 ? 'checked' : '' : '' }}>
                                            </form>
                                        </td>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.admins.destroy', $admin->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
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
                                        <input type="hidden" name="is_on" value="{{ $is_on ?? '' }}">
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
                                {{ $admins->appends(['keyword' => $keyword, 'list' => $list, 'is_on' => $is_on])->render() }}
                                @else
                                {{ $admins->appends(['list' => $list, 'is_on' => $is_on])->render() }}
                                @endisset
                                @else
                                {{ $admins->appends(['keyword' => $keyword])->render() }}
                                @endisset
                                @else
                                {{ $admins->render() }}
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
