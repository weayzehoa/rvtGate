@extends('gate.layouts.master')

@section('title', '多筆訂單統計')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>多筆訂單統計</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('orderCancel') }}">多筆訂單統計</a></li>
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
                                <div class="col-6">
                                </div>
                                <div class="col-6">
                                    <div class="float-right">
                                        <div class="input-group input-group-sm align-middle align-items-middle">
                                            <span class="badge badge-purple text-lg mr-2">總筆數：{{ !empty($orders) ? number_format($orders->total()) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($orders) > 0)
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left text-sm" width="20%">收件人</th>
                                        <th class="text-left text-sm" width="40%">收件地址</th>
                                        <th class="text-left text-sm" width="10%">預定交貨日</th>
                                        <th class="text-left text-sm" width="10%">重複訂單數</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orders as $order)
                                    <tr>
                                        <td class="text-left text-sm align-middle">{{ $order->receiver_name }}</td>
                                        <td class="text-left text-sm align-middle">{{ $order->receiver_address }}</td>
                                        <td class="text-left text-sm align-middle">{{ $order->book_shipping_date }}</td>
                                        <td class="text-left text-sm align-middle">
                                            <a href="{{ url('orders?receiver_name='.$order->receiver_name.'&receiver_address='.$order->receiver_address.'&book_shipping_date='.$order->book_shipping_date) }}"><span class="text-lg text-danger text-bold">{{ $order->count }}</span></a><br>
                                            @for($i=0;$i<count(explode(',',$order->orderData));$i++)
                                            <a href="{{ url('orders/'.explode('_',explode(',',$order->orderData)[$i])[0]) }}"><span> {{ explode('_',explode(',',$order->orderData)[$i])[1] }} </span></a>
                                            @endfor
                                        </td>
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
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($orders) ? number_format($orders->total()) : 0 }}</span>
                            </div>
                            @if(!empty($orders))
                            <div class="float-right">
                                @if(!empty($appends))
                                {{ $orders->appends($appends)->render() }}
                                @else
                                {{ $orders->render() }}
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('css')
@endsection
@section('script')
@endsection

@section('CustomScript')
@endsection
