<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>qrCode</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
</head>

<body class="hold-transition login-page bg-white">
    <div class="login-box">
        <div class="card-body">
            <div class="text-center">{{ $qrCodeUrl }}</div>
            {{-- <div class="text-center"><span class="text-bold text-lg">{{ $serialNo }}</span></div> --}}
            <div class="text-center"><span class="text-bold text-lg">{{ $name }}</span></div>
        </div>
    </div>
</body>

</html>
