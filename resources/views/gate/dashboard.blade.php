@extends('gate.layouts.master')

@section('title', 'Dashboard')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>資訊看板</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div>
            <h1 class="text-danger">此為 Roger's CodingLab 開發測試站台，內容資料數據皆為測試資料，並非實際正確資料。</h1>
        </div>
    </section>
</div>
@endsection
