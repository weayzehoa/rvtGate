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
                    <h1 class="m-0 text-dark"><b>我的帳號資料</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('admins') }}">管理員帳號管理</a></li>
                        <li class="breadcrumb-item active">我的帳號資料</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <form id="myform" action="{{ route('gate.admins.changePassWord') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">帳號資料</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="form-group col-6">
                                                <label for="account"><span class="text-red">* </span>帳號</label>
                                                <input type="text" class="form-control {{ $errors->has('account') ? ' is-invalid' : '' }}" value="{{ $admin->account }}" disabled>
                                                @if ($errors->has('account'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('account') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                            <div class="form-group col-6">
                                                <label for="name"><span class="text-red">* </span>姓名</label>
                                                <input type="text" class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ $admin->name }}">
                                                @if ($errors->has('name'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('name') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                            <div class="form-group col-6">
                                                <label for="email"><span class="text-red">* </span>EMail</label>
                                                <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $admin->email }}">
                                                @if ($errors->has('email'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                            <div class="form-group col-6">
                                                <label for="email"><span class="text-red">* </span>兩階段驗證方法</label>
                                                <select type="text" class="form-control {{ $errors->has('sms_vendor') ? ' is-invalid' : '' }}" name="verify_mode">
                                                    <option value="" {{ $admin->verify_mode == '' ? 'selected' : '' }}>關閉</option>
                                                    <option value="sms" {{ $admin->verify_mode == 'sms' ? 'selected' : '' }}>簡訊</option>
                                                    <option value="2fa" {{ $admin->verify_mode == '2fa' ? 'selected' : '' }}>驗證器</option>
                                                </select>
                                                @if ($errors->has('sms_vendor'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('sms_vendor') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                            <div class="form-group col-6">
                                                <div class="row">
                                                    <div class="form-group col-4">
                                                        <label for="sms_vendor">驗證器條碼</label><br>{{ $qrCodeUrl }}
                                                    </div>
                                                    <div class="form-group col-8">
                                                        <span>請使用手機至 Apple/Google Store 下載並安裝 Google Authenticator。使用 APP 掃描左邊 QRCode 建立 驗證碼產生器。</span>
                                                        <br><button type="button" id="renew" class="mt-1 btn btn-sm btn-warning" value="{{ $admin->id }}">重新產生條碼</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-4">
                                                <label for="mobile"><span class="text-red">* </span>行動電話(國際電話請填+號)</label>
                                                <input type="text" class="form-control {{ $errors->has('mobile') ? ' is-invalid' : '' }}" name="mobile" value="{{ $admin->mobile }}" placeholder="輸入行動電話號碼，國際電話請填+號">
                                                @if ($errors->has('mobile'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('mobile') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                            <div class="form-group col-2">
                                                <label for="sms_vendor">簡訊商</label>
                                                <select type="text" class="form-control {{ $errors->has('sms_vendor') ? ' is-invalid' : '' }}" name="sms_vendor">
                                                    <option value="">系統預設</option>
                                                    <option value="aws" {{ $admin->sms_vendor == 'aws' ? 'selected' : '' }}>AWS</option>
                                                    <option value="mitake" {{ $admin->sms_vendor == 'mitake' ? 'selected' : '' }}>三竹</option>
                                                </select>
                                                @if ($errors->has('sms_vendor'))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first('sms_vendor') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="oldpass"><span class="text-red">* </span>舊密碼</label>
                                            <input type="password" class="form-control {{ $errors->has('oldpass') ? ' is-invalid' : '' }}" id="oldpass" name="oldpass" value="" placeholder="請輸入舊密碼" autocomplete="off">
                                            @if ($errors->has('oldpass'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('oldpass') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="newpass"><span class="text-red">* </span>新密碼</label>
                                            <input type="password" class="form-control {{ $errors->has('newpass') ? ' is-invalid' : '' }}" id="newpass" name="newpass" value="" placeholder="請輸入新密碼" autocomplete="off">
                                            @if ($errors->has('newpass'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('newpass') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="newpass_confirmation"><span class="text-red">* </span>確認密碼</label>
                                            <input type="password" class="form-control {{ $errors->has('newpass_confirmation') ? ' is-invalid' : '' }}" id="newpass_confirmation" name="newpass_confirmation" value="" placeholder="請再次輸入新密碼" autocomplete="off">
                                            @if ($errors->has('newpass_confirmation'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('newpass_confirmation') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary mr-2">修改</button>
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
@endsection

@section('script')
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
@endsection

@section('JsValidator')
{!! JsValidator::formRequest('App\Http\Requests\Gate\AdminsUpdateRequest', '#myform'); !!}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('#renew').click(function (e) {
            let form = $('#myform');
            let id = $(this).val();
            if(confirm('注意! 重新產生驗證器條碼需重新使用手機上的 Google Authenticator 掃描新的 QRCode 條碼建立新的產生器，否則將無法登入。\n請確認是否要重新產生驗證器條碼?')){
                form.append( $('<input type="hidden" class="formappend" name="renew" value="1">') );
                $(this).parents('form').submit();
            };
        });
    })(jQuery);
</script>
@endsection
