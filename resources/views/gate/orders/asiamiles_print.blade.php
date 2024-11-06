@extends('gate.layouts.master')

@section('title', '查詢Asiamiles購買憑証')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>查詢Asiamiles購買憑証</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">後台管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('systemSettings') }}">查詢Asiamiles購買憑証</a></li>
                        <li class="breadcrumb-item active">查詢</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <form id="myform" action="{{ route('gate.asiamilesPrint.index') }}" method="GET">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">訂單查詢</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-10 offset-1">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="order_number"><span class="text-red">* </span>icarry訂單號</label>
                                        <input type="number" class="form-control {{ $errors->has('order_number') ? ' is-invalid' : '' }}" id="order_number" name="order_number" value="{{ $order_number ?? '' }}" placeholder="請輸入iCarry訂單號碼" required>
                                        @if ($errors->has('order_number'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('order_number') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    @if(!empty($url))
                                    <div class="form-group col-md-12">
                                        <label for="url"><span class="text-red">* </span>憑證網址</label>
                                        <input type="text" class="form-control" id="url" name="url" value="{{ $url }}">
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-white">
                        <button type="submit" class="btn btn-primary">查詢</button>
                        <a href="{{ url('dashboard') }}" class="btn btn-info">
                            <span class="text-white"><i class="fas fa-history"></i> 取消</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
