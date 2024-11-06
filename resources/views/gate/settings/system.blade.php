@extends('gate.layouts.master')

@section('title', '系統參數設定')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>系統參數設定</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">後台管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('systemSettings') }}">系統參數設定</a></li>
                        <li class="breadcrumb-item active">修改系統參數</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            @if(isset($system))
            <form id="myform" action="{{ route('gate.systemSettings.update', $system->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">預設參數</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="form-group col-md-2">
                                        <label for="sms_supplier"><span class="text-red">* </span>簡訊供應商</label>
                                        <select class="form-control {{ $errors->has('sms_supplier') ? ' is-invalid' : '' }}" id="sms_supplier" name="sms_supplier">
                                            <option value="">請選擇簡訊供應商</option>
                                            <option value="mitake" {{ strtolower($system->sms_supplier) == 'mitake' ? 'selected' : '' }}>三竹 (餘額: {{ $system->mitake_points }})</option>
                                            {{-- <option value="alibaba" {{ strtolower($system->sms_supplier) == 'alibaba' ? 'selected' : '' }}>阿里巴巴</option> --}}
                                            <option value="aws" {{ strtolower($system->sms_supplier) == 'aws' ? 'selected' : '' }}>AWS SNS</option>
                                            {{-- <option value="twilio" {{ strtolower($system->sms_supplier) == 'twilio' ? 'selected' : '' }}>Twilio</option> --}}
                                        </select>
                                        @if ($errors->has('sms_supplier'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('sms_supplier') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="email_supplier"><span class="text-red">* </span>郵件供應商</label>
                                        <select class="form-control {{ $errors->has('email_supplier') ? ' is-invalid' : '' }}" id="email_supplier" name="email_supplier">
                                            <option value="">請選擇郵件供應商</option>
                                            <option value="aws" {{ strtolower($system->email_supplier) == 'aws' ? 'selected' : '' }}>AWS SES</option>
                                        </select>
                                        @if ($errors->has('email_supplier'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email_supplier') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="customer_service_supplier"><span class="text-red">* </span>請選擇客服系統</label>
                                        <select class="form-control {{ $errors->has('customer_service_supplier') ? ' is-invalid' : '' }}" id="customer_service_supplier" name="customer_service_supplier">
                                            <option value="">請選擇客服系統</option>
                                            <option value="crisp" {{ strtolower($system->customer_service_supplier) == 'crisp' ? 'selected' : '' }}>Crisp</option>
                                        </select>
                                        @if ($errors->has('customer_service_supplier'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('customer_service_supplier') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="payment_supplier"><span class="text-red">* </span>金流服務商</label>
                                        <select class="form-control {{ $errors->has('payment_supplier') ? ' is-invalid' : '' }}" id="payment_supplier" name="payment_supplier">
                                            <option value="">請選擇金流服務商</option>
                                            <option value="藍新" {{ strtolower($system->payment_supplier) == '藍新' ? 'selected' : '' }}>藍新(智付通)</option>
                                            <option value="綠界" {{ strtolower($system->payment_supplier) == '綠界' ? 'selected' : '' }}>綠界</option>
                                            <option value="玉山銀行" {{ strtolower($system->payment_supplier) == '玉山銀行' ? 'selected' : '' }}>玉山銀行</option>
                                        </select>
                                        @if ($errors->has('payment_supplier'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('payment_supplier') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="invoice_supplier"><span class="text-red">* </span>發票開立服務商</label>
                                        <select class="form-control {{ $errors->has('invoice_supplier') ? ' is-invalid' : '' }}" id="invoice_supplier" name="invoice_supplier">
                                            <option value="">發票開立服務商</option>
                                            <option value="ezpay" {{ strtolower($system->invoice_supplier) == 'ezpay' ? 'selected' : '' }}>ezPay簡單付發票平台</option>
                                            <option value="acpay" {{ strtolower($system->invoice_supplier) == 'acpay' ? 'selected' : '' }}>ACPay電子發票平台</option>
                                        </select>
                                        @if ($errors->has('invoice_supplier'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('invoice_supplier') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="gross_weight_rate"><span class="text-red">* </span>毛重加成(倍)</label>
                                        <input type="number" step="0.1" class="form-control {{ $errors->has('gross_weight_rate') ? ' is-invalid' : '' }}" id="gross_weight_rate" name="gross_weight_rate" value="{{ $system->gross_weight_rate ?? '' }}" placeholder="請輸入毛重加成倍率">
                                        @if ($errors->has('gross_weight_rate'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('gross_weight_rate') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="twpay_quota"><span class="text-red">* </span>TWPAY可折抵總額 (目前已用：{{ number_format($twpayUsed) }}，剩餘：{{ number_format($system->twpay_quota - $twpayUsed) }})</label>
                                        <input type="number" class="form-control {{ $errors->has('twpay_quota') ? ' is-invalid' : '' }}" id="twpay_quota" name="twpay_quota" value="{{ $system->twpay_quota ?? '' }}" placeholder="請輸入TWPAY可折抵總額">
                                        @if ($errors->has('twpay_quota'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('twpay_quota') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="mitake_points">三竹簡訊餘額</label>
                                        <input type="number" class="form-control" id="mitake_points" value="{{ $system->mitake_points ?? '' }}" disabled>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="disable_ip_start">關閉IP檢查開始時間</label>
                                        <input type="text" class="form-control datetimepicker" id="disable_ip_start" name="disable_ip_start" value="{{ $system->disable_ip_start ?? '' }}">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="disable_ip_end">關閉IP檢查結束時間</label>
                                        <input type="text" class="form-control datetimepicker" id="disable_ip_end" name="disable_ip_end" value="{{ $system->disable_ip_end ?? '' }}">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>最後修改管理員</label>
                                        <br><span><b>{{ $system->admin->name }}</b> {{ $system->updated_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-white">
                        @if(in_array(isset($system) ? $menuCode.'M' : $menuCode.'N', explode(',',Auth::user()->power)))
                        <button type="submit" class="btn btn-primary confirm-btn">{{ isset($system) ? '修改' : '新增' }}</button>
                        @endif
                        <a href="{{ url('dashboard') }}" class="btn btn-info">
                            <span class="text-white"><i class="fas fa-history"></i> 取消</span>
                        </a>
                    </div>
                </div>
            </form>
            @endif
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
@endsection

@section('script')
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
@endsection

@section('JsValidator')
{!! JsValidator::formRequest('App\Http\Requests\Gate\SystemSettingsRequest', '#myform'); !!}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";

        // date time picker 設定
        $('.datetimepicker').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $(".confirm-btn").click(function() {
            if(confirm('系統參數設定切換，與全站系統相關，請勿任意修改，\n請確認無誤後再按確定送出。')){
                $('#myform').submit();
            }
        });
    })(jQuery);
</script>
@endsection
