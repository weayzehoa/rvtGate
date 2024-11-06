<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>iCarry 測試用後台管理系統 | 變更密碼</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('vendor/Font-Awesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.custom.css') }}">
</head>

@if(env('NOCAPTCHA_HIDE'))
<style>
    .grecaptcha-badge {
        visibility: hidden;
    }
</style>
@endif

<body class="hold-transition login-page bg-navy" style="background-image: url({{ asset('img/bg.jpg') }});">
    {{-- alert訊息 --}}
    @include('gate.layouts.alert_message')
    <div class="login-box">
        <div class="login-logo">
            <a href="javascript:" class="text-yellow"><b>iCarry {!! env('APP_ENV') == 'local' ? '開發團隊測試用<br>' : '' !!}密碼變更</b></a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="text-left">您的密碼已經三個月未變更，強制變更密碼(90天到期，若不變更無法進入後台)。</p>
                <form id="myform" action="{{ route('gate.passwordChange.submit') }}" method="post" autocomplete="off">
                    @csrf
                    <div class="form-group">
                        <label for="account">帳　號</label>
                        <input id="account" type="text" placeholder="請輸入帳號" class="bg-white form-control {{ $errors->has('account') ? ' is-invalid' : '' }}" name="account" value="{{ old('account') ?? '' }}" required autofocus>
                        @if ($errors->has('account'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('account') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="oldpass">舊密碼</label>
                        <input id="oldpass" type="password" placeholder="請輸入舊密碼" class="bg-white form-control {{ $errors->has('oldpass') ? ' is-invalid' : '' }}" name="oldpass" required autocomplete="off" onfocus="this.removeAttribute('readonly');" readonly>
                        @if ($errors->has('oldpass'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('oldpass') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="newpass">新密碼</label>
                        <input id="newpass" type="password" placeholder="請輸入新密碼" class="bg-white form-control {{ $errors->has('newpass') ? ' is-invalid' : '' }}" name="newpass" required autocomplete="off" onfocus="this.removeAttribute('readonly');" readonly>
                        @if ($errors->has('newpass'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('newpass') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="newpass_confirmation">新密碼確認</label>
                        <input id="newpass_confirmation" type="password" placeholder="請再次輸入新密碼" class="bg-white form-control {{ $errors->has('newpass_confirmation') ? ' is-invalid' : '' }}" name="newpass_confirmation" required autocomplete="off" onfocus="this.removeAttribute('readonly');" readonly>
                        @if ($errors->has('newpass_confirmation'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('newpass_confirmation') }}</strong>
                        </span>
                        @endif
                    </div>
                    {{-- Google reCAPTCHA v3 --}}
                    <div class="col-12 mb-3">
                        {!! no_captcha()->input() !!}
                        {!! no_captcha()->script() !!}
                    </div>
                    <div class="row">
                        <div class="col-4">

                        </div>
                        <div class="col-4">

                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-primary btn-block btn-submit">送出</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- 背景動畫使用區塊 --}}
    <div id="particles-js"></div>
    {{-- REQUIRED SCRIPTS --}}
    <script src="{{ asset('vendor/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/adminlte.min.js') }}"></script>
    {{-- VincentGarreau/particles.js --}}
    <script src="{{ asset('vendor/particles.js/particles.min.js') }}"></script>
    <script src="{{ asset('js/admin.common.js') }}"></script>
    {{-- 背景動畫 --}}
    <script>
        particlesJS.load('particles-js', "{{ asset('./js/particles.json') }}");
    </script>
    {{-- Google reCAPTCHA v3 --}}
    <script>
        $('.btn-submit').click(function(){
            grecaptcha.ready(function() {
                grecaptcha.execute('{{ env('NOCAPTCHA_SITEKEY') }}', { action: 'submit' }).then(function(token) {
                    if (token) {
                        $('input[name=g-recaptcha-response]').val(token);
                        $('#myform').submit();
                    }
                });
            });
        });
    </script>
    {{-- Jquery Validation Plugin --}}
    <script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
    {!! JsValidator::formRequest('App\Http\Requests\Gate\PasswordChangeRequest', '#myform'); !!}
</body>

</html>
