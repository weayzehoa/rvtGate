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
                    <h1 class="m-0 text-dark"><b>後台次選單管理</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('subMenus') }}">後台次選單管理</a></li>
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
                                @if(in_array($menuCode.'N',explode(',',Auth::user()->power)))
                                <a href="{{ route('gate.submenus.edit', $subMenus[0]->mainmenu->id ) }}" class="btn btn-sm btn-primary mr-2"><i class="fas fa-plus mr-1"></i>新增次選單</a>
                                @endif
                                <a href="{{ url('mainmenus') }}" class="btn btn-sm btn-info"><i class="fas fa-history mr-1"></i>返回主選單</a>
                            </div>
                            <div class="float-right">
                            </div>
                        </div>
                        <div class="card-header">
                            <div class="float-left">
                                <p>所屬主選單：{{ $subMenus[0]->mainmenu->name }}</p>
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
                                        <th class="text-left" width="18%">次選單名稱</th>
                                        <th class="text-center" width="15%">連結類型</th>
                                        <th class="text-left" width="22%">連結網址</th>
                                        <th class="text-center" width="7%">另開視窗</th>
                                        @if(in_array($menuCode.'O',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="8%">啟用狀態</th>
                                        @endif
                                        @if(in_array($menuCode.'S',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="8%">排序</th>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <th class="text-center" width="7%">刪除</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subMenus as $subMenu)
                                    <tr>
                                        <td class="text-center align-middle">{{ $subMenu->sort }}</td>
                                        <td class="text-center align-middle">{{ $subMenu->code }}</td>
                                        <td class="text-center align-middle">{!! $subMenu->fa5icon !!}</td>
                                        <td class="text-left align-middle">
                                            <a href="{{ route('gate.submenus.show', $subMenu->id ) }}">{{ $subMenu->name }}</a>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if($subMenu->url_type == 1)
                                            <span class="text-info">內部連結</span>
                                            @elseif($subMenu->url_type == 2)
                                            <span class="text-danger">外部連結</span>
                                            @endif
                                        </td>
                                        <td class="text-left align-middle">
                                            @if($subMenu->mainmenu->type == 1)
                                            @if($subMenu->url_type == 1)
                                            <a href="{{ $subMenu->url ? url($subMenu->url) : 'javascript:' }}" {{ $subMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ $subMenu->url ? url($subMenu->url) : '' }}</a>
                                            @elseif($subMenu->url_type == 2)
                                            <a href="{{ $subMenu->url }}" {{ $subMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ $subMenu->url }}</a>
                                            @endif
                                            @else
                                            @if($subMenu->url_type == 1)
                                            <a href="{{ $subMenu->url ? 'https://'.env('VENDOR_DOMAIN').'/'.$subMenu->url : 'javascript:' }}" {{ $subMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ $subMenu->url ? 'https://'.env('VENDOR_DOMAIN').'/'.$subMenu->url : '' }}</a>
                                            @elseif($subMenu->url_type == 2)
                                            <a href="{{ $subMenu->url }}" {{ $subMenu->open_window == 1 ? 'target="_blank"' : '' }}>{{ $subMenu->url }}</a>
                                            @endif
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            @if(!$subMenu->url_type == 0)
                                                <form action="{{ url('submenus/open/' . $subMenu->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    <input type="checkbox" name="open_window" value="{{ $subMenu->open_window == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="開" data-off-text="關" data-off-color="secondary" data-on-color="success" {{ isset($subMenu) ? $subMenu->open_window == 1 ? 'checked' : '' : '' }}>
                                                </form>
                                            @endif
                                        </td>
                                        @if(in_array($menuCode.'S',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ url('submenus/active/' . $subMenu->id) }}" method="POST">
                                                @csrf
                                                <input type="checkbox" name="is_on" value="{{ $subMenu->is_on == 1 ? 0 : 1 }}" data-bootstrap-switch data-on-text="開" data-off-text="關" data-off-color="secondary" data-on-color="primary" {{ isset($subMenu) ? $subMenu->is_on == 1 ? 'checked' : '' : '' }}>
                                            </form>
                                        </td>
                                        @endif
                                        @if(in_array($menuCode.'S',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            @if($subMenu->sort != 1)
                                            <a href="{{ url('submenus/sortup/' . $subMenu->id) }}" class="text-navy">
                                                <i class="fas fa-arrow-alt-circle-up text-lg"></i>
                                            </a>
                                            @endif
                                            @if($subMenu->sort != count($subMenus))
                                            <a href="{{ url('submenus/sortdown/' . $subMenu->id) }}" class="text-navy">
                                                <i class="fas fa-arrow-alt-circle-down text-lg"></i>
                                            </a>
                                            @endif
                                        </td>
                                        @endif
                                        @if(in_array($menuCode.'D',explode(',',Auth::user()->power)))
                                        <td class="text-center align-middle">
                                            <form action="{{ route('gate.submenus.destroy', $subMenu->id) }}" method="POST">
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
