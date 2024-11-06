@extends('gate.layouts.master')

@section('title', '管理員帳號管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>後台次選單管理</b><small> ({{ isset($subMenu) ? '修改' : '新增' }})</small></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('mainmenus') }}">後台主選單管理</a></li>
                        <li class="breadcrumb-item active">{{ isset($subMenu) ? '修改' : '新增' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        @if(isset($subMenu))
        <form id="myform" action="{{ route('gate.submenus.update', $subMenu->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
        @else
        <form id="myform" action="{{ route('gate.submenus.store') }}" method="POST">
        @endif
            @csrf
            <input type="hidden" name="mainmenu_id" value="{{ $mainMenu->id }}">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">{{ $subMenu->name ?? '新增'.$mainMenu->name }}次選單資料</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>所屬主選單名稱：{{ $mainMenu->name }}</label>
                                        </div>
                                        <div class="form-group">
                                            <label for="account"><span class="text-red">* </span>次選單名稱</label>
                                            <input type="text" class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}" id="name" name="name" value="{{ $subMenu->name ?? '' }}" placeholder="請輸次選單名稱">
                                            @if ($errors->has('name'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('name') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="fa5icon">Font Awesome 5 圖示 (範例: <span class="text-danger">&lt;i class="nav-icon fas fa-door-open text-danger"&gt;&lt;/i&gt;</span>)</label>
                                            <input type="text" class="form-control {{ $errors->has('fa5icon') ? ' is-invalid' : '' }}" id="fa5icon" name="fa5icon" value="{{ $subMenu->fa5icon ?? ''}}" placeholder="請輸入Font Awesome 5圖示完整語法，可在class中加上其他語法">
                                            @if ($errors->has('fa5icon'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('fa5icon') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="url_type"><span class="text-red">* </span>連結類型</label>
                                            <select class="form-control select2bs4 select2-primary {{ $errors->has('url_type') ? ' is-invalid' : '' }}" data-dropdown-css-class="select2-primary" name="url_type">
                                                <option value="1" {{ isset($subMenu) ? $subMenu->url_type == 1 ? 'selected' : '' : ''}}>內部連結</option>
                                                <option value="2" {{ isset($subMenu) ? $subMenu->url_type == 2 ? 'selected' : '' : ''}}>外部連結</option>
                                            </select>
                                            @if ($errors->has('url_type'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('url_type') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="url">連結 (選擇外部連結類型請輸入完整連結 http:// or https://)</label>
                                            <input type="text" class="form-control {{ $errors->has('url') ? ' is-invalid' : '' }}" id="url" name="url" value="{{ $subMenu->url ?? '' }}" placeholder="請輸入連結">
                                            @if ($errors->has('url'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('url') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="url">提供的功能</label>
                                            <div class="row col-md-12">
                                            @foreach($poweractions as $poweraction)
                                            @if(in_array($poweraction->code, explode(',',$subMenu->power_action)))
                                            <div class="icheck-primary mr-2">
                                                <input type="checkbox" id="pachkbox{{ $poweraction->id }}" name="power_action[]" value="{{ $poweraction->code }}" {{ isset($subMenu) ? in_array($poweraction->code, explode(',',$subMenu->power_action)) ? 'checked' : '' : '' }} disabled>
                                                <label for="pachkbox{{ $poweraction->id }}">{{ $poweraction->name }}({{ $poweraction->code }})</label>
                                            </div>
                                            @endif
                                            @endforeach
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-6">
                                                <label for="open_window">另開視窗</label>
                                                <div class="input-group">
                                                    <input type="checkbox" name="open_window" value="1" data-bootstrap-switch data-off-color="secondary" data-on-color="success" {{ isset($subMenu) ? $subMenu->open_window == 1 ? 'checked' : '' : '' }}>
                                                </div>
                                            </div>
                                            <div class="form-group col-6">
                                                <label for="is_on">啟用狀態</label>
                                                <div class="input-group">
                                                    <input type="checkbox" name="is_on" value="1" data-bootstrap-switch data-off-color="secondary" data-on-color="primary" {{ isset($subMenu) ? $subMenu->is_on == 1 ? 'checked' : '' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center bg-white">
                                @if(in_array(isset($subMenu) ? $menuCode.'M' : $menuCode.'S2N', explode(',',Auth::user()->power)))
                                <button type="submit" class="btn btn-primary">{{ isset($subMenu) ? '修改' : '新增' }}</button>
                                @endif
                                <a href="{{ url('mainmenus/submenu/'.$mainMenu->id) }}" class="btn btn-info">
                                    <span class="text-white"><i class="fas fa-history"></i> 取消</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
@endsection

@section('JsValidator')
{!! JsValidator::formRequest('App\Http\Requests\Gate\SubmenusRequest', '#myform'); !!}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";

        //Initialize Select2 Elements
        $('.select2').select2();

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });
    })(jQuery);
</script>
@endsection
