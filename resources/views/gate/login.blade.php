<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    @if(env('APP_ENV') == 'local')<title>iCarry {!! env('APP_ENV') == 'local' ? '測試用' : '' !!}中繼管理系統 - 登入</title>@else<title>iCarry 中繼管理系統 - 登入</title>@endif
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
            <a href="javascript:" class="text-yellow"><b>iCarry {!! env('APP_ENV') == 'local' ? '開發團隊測試用<br>' : '' !!}中繼管理系統</b></a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">請輸入 帳號 與 密碼</p>
                <form id="loginForm" action="{{ route('gate.login.submit') }}" method="post">
                    @csrf
                    <div class="input-group mb-3">
                        <input id="account" type="account" placeholder="請輸入帳號" class="form-control {{ $errors->has('account') ? ' is-invalid' : '' }}" name="account" value="{{ old('account') }}" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        @if ($errors->has('account'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('account') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="input-group mb-3">
                        <input id="password" type="password" placeholder="請輸入密碼" class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
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
                            <button type="button" class="btn btn-primary btn-block btn-submit">登入</button>
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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
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
            $(this).attr('disabled',true);
            grecaptcha.ready(function() {
                grecaptcha.execute('{{ env('NOCAPTCHA_SITEKEY') }}', { action: 'submit' }).then(function(token) {
                    if (token) {
                        $('input[name=g-recaptcha-response]').val(token);
                        $('#loginForm').submit();
                    }
                });
            });
        });
        $('body').keydown(function(e){
            if(e.keyCode ==13){
                $('.btn-submit').attr('disabled',true);
                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ env('NOCAPTCHA_SITEKEY') }}', { action: 'submit' }).then(function(token) {
                        if (token) {
                            $('input[name=g-recaptcha-response]').val(token);
                            $('#loginForm').submit();
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>
