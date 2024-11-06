<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;

class OpenInvoiceSettingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        request()->has('list') ? $list = (INT)urldecode(request()->list) : $list = 50;
        request()->has('is_invoice') ? $is_invoice = (INT)urldecode(request()->is_invoice) : $is_invoice = 2;
        request()->has('keyword') ? $keyword = urldecode(request()->keyword) : $keyword = '';

        $departments = DigiwinPaymentDB::where(function($query){
            $query->where('customer_no','<=','999')
            ->orWhereIn('customer_no',['065001','065002','065003','065004','065005','065006','065007','065008','065009','065010','065011'])
            ->orWhere('customer_no','like','AC%');
        });

        if($is_invoice != 2){
            $departments = $departments->where('is_invoice',$is_invoice);
        }
        if(!empty($keyword)){
            $departments = $departments->where(function ($query) use ($keyword) {
                    $query->where('customer_no','like',"%$keyword%")
                    ->orWhere('customer_name','like',"%$keyword%");
            });
        }
        $departments = $departments->orderBy('customer_no','asc')->paginate($list);

        $totalEnable = DigiwinPaymentDB::where('is_invoice',1)->count();
        $totalDisable = DigiwinPaymentDB::where('is_invoice',0)->count();

        $menuCode = 'M26S8';
        return view('gate.settings.openInvoice', compact('menuCode','totalEnable','totalDisable','departments','list','is_invoice','keyword'));

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

    public function activeInvoice(Request $request)
    {
        if(isset($request->customer_no)){
            $department = DigiwinPaymentDB::where('customer_no',$request->customer_no)->first();
            isset($request->is_invoice) ? $is_invoice = $request->is_invoice : $is_invoice = 0;
            if(!empty($department)){
                $department->update(['is_invoice' => $is_invoice]);
            }
        }
        return redirect()->back();
    }

    public function activeAcrtb(Request $request)
    {
        if(isset($request->customer_no)){
            $department = DigiwinPaymentDB::where('customer_no',$request->customer_no)->first();
            isset($request->is_acrtb) ? $is_acrtb = $request->is_acrtb : $is_acrtb = 0;
            if(!empty($department)){
                $department->update(['is_acrtb' => $is_acrtb]);
            }
        }
        return redirect()->back();
    }
}
