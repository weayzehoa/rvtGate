<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryOrder as OrderDB;
use DB;

class AsiamilesPrintController extends Controller
{
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
        $order_number = $url = null;
        if(!empty(request()->order_number)){
            $order_number = request()->order_number;
            $order = OrderDB::where('order_number',$order_number)
            ->whereNotNull('partner_order_number')
            ->where(function($query){
                $query = $query->where('create_type','asiamiles')->orWhere('create_type','ASIAMILES');
            })->select([
                DB::raw("MD5(CONCAT('ica',partner_order_number,'ry')) am_md5"),
            ])->first();
            if(!empty($order)){
                $url = 'https://icarry.me/asiamiles-print.php?o='.$order->am_md5;
            }else{
                $url = '訂單號碼錯誤 或 非asiamiles訂單，無法生成。';
            }
        }
        return view('gate.orders.asiamiles_print',compact('order_number','url'));
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
}
