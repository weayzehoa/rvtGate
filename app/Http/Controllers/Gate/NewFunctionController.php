<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\NewFunctionImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryVendor as VendorDB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DB;
use App\Traits\UniversalFunctionTrait;

class NewFunctionController extends Controller
{
    use UniversalFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(auth('gate')->user()->id != 40){ //非Roger不能讀取
            return view()->make('gate.dashboard');
        }else{
            // $postDataStr = '{"out_trad_no":"12345a","time_end":"20241016123456"}'; //原始字串
            // $key = '1234567890abcdefghijklmnopqrstuv'; //任意32碼
            // $iv = '1234567890abcdef'; //任意16碼
            // $postData = 'ebyih2IHvCZEAtFZoigVh6apxGOItPev1OMKNIvyJDLdkBH5D87KtebbXF6/evTSbySmBbjKdsPjE91G3mXv4Q=='; //加密後
            // $postData = $this->acEncrypt($postDataStr, $key, $iv);
            // $postData = $this->acDecrypt($postData, $key, $iv);
            // dd($postData);

            return view()->make('gate.newfunction');
        }
    }

    public function acDecrypt($postData, $key, $iv)
    {
        $postData = openssl_decrypt(($postData),'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, $iv);
        $postData = str_replace("\x00","",$postData);
        return $postData;
    }

    public function acEncrypt($postDataStr, $key, $iv)
    {
        $len = strlen($postDataStr);
        $pad = 16 - ($len % 16);
        $postDataStr .= str_repeat("\0", $pad);
        $postData = openssl_encrypt($postDataStr,'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, $iv);
        return $postData;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->hasFile('filename')){
            $file = $request->file('filename');
            $uploadedFileMimeType = $file->getMimeType();
            $mimes = array('application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/CDFV2','application/octet-stream');
            if(in_array($uploadedFileMimeType, $mimes)){
                $result = Excel::toArray(new NewFunctionImport, $file);
                if(count($result[0]) > 0){
                    $result = $result[0];
                    if($request->cate == 'product'){
                        for($i=0;$i<count($result);$i++){
                            if(!empty($result[$i][0])){
                                $product = ProductDB::find($result[$i][0]);
                                if(!empty($product)){
                                    $product->update(['category_id' => $result[$i][1], 'sub_categories' => $result[$i][2]]);
                                }
                            }
                        }
                    }
                    if($request->cate == 'vendor'){
                        for($i=0;$i<count($result);$i++){
                            if(!empty($result[$i][0])){
                                $vendor = VendorDB::find($result[$i][0]);
                                if(!empty($vendor)){
                                    $vendor->update(['categories' => $result[$i][1]]);
                                }
                            }
                        }
                    }
                }
            }
        }
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
