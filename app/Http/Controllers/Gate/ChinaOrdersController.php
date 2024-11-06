<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryOrder as OrderDB;
use App\Traits\OrderFunctionTrait;
use DB;

class ChinaOrdersController extends Controller
{
    use OrderFunctionTrait;
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
        $menuCode = 'M27S5';
        $appends = $compact = [];
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
        $orders = OrderDB::whereIn('status',[1,2])
        ->whereIn('ship_to',['中國','新加坡'])
        ->select([
            'receiver_name',
            'receiver_address',
            'book_shipping_date',
            DB::raw("count(id) as count"),
            DB::raw("GROUP_CONCAT(order_number) as orderNumbers"),
            DB::raw("GROUP_CONCAT(id) as orderIds"),
            DB::raw("GROUP_CONCAT(id,'_',order_number) as orderData"),
        ])->groupBy('book_shipping_date','receiver_name')->having('count','>',1)->paginate($list);

        $compact = array_merge($compact, ['menuCode','appends','orders']);
        return view('gate.orders.china_orders', compact($compact));
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
