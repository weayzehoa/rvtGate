<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryPay2go as Pay2GoDB;
use DB;

class InvoiceFailureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M27S7';
        $compact = $appends = [];
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
            !empty($value) ? $con[$key] = $value : '';
        }
        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $pay2goTable = env('DB_ICARRY').'.'.(new Pay2GoDB)->getTable();
        $failures = Pay2GoDB::whereRaw(" order_number not IN(SELECT order_number FROM $pay2goTable where get_json = 'SUCCESS,發票開立成功' or get_json = 'SUCCESS,開立成功(此發票已重覆開立)')");
        !empty($order_number) ? $failures = $failures->where('order_number','like',"%$order_number%") : '';
        $failures = $failures->select([
            $pay2goTable.'.order_number',
            $pay2goTable.'.buyer_name',
            'order_id' => OrderDB::whereColumn($pay2goTable.'.order_number',$orderTable.'.order_number')->select($orderTable.'.id as order_id')->limit(1),
            DB::raw("count(id) as times"),
            DB::raw("MAX(get_json) as get_json"),
            DB::raw("MAX(create_time) as create_time"),
        ])->DISTINCT()->groupBy('order_number')->paginate($list);
        $compact = array_merge($compact, ['menuCode','failures']);
        return view('gate.orders.invoice_failure', compact($compact));
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
