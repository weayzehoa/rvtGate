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
                    <h1 class="m-0 text-dark"><b>管理員帳號管理</b><small> (修改)</small></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('admins') }}">管理員帳號管理</a></li>
                        <li class="breadcrumb-item active">修改管理員帳號資料</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            @if(isset($admin))
            <form id="myform" action="{{ route('gate.admins.update', $admin->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
            @else
            <form id="myform" action="{{ route('gate.admins.store') }}" method="POST">
            @endif
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">{{ $admin->name ?? '' }} 帳號資料</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-9">
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="account"><span class="text-red">* </span>帳號</label>
                                        <input type="text" class="form-control {{ $errors->has('account') ? ' is-invalid' : '' }}" id="account" name="account" value="{{ $admin->account ?? '' }}" placeholder="請輸入帳號">
                                        @if ($errors->has('account'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('account') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="password"><span class="text-red">* </span>密碼</label>
                                        <input type="password" class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}" id="password" name="password" value="{{ $admin->password ?? '' }}" placeholder="請輸入密碼">
                                        @if ($errors->has('password'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="name"><span class="text-red">* </span>姓名</label>
                                        <input type="text" class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}" id="name" name="name" value="{{ $admin->name ?? '' }}" placeholder="請輸入姓名">
                                        @if ($errors->has('name'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="email"><span class="text-red">* </span>EMail</label>
                                        <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}" id="email" name="email" value="{{ $admin->email ?? '' }}" placeholder="請輸入電子郵件">
                                        @if ($errors->has('email'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="mobile"><span class="text-red">* </span>行動電話號碼</label>
                                        <input type="text" class="form-control {{ $errors->has('mobile') ? ' is-invalid' : '' }}" id="mobile" name="mobile" value="{{ $admin->mobile ?? '' }}" placeholder="請輸入行動電話號碼">
                                        @if ($errors->has('mobile'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('mobile') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-3">
                                        <label for="sms_vendor">簡訊商</label>
                                        <select type="text" class="form-control {{ $errors->has('sms_vendor') ? ' is-invalid' : '' }}" name="sms_vendor">
                                            <option value="">系統預設</option>
                                            <option value="aws" {{ isset($admin) && $admin->sms_vendor == 'aws' ? 'selected' : '' }}>AWS</option>
                                            <option value="mitake" {{ isset($admin) && $admin->sms_vendor == 'mitake' ? 'selected' : '' }}>三竹</option>
                                        </select>
                                        @if ($errors->has('sms_vendor'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('sms_vendor') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group col-3">
                                        <label for="email"><span class="text-red">* </span>兩階段驗證方法</label>
                                        <select type="text" class="form-control {{ $errors->has('sms_vendor') ? ' is-invalid' : '' }}" name="verify_mode">
                                            <option value="" {{ isset($admin) && $admin->verify_mode == '' ? 'selected' : '' }}>關閉</option>
                                            <option value="sms" {{ isset($admin) && $admin->verify_mode == 'sms' ? 'selected' : '' }}>簡訊</option>
                                            <option value="2fa" {{ isset($admin) && $admin->verify_mode == '2fa' ? 'selected' : '' }}>驗證器</option>
                                        </select>
                                        @if ($errors->has('sms_vendor'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('sms_vendor') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    @if(in_array($menuCode.'O' , explode(',',Auth::user()->power)))
                                    <div class="form-group col-md-3">
                                        <label for="active"><span class="text-red">* </span>狀態</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-danger d-inline mr-2">
                                                <input type="radio" id="active_lock" name="is_on" value="3" {{ isset($admin) ? $admin->lock_on >= 3 ? 'checked' : '' : 'checked' }}>
                                                <label for="active_lock">鎖定</label>
                                            </div>
                                            <div class="icheck-green d-inline mr-2">
                                                <input type="radio" id="active_pass" name="is_on" value="1" {{ isset($admin) ? $admin->is_on == 1 && $admin->lock_on < 3 ? 'checked' : '' : 'checked' }}>
                                                <label for="active_pass">啟用</label>
                                            </div>
                                            <div class="icheck-danger d-inline mr-2">
                                                <input type="radio" id="active_denie" name="is_on" value="0" {{ isset($admin) ? $admin->is_on == 0 ? 'checked' : '' : '' }}>
                                                <label for="active_denie">停權</label>
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <input type="hidden" name="is_on" value="{{ $admin->is_on }}">
                                    @endif
                                </div>
                            </div>
                            @if(isset($qrCodeUrl))
                            <div class="col-3">
                                <div class="row">
                                    <div class="form-group col-4">
                                        <label for="sms_vendor">驗證器條碼</label><br>{{ $qrCodeUrl }}
                                    </div>
                                    <div class="form-group col-8">
                                        <span>請使用手機至 Apple/Google Store 下載並安裝 Google Authenticator。使用 APP 掃描左邊 QRCode 建立 驗證碼產生器。</span>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="col-3">
                                <div class="row">
                                    <div class="form-group col-4">
                                        <label for="sms_vendor">驗證器條碼</label><br>請先建立帳號。
                                    </div>
                                    <div class="form-group col-8">

                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="card-primary card-outline col-12 mb-2"></div>
                            <div class="col-md-12">
                                <label for="">中繼後台權限設定</label>
                                <div class="icheck-primary">
                                    <input type="checkbox" id="checkAll" onclick="toggleSelect('#myform',this)">
                                    <label for="checkAll">選擇全部</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    @foreach($mainmenus as $mainmenu)
                                    @if($mainmenu->type == 3)
                                    <div class="col-md-4" id="mmid_{{ $mainmenu->id }}">
                                        <div class="icheck-primary col-md-12">
                                            <input type="checkbox" onclick="toggleSelect('#mmid_{{ $mainmenu->id }}',this)" name="mypower" value="{{ $mainmenu->code }}" id="mmchkbox{{ $mainmenu->id }}" {{ isset($admin) ? in_array($mainmenu->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}>
                                            <label for="mmchkbox{{ $mainmenu->id }}">{!! $mainmenu->fa5icon !!} {{ $mainmenu->name }}</label>
                                        </div>
                                        @if($mainmenu->url_type == 1)
                                        <div class="col-md-12">
                                            @foreach($powerActions as $powerAction)
                                            <input type="checkbox" name="mypower" value="{{ $mainmenu->code.$powerAction->code }}" {{ isset($admin) ? in_array($mainmenu->code.$powerAction->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}><span> {{ $powerAction->name }}</span>
                                            @endforeach
                                        </div>
                                        @elseif($mainmenu->power_action)
                                        <div class="col-md-12">
                                            @foreach($powerActions as $powerAction)
                                            @if(in_array($powerAction->code,explode(',',$mainmenu->power_action)))
                                            <input type="checkbox" name="mypower" value="{{ $mainmenu->code.$powerAction->code }}" {{ isset($admin) ? in_array($mainmenu->code.$powerAction->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}><span> {{ $powerAction->name }}</span>
                                            @endif
                                            @endforeach
                                        </div>
                                        @endif
                                        <div class="col-md-12">
                                            <ol>
                                                @foreach($mainmenu->submenu as $submenu)
                                                <div class="icheck-primary">
                                                    <input type="checkbox" onclick="toggleSelect('#smid_{{ $submenu->id }}',this)" name="mypower" value="{{ $submenu->code }}" id="smchkbox{{ $submenu->id }}" {{ isset($admin) ? in_array($submenu->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}>
                                                    <label for="smchkbox{{ $submenu->id }}">{!! $submenu->fa5icon !!} {{ $submenu->name }}</label>
                                                </div>
                                                <div class="col-md-12" id="smid_{{ $submenu->id }}">
                                                    <ol>
                                                        @foreach($powerActions as $powerAction)
                                                        @if(in_array($powerAction->code,explode(',',$submenu->power_action)))
                                                        <input type="checkbox" name="mypower" value="{{ $submenu->code.$powerAction->code }}" {{ isset($admin) ? in_array($submenu->code.$powerAction->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}><span> {{ $powerAction->name }}</span>
                                                        @endif
                                                        @endforeach
                                                    </ol>
                                                </div>
                                                @endforeach
                                            </ol>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="card-primary card-outline col-12 mb-2"></div>
                            <div class="col-md-12">
                                <label for="">Admin後台權限設定</label>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    @foreach($mainmenus as $mainmenu)
                                    @if($mainmenu->type == 1)
                                    <div class="col-md-4" id="mmid_{{ $mainmenu->id }}">
                                        <div class="icheck-primary col-md-12">
                                            <input type="checkbox" onclick="toggleSelect('#mmid_{{ $mainmenu->id }}',this)" name="mypower" value="{{ $mainmenu->code }}" id="mmchkbox{{ $mainmenu->id }}" {{ isset($admin) ? in_array($mainmenu->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}>
                                            <label for="mmchkbox{{ $mainmenu->id }}">{!! $mainmenu->fa5icon !!} {{ $mainmenu->name }}</label>
                                        </div>
                                        @if($mainmenu->url_type == 1)
                                        <div class="col-md-12">
                                            @foreach($powerActions as $powerAction)
                                            <input type="checkbox" name="mypower" value="{{ $mainmenu->code.$powerAction->code }}" {{ isset($admin) ? in_array($mainmenu->code.$powerAction->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}><span> {{ $powerAction->name }}</span>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div class="col-md-12">
                                            <ol>
                                                @foreach($mainmenu->submenu as $submenu)
                                                <div class="icheck-primary">
                                                    <input type="checkbox" onclick="toggleSelect('#smid_{{ $submenu->id }}',this)" name="mypower" value="{{ $submenu->code }}" id="smchkbox{{ $submenu->id }}" {{ isset($admin) ? in_array($submenu->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}>
                                                    <label for="smchkbox{{ $submenu->id }}">{!! $submenu->fa5icon !!} {{ $submenu->name }}</label>
                                                </div>
                                                <div class="col-md-12" id="smid_{{ $submenu->id }}">
                                                    <ol>
                                                        @foreach($powerActions as $powerAction)
                                                        @if(in_array($powerAction->code,explode(',',$submenu->power_action)))
                                                        <input type="checkbox" name="mypower" value="{{ $submenu->code.$powerAction->code }}" {{ isset($admin) ? in_array($submenu->code.$powerAction->code,explode(',',$admin->power)) ? 'checked' : '' : ''}}><span> {{ $powerAction->name }}</span>
                                                        @endif
                                                        @endforeach
                                                    </ol>
                                                </div>
                                                @endforeach
                                            </ol>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-white">
                        @if(in_array(isset($admin) ? $menuCode.'M' : $menuCode.'N', explode(',',Auth::user()->power)))
                        <button id="modifyBtn" type="button" class="btn btn-primary">{{ isset($admin) ? '修改' : '新增' }}</button>
                        @endif
                        <a href="{{ url('admins') }}" class="btn btn-info">
                            <span class="text-white"><i class="fas fa-history"></i> 取消</span>
                        </a>
                    </div>
                </div>
            </form>
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
{!! JsValidator::formRequest('App\Http\Requests\Gate\AdminsUpdateRequest', '#myform'); !!}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('#modifyBtn').click(function(){
            let form = $('#myform');
            $('#modifyBtn').attr('disabled',true);
            let power = [];
            $.each($("input[name='mypower']:checked"), function(){
                power.push($(this).val());
                $("input[name='mypower']").remove();
            });
            form.append($('<input type="hidden" name="power" value="'+power+'">'));
            form.submit();
        });
    })(jQuery);

    function chkall(input1, input2) {
        var objForm = document.forms[input1];
        var objLen = objForm.length;
        for (var iCount = 0; iCount < objLen; iCount++) {
            var objChk = $(objForm.elements[iCount]);
            if (!objChk.hasClass("toggle-switch") && !objChk.hasClass("disabled")) { //去除 上線,disabled
                if (input2.checked == true) {
                    if (objForm.elements[iCount].type == "checkbox") {
                        objForm.elements[iCount].checked = true;
                    }
                } else {
                    if (objForm.elements[iCount].type == "checkbox") {
                        objForm.elements[iCount].checked = false;
                    }
                }
            }
        }
    }

    function toggleSelect(parentId, trigger) {
        var option_list = document.querySelectorAll(`${parentId} input`);
        if (trigger.checked) {
            option_list.forEach((el) => {
                if (!el.classList.contains("disabled") && el.getAttribute("type") !== "radio") {
                    el.checked = true;
                }
            });
        } else {
            option_list.forEach((el) => {
                if (!el.classList.contains("disabled") && el.getAttribute("type") !== "radio") {
                    el.checked = false;
                }
            });
        }
    }

</script>
@endsection
