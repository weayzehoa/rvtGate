<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>廠商採購單確認</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('vendor/Font-Awesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.custom.css') }}">
</head>

<body class="hold-transition login-page bg-navy" style="background-image: url({{ asset('img/bg.jpg') }});">
    {{-- alert訊息 --}}
    @include('gate.layouts.alert_message')
    <div class="login-box">
        <div class="login-logo">
            <a href="javascript:" class="text-yellow"><b>廠商採購單確認</b></a>
        </div>
        <div class="card">
            <div class="card-title p-2">
                <span class="text-primary text-left">商　　家：{{ $vendorName }}</span><br>
                <span class="text-primary text-left">採購單號：{{ $purchaseNos }}</span><br>
            </div>
            <div class="card-body login-card-body">
                <form action="{{ route('gate.vendorConfirm.store') }}" method="post">
                    <input id="id" type="hidden" name="vId" value="{{ $vId }}">
                    <input id="id" type="hidden" name="poId" value="{{ $poId }}">
                    <input id="id" type="hidden" name="no" value="{{ $no }}">
                    <input id="id" type="hidden" name="chk" value="{{ $chk }}">
                    @csrf
                    <div class="row">
                        <div class="col-8">
                            <span>請點擊右邊確認按鈕即可。</span>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block btn-submit">確認</button>
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
</body>

</html>
