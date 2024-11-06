@extends('gate.layouts.master')

@section('title', '後台選單管理')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>後台主選單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('mainmenus') }}">後台主選單管理</a></li>
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
                            <div class="float-left">
                                <div class="input-group">
                                    <div class="input-group-append">
                                        @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                        <a href="{{ route('gate.mainmenus.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i>新增主選單</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="float-right">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="5%">順序</th>
                                        <th class="text-center" width="5%">選單代碼</th>
                                        <th class="text-center" width="5%">FA5圖示</th>
                                        <th class="text-left" width="15%">主選單名稱</th>
                                        <th class="text-center" width="10%">類型</th>
                                        <th class="text-center" width="10%">連結類型</th>
                                        <th class="text-left" width="20%">連結網址</th>
                                        <th class="text-center" width="7%">另開視窗</th>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="8%">啟用狀態</th>
                                        @endif
                                        @if(in_array($menuCode.'S',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="10%">排序</th>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="5%">刪除</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mainMenus as $mainMenu)
                                    <tr>
                                        <td class="text-center align-middle">{{ $mainMenu->sort }}</td>
                                        <td class="text-center align-middle">{{ $mainMenu->code }}</td>
                                        <td class="text-center align-middle">{!! $mainMenu->fa5icon !!}</td>
                                        <td class="text-left align-middle">
                                            <a href="{{ route('gate.mainmenus.show', $mainMenu->id ) }}">{{ $mainMenu->name }}</a>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if($mainMenu->type == 1)
                                            <span>後台</span>
                                            @elseif($mainMenu->type == 2)
                                            <span>商家後台</span>
                                            @else
                                            <span>中繼後台</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            @if($mainMenu->url_type == 1)
                                            <span class="text-info">內部連結</span>
                                            @elseif($mainMenu->url_type == 2)
                                            <span class="text-danger">外部連結</span>
                                            @else
                                            @if(count($mainMenu->subMenu) > 0)
                                            <a href="{{ url('mainmenus/submenu/'. $mainMenu->id) }}" class="btn btn-sm btn-primary">次選單管理</a>
                                            @endif
                                            @endif
                                        </td>
                                        <td class="text-left align-middle">
                                            @if($mainMenu->type == 1)
                                            @if($mainMenu->url_type == 1)
                                            <a href="{{ url($mainMenu->url) }}" {{ $mainMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ url($mainMenu->url) }}</a>
                                            @elseif($mainMenu->url_type == 2)
                                            <a href="{{ $mainMenu->url }}" {{ $mainMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ $mainMenu->url }}</a>
                                            @endif
                                            @else
                                            @if($mainMenu->url_type == 1)
                                            <a href="https://{{ env('VENDOR_DOMAIN').'/'.$mainMenu->url }}" {{ $mainMenu->open_window == 1 ? 'target="_blank"' : '' }}>https://{{ env('VENDOR_DOMAIN').'/'.$mainMenu->url }}</a>
                                            @elseif($mainMenu->url_type == 2)
                                            <a href="{{ $mainMenu->url }}" {{ $mainMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ $mainMenu->url }}</a>
                                            @endif
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            @if(!$mainMenu->url_type == 0)
                                                <form action="{{ url('mainmenus/open/' . $mainMenu->id) }}" method="POST">
                                                    @csrf
                                                    <input type="checkbox" name="open_window" value="{{ $mainMenu->open_window == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="是" data-off-text="否" data-off-color="secondary" data-on-color="success" {{ isset($mainMenu) ? $mainMenu->open_window == 1 ? 'checked' : '' : '' }}>
                                                </form>
                                            @endif
                                        </td>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ url('mainmenus/active/' . $mainMenu->id) }}" method="POST">
                                                @csrf
                                                <input type="checkbox" name="is_on" value="{{ $mainMenu->is_on == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="開" data-off-text="關" data-off-color="secondary" data-on-color="primary" {{ isset($mainMenu) ? $mainMenu->is_on == 1 ? 'checked' : '' : '' }}>
                                            </form>
                                        </td>
                                        @endif
                                        @if(in_array($menuCode.'S',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            @if($mainMenu->sort != 1)
                                            <a href="{{ url('mainmenus/sortup/' . $mainMenu->id) }}" class="text-navy">
                                                <i class="fas fa-arrow-alt-circle-up text-lg"></i>
                                            </a>
                                            @endif
                                            @if($mainMenu->type == 1)
                                            @if($mainMenu->sort != $type1Count)
                                            <a href="{{ url('mainmenus/sortdown/' . $mainMenu->id) }}" class="text-navy">
                                                <i class="fas fa-arrow-alt-circle-down text-lg"></i>
                                            </a>
                                            @endif
                                            @else
                                            @if($mainMenu->sort != $type2Count)
                                            <a href="{{ url('mainmenus/sortdown/' . $mainMenu->id) }}" class="text-navy">
                                                <i class="fas fa-arrow-alt-circle-down text-lg"></i>
                                            </a>
                                            @endif
                                            @endif
                                        </td>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.mainmenus.destroy', $mainMenu->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                        @endif
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

        $('.delete-btn').click(function (e) {
            if(confirm('請確認是否要刪除這筆資料?')){
                $(this).parents('form').submit();
            };
        });
    })(jQuery);
</script>
@endsection
