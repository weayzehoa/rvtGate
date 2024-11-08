@extends('gate.layouts.master')

@section('title', '公司資料設定')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>公司資料設定</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">後台管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('companySettings') }}">公司資料設定</a></li>
                        <li class="breadcrumb-item active">修改公司資料</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            @if(isset($company))
            <form id="myform" action="{{ route('gate.companySettings.update', $company->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">{{ $company->name ?? '' }} 資料設定</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="name"><span class="text-red">* </span>公司名稱</label>
                                        <input type="text" class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}" id="name" name="name" value="{{ $company->name ?? '' }}" placeholder="請輸入公司中文名稱">
                                        @if ($errors->has('name'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="name_en"><span class="text-red">* </span>公司英文名稱</label>
                                        <input type="text" class="form-control {{ $errors->has('name_en') ? ' is-invalid' : '' }}" id="name_en" name="name_en" value="{{ $company->name_en ?? '' }}" placeholder="請輸入公司英文名稱">
                                        @if ($errors->has('name_en'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('name_en') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="tax_id_num"><span class="text-red">* </span>統一編號</label>
                                        <input type="number" class="form-control {{ $errors->has('tax_id_num') ? ' is-invalid' : '' }}" id="tax_id_num" name="tax_id_num" value="{{ $company->tax_id_num ?? '' }}" placeholder="請輸入公司統一編號">
                                        @if ($errors->has('tax_id_num'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('tax_id_num') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="tel"><span class="text-red">* </span>電話(含+號與國碼)</label>
                                        <input type="text" class="form-control {{ $errors->has('tel') ? ' is-invalid' : '' }}" id="tel" name="tel" value="{{ $company->tel ?? '' }}" placeholder="請輸入公司電話(含國際碼)">
                                        @if ($errors->has('tel'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('tel') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fax"><span class="text-red">* </span>傳真(含+號與國碼)</label>
                                        <input type="text" class="form-control {{ $errors->has('fax') ? ' is-invalid' : '' }}" id="fax" name="fax" value="{{ $company->fax ?? '' }}" placeholder="請輸入公司傳真(含國際碼)">
                                        @if ($errors->has('fax'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('fax') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="address"><span class="text-red">* </span>中文地址</label>
                                        <input type="text" class="form-control {{ $errors->has('address') ? ' is-invalid' : '' }}" id="address" name="address" value="{{ $company->address ?? '' }}" placeholder="請輸入公司中文地址">
                                        @if ($errors->has('address'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('address') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="address_en"><span class="text-red">* </span>英文地址</label>
                                        <input type="text" class="form-control {{ $errors->has('address_en') ? ' is-invalid' : '' }}" id="address_en" name="address_en" value="{{ $company->address_en ?? '' }}" placeholder="請輸入公司英文地址">
                                        @if ($errors->has('address_en'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('address_en') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="service_tel"><span class="text-red">* </span>客服電話</label>
                                        <input type="text" class="form-control {{ $errors->has('service_tel') ? ' is-invalid' : '' }}" id="service_tel" name="service_tel" value="{{ $company->service_tel ?? '' }}" placeholder="請輸入客服電話(含國際碼)">
                                        @if ($errors->has('service_tel'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('service_tel') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="service_email"><span class="text-red">* </span>客服信箱</label>
                                        <input type="text" class="form-control {{ $errors->has('service_email') ? ' is-invalid' : '' }}" id="service_email" name="service_email" value="{{ $company->service_email ?? '' }}" placeholder="請輸入客服信箱">
                                        @if ($errors->has('service_email'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('service_email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="website"><span class="text-red">* </span>官網網址(不含https://)</label>
                                        <input type="text" class="form-control {{ $errors->has('website') ? ' is-invalid' : '' }}" id="website" name="website" value="{{ $company->website ?? '' }}" placeholder="請輸入官網網址">
                                        @if ($errors->has('website'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('website') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="url"><span class="text-red">* </span>官網網址(含https://)</label>
                                        <input type="text" class="form-control {{ $errors->has('url') ? ' is-invalid' : '' }}" id="url" name="url" value="{{ $company->url ?? '' }}" placeholder="請輸入官網網址(含https)">
                                        @if ($errors->has('url'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('url') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="fb_url"><span class="text-red">* </span>FB粉絲頁連結</label>
                                        <input type="text" class="form-control {{ $errors->has('fb_url') ? ' is-invalid' : '' }}" id="fb_url" name="fb_url" value="{{ $company->fb_url ?? '' }}" placeholder="請輸入FB粉絲頁連結">
                                        @if ($errors->has('fb_url'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('fb_url') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="Instagram_url"><span class="text-red">* </span>Instagram粉絲頁連結</label>
                                        <input type="text" class="form-control {{ $errors->has('Instagram_url') ? ' is-invalid' : '' }}" id="Instagram_url" name="Instagram_url" value="{{ $company->Instagram_url ?? '' }}" placeholder="請輸入Instagram粉絲頁連結">
                                        @if ($errors->has('Instagram_url'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('Instagram_url') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="Telegram_url"><span class="text-red">* </span>Telegram粉絲頁連結</label>
                                        <input type="text" class="form-control {{ $errors->has('Telegram_url') ? ' is-invalid' : '' }}" id="Telegram_url" name="Telegram_url" value="{{ $company->Telegram_url ?? '' }}" placeholder="請輸入Telegram粉絲頁連結">
                                        @if ($errors->has('Telegram_url'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('Telegram_url') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="line"><span class="text-red">* </span>Line ID</label>
                                        <input type="text" class="form-control {{ $errors->has('line') ? ' is-invalid' : '' }}" id="line" name="line" value="{{ $company->line ?? '' }}" placeholder="請輸入 Line ID">
                                        @if ($errors->has('line'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('line') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="wechat"><span class="text-red">* </span>微信 WeChat ID</label>
                                        <input type="text" class="form-control {{ $errors->has('wechat') ? ' is-invalid' : '' }}" id="wechat" name="wechat" value="{{ $company->wechat ?? '' }}" placeholder="請輸入微信 Wechat ID">
                                        @if ($errors->has('wechat'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('wechat') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>最後修改管理員</label>
                                        <br><span><b>{{ !empty($system->admin) ? $system->admin->name : '沒有姓名' }}</b> {{ $company->updated_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-white">
                        @if(in_array(isset($company) ? $menuCode.'M' : $menuCode.'N', explode(',',Auth::user()->power)))
                        <button type="button" class="btn btn-primary confirm-btn">{{ isset($company) ? '修改' : '新增' }}</button>
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
@endsection

@section('script')
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
@endsection

@section('JsValidator')
{!! JsValidator::formRequest('App\Http\Requests\Gate\CompanySettingsRequest', '#myform'); !!}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $(".confirm-btn").click(function() {
            if(confirm('本頁面資訊顯示於網站首頁及相關信件，請勿任意修改，\n請確認無誤後再按確定送出。')){
                $('#myform').submit();
            }
        });
    })(jQuery);
</script>
@endsection
