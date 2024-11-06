@extends('gate.layouts.master')

@section('title', '員工打卡紀錄')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            {{-- alert訊息 --}}
            @include('gate.layouts.alert_message')
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>員工打卡紀錄</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('attendances') }}">員工打卡紀錄</a></li>
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
                                @if(in_array($menuCode.'IM',explode(',',Auth::user()->power)))
                                <button type="button" class="btn btn-sm btn-primary btn-import">匯入</button>
                                @endif
                            </div>
                            <div class="float-right">
                                {{-- <form action="{{ url('employees') }}" method="GET" class="form-inline" role="search">
                                    選擇：
                                    <div class="form-group-sm">
                                        <select class="form-control form-control-sm" name="is_on" onchange="submit(this)">
                                            <option value="2" {{ $is_on == 2 ? 'selected' : '' }}>所有狀態 ({{ $totalAdmins }})</option>
                                            <option value="1" {{ $is_on == 1 ? 'selected' : '' }}>啟用 ({{ $totalEnable }})</option>
                                            <option value="0" {{ $is_on == 0 ? 'selected' : '' }}>停用 ({{ $totalDisable }})</option>
                                        </select>
                                        <select class="form-control form-control-sm" name="list" onchange="submit(this)">
                                            <option value="15" {{ $list == 15 ? 'selected' : '' }}>每頁 15 筆</option>
                                            <option value="30" {{ $list == 30 ? 'selected' : '' }}>每頁 30 筆</option>
                                            <option value="50" {{ $list == 50 ? 'selected' : '' }}>每頁 50 筆</option>
                                            <option value="100" {{ $list == 100 ? 'selected' : '' }}>每頁 100 筆</option>
                                        </select>
                                        <input type="search" class="form-control form-control-sm" name="keyword" value="{{ isset($keyword) ? $keyword : '' }}" placeholder="輸入關鍵字搜尋" title="搜尋姓名、帳號及Email" aria-label="Search">
                                        <button type="submit" class="btn btn-sm btn-info" title="搜尋姓名、帳號及Email">
                                            <i class="fas fa-search"></i>
                                            搜尋
                                        </button>
                                    </div>
                                </form> --}}
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left" width="15%">工號</th>
                                        <th class="text-left" width="15%">姓名</th>
                                        <th class="text-left" width="15%">工作日期</th>
                                        <th class="text-left" width="10%">星期?</th>
                                        <th class="text-left" width="15%">打卡時間</th>
                                        <th class="text-left" width="15%">打卡結果</th>
                                        <th class="text-left" width="15%">打卡備註</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($attendances as $attendance)
                                    <tr>
                                        <td class="text-left align-middle">{{ $attendance->employee_no }}</td>
                                        <td class="text-left align-middle">{{ !empty($attendance->employee) ? $attendance->employee->name : null }}</td>
                                        <td class="text-left align-middle">{{ $attendance->work_date }}</td>
                                        <td class="text-left align-middle">
                                            @if($attendance->week == 0)
                                            日
                                            @elseif($attendance->week == 1)
                                            一
                                            @elseif($attendance->week == 2)
                                            二
                                            @elseif($attendance->week == 3)
                                            三
                                            @elseif($attendance->week == 4)
                                            四
                                            @elseif($attendance->week == 5)
                                            五
                                            @elseif($attendance->week == 6)
                                            六
                                            @endif
                                        </td>
                                        <td class="text-left align-middle">{{ $attendance->chk_time }}</td>
                                        <td class="text-left align-middle">{{ $attendance->result }}</td>
                                        <td class="text-left align-middle">{{ $attendance->memo }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="float-left">
                                <span class="badge badge-primary text-lg ml-1">總筆數：{{ !empty($attendances) ? number_format($attendances->total()) : 0 }}</span>
                            </div>
                            <div class="float-right">
                                @if(isset($appends))
                                {{ !empty($attendances) ? (!empty($appends) ? $attendances->appends($appends)->render() : '') : '' }}
                                @else
                                {{ $attendances->render() }}
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
@section('modal')
{{-- 匯入Modal --}}
<div id="importModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">請選擇匯入檔案</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  id="importForm" action="{{ url('attendances/import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" id="filename" name="filename" class="custom-file-input" required autocomplete="off">
                                <label class="custom-file-label" for="filename">瀏覽選擇EXCEL檔案</label>
                            </div>
                            <div class="input-group-append">
                                <button id="importBtn" type="button" class="btn btn-md btn-primary btn-block">上傳</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div>
                    <span class="text-danger">注意! 請選擇正確的檔案並填寫正確的資料格式匯入，否則將造成資料錯誤，若不確定格式，請參考 <a href="./sample/員工打卡資料範本.xlsx" target="_blank">員工打卡資料範本</a> ，製作正確的檔案。</span>
                </div>
            </div>
        </div>
    </div>
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

        $('.btn-import').click(function(){
            $('#importModal').modal('show');
        });

        $('#importBtn').click(function(){
            let form = $('#importForm');
            let file = $('#filename')[0];
            if(file.value){
                $('#importBtn').attr('disabled',true);
                form.submit();
            }else{
                alert('請選擇EXCEL檔案');
            }
        });

        $('input[type="file"]').change(function(e){
            let file = e.target.files[0];
            let fileName = '瀏覽選擇EXCEL檔案';
            file ? fileName = e.target.files[0].name : '';
            $('.custom-file-label').html(fileName);
        });

    })(jQuery);
</script>
@endsection
