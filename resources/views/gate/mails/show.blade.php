@extends('gate.layouts.master')

@section('title', '信件模板管理')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>信件模板管理</b><span class="badge badge-success text-sm">{{ $mailTemplate->order_number }}</span></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('mailTemplates') }}">信件模板管理</a></li>
                        <li class="breadcrumb-item active">修改</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <form id="myform" action="{{ route('gate.mailTemplates.update', $mailTemplate->id) }}" method="POST">
            <input type="hidden" name="_method" value="PATCH">
            @csrf
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title mr-1">{{ $mailTemplate->name }}</h3><span class="text-warning">{{ $mailTemplate->purchase_no }}</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-12 mb-2">
                                        @if($mailTemplate->file == 'purchaseMailBodyForNormal' || $mailTemplate->file == 'purchaseMailBodyForSpecialVendor' || $mailTemplate->file == 'purchaseMailBodyForNormalNew' || $mailTemplate->file == 'purchaseMailBodyForSpecialVendorNew')
                                        <span>主旨編輯參數說明</span><br>
                                        <span>今日日期八碼: <span> #^#today </span><br>
                                        <span>商家公司名稱: <span> #^#companyName </span><br>
                                        <span>商家名稱簡稱: <span> #^#vendorName </span><br>
                                        @elseif($mailTemplate->file == 'StatementMailBody')
                                        <span>主旨編輯參數說明</span><br>
                                        <span>今年年份4碼: <span> #^#year </span><br>
                                        <span>今年月份2碼: <span> #^#month </span><br>
                                        <span>商家公司名稱: <span> #^#companyName </span><br>
                                        <span>商家名稱簡稱: <span> #^#vendorName </span><br>
                                        @elseif($mailTemplate->file == 'NormalOrderMailBody')

                                        @elseif($mailTemplate->file == 'AsiamileOrderMailBody')

                                        @elseif($mailTemplate->file == 'AirportPickupOrderMailBody')

                                        @elseif($mailTemplate->file == 'AsiamileAirportPickupOrderMailBody')

                                        @elseif($mailTemplate->file == 'RefunMailBody')
                                        <span>訂單號碼: <span> #^#orderNumber </span><br>
                                        @endif
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">信件標題</span>
                                            </div>
                                            <input type="text" class="form-control" id="subject" name="subject" value="{{ $mailTemplate->subject }}" placeholder="請輸入信件標題">
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <span>內容編輯參數說明</span><br>
                                        @if($mailTemplate->file == 'purchaseMailBodyForNormal' || $mailTemplate->file == 'purchaseMailBodyForSpecialVendor')
                                            @if($mailTemplate->file == 'purchaseMailBodyForSpecialVendor')
                                            <span>今日日期參數字串: <span>((</span> <span>date('Ymd')</span> <span>))</span><br>
                                            @endif
                                            <span>廠商確認連結參數字串: <span>((</span> <span>$details['confirmUrl']</span> <span>))</span>
                                        </div>
                                        @elseif($mailTemplate->file == 'StatementMailBody')
                                        <span>對帳月份參數字串: <span>((</span> <span>$details['statementMonth']</span> <span>))</span><br>
                                        <span>對帳日期範圍參數字串: <span>((</span> <span>$details['statementDateRang']</span> <span>))</span><br>
                                        <span>開立年份參數字串: <span>((</span> <span>$details['statementYear']</span> <span>))</span><br>
                                        <span>開立月份參數字串: <span>((</span> <span>$details['statementMonth']</span> <span>))</span><br>
                                        <span>收發票期限參數字串: <span>((</span> <span>$details['getBefore']</span> <span>))</span><br>
                                        <span>付款日參數字串: <span>((</span> <span>$details['payDate']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'KlookOrderMailBody')
                                        <span>今年年份參數字串: <span>((</span> <span>date('Y')</span> <span>))</span><br>
                                        <span>外渠號碼參數字串: <span>((</span> <span>$details['order']['partner_order_number']</span> <span>))</span><br>
                                        <span>物流資料參數字串: <span>((</span> <span>$details['order']['shippingData']</span> <span>))</span><br>
                                        <span>訂單號碼參數字串: <span>((</span> <span>$details['order']['order_number']</span> <span>))</span><br>
                                        <span>訂購日期參數字串: <span>((</span> <span>$details['order']['create_time']</span> <span>))</span><br>
                                        <span>付款時間參數字串: <span>((</span> <span>$details['order']['pay_time']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'NormalOrderMailBody')
                                        <span>今年年份參數字串: <span>((</span> <span>date('Y')</span> <span>))</span><br>
                                        <span>訂單號碼參數字串: <span>((</span> <span>$details['order']['order_number']</span> <span>))</span><br>
                                        <span>訂購日期參數字串: <span>((</span> <span>$details['order']['create_time']</span> <span>))</span><br>
                                        <span>付款時間參數字串: <span>((</span> <span>$details['order']['pay_time']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'AsiamileOrderMailBody')
                                        <span>今年年份參數字串: <span>((</span> <span>date('Y')</span> <span>))</span><br>
                                        <span>訂單號碼參數字串: <span>((</span> <span>$details['order']['order_number']</span> <span>))</span><br>
                                        <span>訂購日期參數字串: <span>((</span> <span>$details['order']['create_time']</span> <span>))</span><br>
                                        <span>付款時間參數字串: <span>((</span> <span>$details['order']['pay_time']</span> <span>))</span><br>
                                        <span>憑證連結參數字串: <span>((</span> <span>$details['order']['am_print_link']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'AirportPickupOrderMailBody')
                                        <span>今年年份參數字串: <span>((</span> <span>date('Y')</span> <span>))</span><br>
                                        <span>訂單號碼參數字串: <span>((</span> <span>$details['order']['order_number']</span> <span>))</span><br>
                                        <span>訂購日期參數字串: <span>((</span> <span>$details['order']['create_time']</span> <span>))</span><br>
                                        <span>付款時間參數字串: <span>((</span> <span>$details['order']['pay_time']</span> <span>))</span><br>
                                        <span>提貨地點參數字串: <span>((</span> <span>$details['order']['airport_location']</span> <span>))</span><br>
                                        <span>提貨日期參數字串: <span>((</span> <span>$details['order']['receiver_time']</span> <span>))</span><br>
                                        <span>提貨時間參數字串: <span>((</span> <span>$details['order']['receiver_key_time']</span> <span>))</span><br>
                                        <span>取貨號碼參數字串: <span>((</span> <span>$details['order']['shipping_number']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'AsiamileAirportPickupOrderMailBody')
                                        <span>今年年份參數字串: <span>((</span> <span>date('Y')</span> <span>))</span><br>
                                        <span>訂單號碼參數字串: <span>((</span> <span>$details['order']['order_number']</span> <span>))</span><br>
                                        <span>訂購日期參數字串: <span>((</span> <span>$details['order']['create_time']</span> <span>))</span><br>
                                        <span>付款時間參數字串: <span>((</span> <span>$details['order']['pay_time']</span> <span>))</span><br>
                                        <span>提貨地點參數字串: <span>((</span> <span>$details['order']['airport_location']</span> <span>))</span><br>
                                        <span>提貨日期參數字串: <span>((</span> <span>$details['order']['receiver_time']</span> <span>))</span><br>
                                        <span>提貨時間參數字串: <span>((</span> <span>$details['order']['receiver_key_time']</span> <span>))</span><br>
                                        <span>取貨號碼參數字串: <span>((</span> <span>$details['order']['shipping_number']</span> <span>))</span><br>
                                        <span>憑證連結參數字串: <span>((</span> <span>$details['order']['am_print_link']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'RefunMailBody')
                                        <span>今年年份參數字串: <span>((</span> <span>date('Y')</span> <span>))</span><br>
                                        <span>退款日期參數字串: <span>((</span> <span>date('Y-m-d')</span> <span>))</span><br>
                                        <span>退款金額參數字串: <span>((</span> <span>$details['order']['refund']</span> <span>))</span><br>
                                        <span>訂單號碼參數字串: <span>((</span> <span>$details['order']['order_number']</span> <span>))</span><br>
                                        <span>訂購日期參數字串: <span>((</span> <span>$details['order']['create_time']</span> <span>))</span><br>
                                        <span>付款日期參數字串: <span>((</span> <span>$details['order']['pay_time']</span> <span>))</span><br>
                                        @elseif($mailTemplate->file == 'OrderPayFinishMailBody')
                                        <span>訂單號碼參數字串: <span>{order_number}</span><br>
                                        <span>訂購日期參數字串: <span>{order_create_time}</span><br>
                                        <span>付款時間參數字串: <span>{order_pay_time}</span><br>
                                        @endif
                                    </div>
                                    <div class="col-12 text-center mb-2">
                                        <textarea name="content">{{ $fileContent }}</textarea>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary">確認修改</button>
                                        <button type="button" id="preview" class="btn btn-success">預覽</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
@endsection

@section('modal')
{{-- 註記 Modal --}}
<div id="myModal" class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">{{ $mailTemplate->name }} 預覽</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div><span class="mb-2">信件主旨：</span><span id="myModalSubject"></span></div>
                <hr>
                <div id="myModalBody"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
{{-- iCheck for checkboxes and radio inputs --}}
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css') }}">
{{-- 時分秒日曆 --}}
<link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css') }}">
@endsection

@section('script')
{{-- Select2 --}}
<script src="{{ asset('vendor/select2/dist/js/select2.full.min.js') }}"></script>
{{-- Bootstrap Switch --}}
<script src="{{ asset('vendor/bootstrap-switch/dist/js/bootstrap-switch.min.js') }}"></script>
{{-- Jquery Validation Plugin --}}
<script src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{{-- 時分秒日曆 --}}
<script src="{{ asset('vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js') }}"></script>
<script src="{{ asset('vendor/jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-zh-TW.js') }}"></script>
{{-- Ckeditor 4.x --}}
<script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>
@endsection

@section('JsValidator')
{{-- {!! JsValidator::formRequest('App\Http\Requests\Admin\MainmenusRequest', '#myform'); !!} --}}
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        var editor = CKEDITOR.replace( 'content', {
            height : '40em',
            extraPlugins: 'font,justify,panelbutton,colorbutton,colordialog',
            removePlugins: 'exportpdf,templates,newpage',
            removeButtons: "Save,Preview,Print", // 不要的按鈕
            // removeButtons: "Save,Image,Scayt,PasteText,PasteFromWord,Outdent,Indent", // 不要的按鈕
        });
        editor.on( 'required', function( evt ) {
            editor.showNotification( '請輸入資料再按儲存.', 'warning' );
            evt.cancel();
        } );

        $('[data-toggle="popover"]').popover({
            html: true,
            sanitize: false,
        });

        //Initialize Select2 Elements
        $('.select2').select2();

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        // date time picker 設定
        $('.datetimepicker').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });
        $('.datepicker').datepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('.timepicker').timepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd",
        });

        $('#preview').click(function(){
            let subject = $('#subject').val();
            let d = new Date();
            let year = d.getFullYear();
            let month = d.getMonth()+1;
            let day = d.getDate();
            let today = d.getFullYear() + '-' +
                (month<10 ? '0' : '') + month + '-' +
                (day<10 ? '0' : '') + day;
            let TD = d.getFullYear() + '' +
                (month<10 ? '0' : '') + month + '' +
                (day<10 ? '0' : '') + day;
            let content = CKEDITOR.instances['content'].getData();
            subject = subject.replaceAll("#^#today",TD);
            subject = subject.replaceAll("#^#companyName","莊子茶葉有限公司");
            subject = subject.replaceAll("#^#vendorName","莊子茶房");
            subject = subject.replaceAll("#^#year",year);
            subject = subject.replaceAll("#^#month",month);
            content = content.replaceAll("&#39;","'");
            content = content.replaceAll("(( date('Y-m-d') ))",today);
            content = content.replaceAll("(( date('Ymd') ))",TD);
            content = content.replaceAll("(( date('Y') ))",year);
            content = content.replaceAll("(( $details['confirmUrl'] ))","https://icarry.me");
            content = content.replaceAll("(( $details['statementMonth'] ))","9");
            content = content.replaceAll("(( $details['statementYear'] ))","2022");
            content = content.replaceAll("(( $details['statementDateRang'] ))","2022-08-26 ~ 2022-09-25");
            content = content.replaceAll("(( $details['getBefore'] ))","2022-10-03");
            content = content.replaceAll("(( $details['payDate'] ))","2022-10-20");
            content = content.replaceAll("(( $details['refund'] ))","150");
            content = content.replaceAll("(( $details['order']['pay_time'] ))","2022-01-01 01:01:01");
            content = content.replaceAll("(( $details['order']['create_time'] ))","2022-01-01 01:01:01");
            content = content.replaceAll("(( $details['order']['order_number'] ))","20220101001234567");
            content = content.replaceAll("(( $details['order']['receiver_key_time'] ))","2022-01-06 00:00:00");
            content = content.replaceAll("(( $details['order']['receiver_time'] ))","01/06");
            content = content.replaceAll("(( $details['order']['airport_location'] ))","第一航廈-台灣宅配通櫃檯：位於 1 樓出境大廳（近 12 號報到櫃檯）");
            content = content.replaceAll("(( $details['order']['shipping_number'] ))","BC123456789,BC123456780");
            content = content.replaceAll("(( $details['order']['receiver_name'] ))","iCarry 我來寄");
            content = content.replaceAll("(( $details['order']['partner_order_number'] ))","KL1234567890");
            content = content.replaceAll("(( $details['order']['shippingData'] ))","台灣宅配通_1234567890,順豐_0987654321");
            content = content.replaceAll("(( $details['order']['am_print_link'] ))","https://icarry.me/asiamiles-print.php?o=5jhqDFjweiwemd5");
            $('#myModalBody').html('');
            $('#myModalBody').html('');
            $('#myModal').modal('show');
            $('#myModalSubject').html(subject);
            $('#myModalBody').html(content);
        });
    })(jQuery);
</script>
@endsection
