@extends('gate.layouts.master')

@section('title', '信件模板管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>信件模板管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('mailTemplates') }}">信件模板管理</a></li>
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
                                        <th class="text-left" width="25%">名稱</th>
                                        <th class="text-left" width="20%">代號</th>
                                        <th class="text-left" width="25%">檔案名稱</th>
                                        <th class="text-left" width="10%">管理員</th>
                                        <th class="text-left" width="10%">修改日期</th>
                                        <th class="text-center" width="10%">修改</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mailTemplates as $mailTemplate)
                                    <tr>
                                        <td class="text-left align-middle">{{ $mailTemplate->name }}</td>
                                        <td class="text-left align-middle">{{ $mailTemplate->file }}</td>
                                        <td class="text-left align-middle">{{ $mailTemplate->filename }}</td>
                                        <td class="text-left align-middle">{{ $mailTemplate->admin_name }}</td>
                                        <td class="text-left align-middle">{{ $mailTemplate->updated_at }}</td>
                                        <td class="text-center align-middle">
                                            @if(in_array($menuCode.'M' , explode(',',Auth::user()->power)))
                                                <a href="{{ route('gate.mailTemplates.show',$mailTemplate->id) }}" class="btn btn-sm btn-primary">修改</a>
                                            @endif
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
