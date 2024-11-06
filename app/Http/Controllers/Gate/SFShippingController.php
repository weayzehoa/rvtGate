<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Traits\SFShippingFunctionTrait;
use App\Traits\SFApiFunctionTrait;

class SFShippingController extends Controller
{
    use SFShippingFunctionTrait,SFApiFunctionTrait;

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
        $menuCode = 'M29S6';
        $shippings = $compact = $appends = [];
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
        }
        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        $shippings = $this->getSFShippingData(request(),'index');

        $compact = array_merge($compact, ['menuCode','shippings','list','appends']);
        return view('gate.purchases.sfShipping', compact($compact));
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
        //
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


    public function getStatus(Request $request)
    {
        if(isset($request->id)){
            $sfShipping = $this->getSFShippingData(request(),'show');
            $sfShippingNos = [$sfShipping->sf_express_no];
            $result = $this->chkSFShippingNumber($sfShipping->sf_express_no,mb_substr($sfShipping->phone,-4));
            if(!empty($result)){
                $result = $result[0];
                if($result['code'] == 0 && $result['msg'] == 'success') {
                    $sfShipping->traceItems = $result['trackDetailItems'];
                }
            }
            return $sfShipping;
        }
        return null;
    }

}
