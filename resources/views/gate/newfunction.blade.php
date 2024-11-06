@extends('gate.layouts.master')

@section('title', 'newFunction')

@section('content')

<div class="content-wrapper">
    <div class="content-header">
        {{-- alert訊息 --}}
        @include('gate.layouts.alert_message')
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><b>功能開發測試</b></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('gate.dashboard') }}">中繼管理系統</a></li>
                        <li class="breadcrumb-item active"><a href="{{ url('newFunction') }}">功能開發測試</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <form id="myform" action="{{ route('gate.newFunction.store') }}" method="POST">
            @csrf
            <input type="hidden" name="cate" value="product">
            {{-- <textarea id="specification" name="specification"></textarea><button type="submit" class="btn btn-primary">新增</button> --}}
            <div>商品類別更新功能</div>
            <div class="form-group col-6">
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" id="filename" name="filename" class="custom-file-input" required autocomplete="off">
                        <label class="custom-file-label" for="filename">瀏覽選擇EXCEL檔案</label>
                    </div>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-md btn-primary">上傳</button>
                    </div>
                </div>
            </div>
        </form>
        <form id="myform2" action="{{ route('gate.newFunction.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="cate" value="vendor">
            {{-- <textarea id="specification" name="specification"></textarea><button type="submit" class="btn btn-primary">新增</button> --}}
            <div>商家類別更新功能</div>
            <div class="form-group col-6">
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" id="filename" name="filename" class="custom-file-input" required autocomplete="off">
                        <label class="custom-file-label" for="filename">瀏覽選擇EXCEL檔案</label>
                    </div>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-md btn-primary">上傳</button>
                    </div>
                </div>
            </div>
        </form>
        {{-- <form id="myform" action="{{ route('gate.newFunction.store') }}" method="POST">
            @csrf
            <!-- Place the first <script> tag in your HTML's <head> -->
            <script src="https://cdn.tiny.cloud/1/174xi1yie9lc6e0uxs89d9xg23tnvys7fjrgmh26ft37scu1/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
            <script>
                tinymce.init({
                    selector: 'textarea',
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed linkchecker a11ychecker tinymcespellchecker permanentpen powerpaste advtable advcode editimage advtemplate ai mentions tinycomments tableofcontents footnotes mergetags autocorrect typography inlinecss markdown',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat | language',
                    tinycomments_mode: 'embedded',
                    tinycomments_author: 'Author name',
                    mergetags_list: [
                    { value: 'First.Name', title: 'First Name' },
                    { value: 'Email', title: 'Email' },
                    ],
                    content_langs: [
                    { title: 'English', code: 'en' },
                    { title: 'Spanish', code: 'es' },
                    { title: 'French', code: 'fr' },
                    { title: 'German', code: 'de' },
                    { title: 'Portuguese', code: 'pt' },
                    { title: 'Chinese', code: 'zh' }
                    ],
                    ai_request: (request, respondWith) => respondWith.string(() => Promise.reject("See docs to implement AI Assistant")),
                });
            </script>
            <textarea name="test">
                Welcome to TinyMCE!
            </textarea>
            <button type="submit" class="btn btn-primary">新增</button>
        </form> --}}
        {{-- <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($data->orderNew) }}</h3>
                        <p>新的訂單！(已付款)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <a href="{{ url('orders?status=1') }}" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($data->orderCollect) }}</h3>
                        <p>集貨中訂單！</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-luggage-cart"></i>
                    </div>
                    <a href="{{ url('orders?status=2') }}" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($data->productWait) }}</h3>
                        <p>待審核商品！</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-product-hunt"></i>
                    </div>
                    <a href="{{ url('products?status=2') }}" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($data->productReplenishment) }}</h3>
                        <p>待補貨商品！</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-product-hunt"></i>
                    </div>
                    <a href="{{ url('products?status=-2') }}" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($data->productNeedReplenishment) }}</h3>
                        <p>低於安全庫存商品！</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-product-hunt"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ number_format($data->productStop) }}</h3>
                        <p>停售中商品！</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-product-hunt"></i>
                    </div>
                    <a href="{{ url('products?status=-3') }}" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ number_format($data->productPause) }}</h3>
                        <p>廠商停售中商品！</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-product-hunt"></i>
                    </div>
                    <a href="{{ url('products?status=-3') }}" class="small-box-footer">
                        查看明細 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div> --}}
    </section>
</div>
@endsection

@section('css')
@endsection

@section('script')
{{-- Ckeditor 4.x --}}
<script src="{{ asset('vendor/ckeditor/ckeditor.js') }}"></script>
@endsection

@section('CustomScript')
<script>
    (function($) {
        "use strict";
        var editor = CKEDITOR.replace( 'specification', {
            height : '40em',
            extraPlugins: 'font,justify,panelbutton,colorbutton,colordialog,editorplaceholder',
            editorplaceholder: '請填寫詳細說明描述商品或規格...',
            // removeButtons: "Image,Scayt,PasteText,PasteFromWord,Outdent,Indent", // 不要的按鈕
        });

    })(jQuery);

</script>
@endsection
