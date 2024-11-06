@extends('gate.layouts.master')

@section('title', '發票開立失敗記錄表')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>發票開立失敗記錄表</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('invoiceFailure') }}">發票開立失敗記錄表</a></li>
                        <li class="breadcrumb-item active">清單</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-9">
                                    <div class="float-left d-flex align-items-center">
                                        <button id="showForm" class="btn btn-sm btn-success mr-2" title="隱藏欄位查詢">隱藏欄位查詢</button>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($failures) ? number_format($failures->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="search" class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">使用欄位查詢</h3>
                                </div>
                                <form id="searchForm" role="form" action="{{ url('invoiceFailure') }}" method="get">
                                    <div class="card-body">
                                        <div class="row col-10 offset-1">
                                            <div class="col-6 mt-2">
                                                <label class="control-label" for="order_number">訂單單號:</label>
                                                <input type="text" class="form-control" id="order_number" name="order_number" placeholder="填寫訂單單號" value="{{ isset($order_number) ? $order_number ?? '' : '' }}" autocomplete="off">
                                            </div>
                                            <div class="col-4 mt-2">
                                                <label class="control-label" for="list">每頁筆數:</label>
                                                <select class="form-control" id="list" name="list">
                                                    <option value="50" {{ $list == 50 ? 'selected' : '' }}>50</option>
                                                    <option value="100" {{ $list == 100 ? 'selected' : '' }}>100</option>
                                                    <option value="300" {{ $list == 300 ? 'selected' : '' }}>300</option>
                                                    <option value="500" {{ $list == 500 ? 'selected' : '' }}>500</option>
                                                </select>
                                            </div>
                                            <div class="col-2 mt-2">
                                                <label class="control-label" for="list">　</label><br>
                                                <button type="submit" class="btn btn-primary">查詢</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @if(count($failures) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="8%">訂單編號</th>
                                        <th class="text-left text-sm" width="8%">購買者</th>
                                        <th class="text-left text-sm" width="8%">開立錯誤訊息</th>
                                        <th class="text-right text-sm" width="8%">開立失敗次數</th>
                                        <th class="text-right text-sm" width="5%">最後開立時間</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($failures as $failure)
                                    <tr>
                                        <td class="text-left text-sm align-middle"><a href="{{ url('orders/'.$failure->order_id) }}" target="_blank">{{ $failure->order_number }}</a></td>
                                        <td class="text-left text-sm align-middle">{{ $failure->buyer_name }}</td>
                                        <td class="text-left text-sm align-middle">{{ $failure->get_json }}</td>
                                        <td class="text-right text-sm align-middle">{{ $failure->times }}</td>
                                        <td class="text-right text-sm align-middle">{{ $failure->create_time }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <h3>尚無資料</h3>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($failures) ? number_format($failures->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(count($failures) > 0)
                                @if(isset($appends))
                                {{ $failures->appends($appends)->render() }}
                                @else
                                {{ $failures->render() }}
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $('#showForm').click(function(){
            let text = $('#showForm').html();
            $('#search').toggle();
            text == '使用欄位查詢' ? $('#showForm').html('隱藏欄位查詢') : $('#showForm').html('使用欄位查詢');
        });
    })(jQuery);
</script>
@endsection
