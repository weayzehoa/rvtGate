@extends('gate.layouts.master')

@section('title', '排程管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>排程管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('schedules') }}">排程管理</a></li>
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
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left" width="20%">名稱</th>
                                        <th class="text-left" width="20%">代號</th>
                                        <th class="text-left" width="15%">循環頻率</th>
                                        <th class="text-center" width="10%">啟用狀態</th>
                                        <th class="text-center" width="10%">立即執行</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($schedules as $schedule)
                                    <tr>
                                        <td class="text-left align-middle">{{ $schedule->name }}</td>
                                        <td class="text-left align-middle">{{ $schedule->code }}</td>
                                        <td class="text-left align-middle">
                                            <form action="{{ route('gate.schedules.update', $schedule->id) }}" method="POST">
                                                <input type="hidden" name="_method" value="PATCH">
                                                @csrf
                                                <select class="form-control" name="frequency" onchange="submit(this)" {{ in_array($menuCode.'M' , explode(',',Auth::user()->power)) ? '' : 'disabled' }} {{ $schedule->code == 'OrderInvoiceUpdate' ? 'disabled' : '' }} {{ $schedule->code == 'VendorShippingSellData' ? 'disabled' : '' }}  {{ $schedule->code == 'productPriceChange' ? 'disabled' : '' }}>
                                                    {{-- <option value="everyMinute" {{ $schedule->frequency == 'everyMinute' ? 'selected' : '' }}>每分鐘</option> --}}
                                                    <option value="everyFiveMinutes" {{ $schedule->frequency == 'everyFiveMinutes' ? 'selected' : '' }}>每五分鐘</option>
                                                    <option value="everyTenMinutes" {{ $schedule->frequency == 'everyTenMinutes' ? 'selected' : '' }}>每十分鐘</option>
                                                    <option value="everyFifteenMinutes" {{ $schedule->frequency == 'everyFifteenMinutes' ? 'selected' : '' }}>每十五分鐘</option>
                                                    <option value="everyThirtyMinutes" {{ $schedule->frequency == 'everyThirtyMinutes' ? 'selected' : '' }}>每三十分鐘</option>
                                                    <option value="hourly" {{ $schedule->frequency == 'hourly' ? 'selected' : '' }}>每小時</option>
                                                    <option value="everyThreeHours" {{ $schedule->frequency == 'everyThreeHours' ? 'selected' : '' }}>每三小時</option>
                                                    <option value="everySixHours" {{ $schedule->frequency == 'everySixHours' ? 'selected' : '' }}>每六小時</option>
                                                    <option value="daily" {{ $schedule->frequency == 'daily' ? 'selected' : '' }}>每日午夜</option>
                                                    <option value="weekly" {{ $schedule->frequency == 'weekly' ? 'selected' : '' }}>每週六午夜</option>
                                                    <option value="monthly" {{ $schedule->frequency == 'monthly' ? 'selected' : '' }}>每月第一天的午夜</option>
                                                    <option value="quarterly" {{ $schedule->frequency == 'quarterly' ? 'selected' : '' }}>每季第一天的午夜</option>
                                                    <option value="yearly" {{ $schedule->frequency == 'yearly' ? 'selected' : '' }}>每年第一天的午夜</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="text-center align-middle">
                                            <form action="{{ url('schedules/active/' . $schedule->id) }}" method="POST">
                                                @csrf
                                                <input type="checkbox" name="is_on" value="{{ $schedule->is_on == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="開" data-off-text="關" data-off-color="secondary" data-on-color="primary" {{ isset($schedules) ? $schedule->is_on == 1 ? 'checked' : '' : '' }} {{ in_array($menuCode.'O' , explode(',',Auth::user()->power)) ? '' : 'disabled' }}>
                                            </form>
                                        </td>
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.schedules.execNow',$schedule->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" {{ in_array($menuCode.'E' , explode(',',Auth::user()->power)) ? '' : 'disabled' }}>立即執行</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
@endsection

@section('script')
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        $('input[data-bootstrap-switch]').on('switchChange.bootstrapSwitch', function (event, state) {
            $(this).parents('form').submit();
        });
    })(jQuery);
</script>
@endsection
