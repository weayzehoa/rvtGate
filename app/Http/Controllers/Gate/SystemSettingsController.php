<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\iCarryOrder as OrderDB;
use App\Http\Requests\Gate\SystemSettingsRequest;
use DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SystemSettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate',['except' => ['qrCode']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M26S4';
        $system = SystemSettingDB::with('admin')->findOrFail(1); //只有一筆
        $twpayUsed = OrderDB::where('promotion_code','TWPAY')->select([
            DB::raw('SUM(discount) as discount')
        ])->first()->discount;
        return view('gate.settings.system', compact('menuCode','system','twpayUsed'));
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
    public function update(SystemSettingsRequest $request, $id)
    {
        $data = $request->all();
        $data['admin_id'] = Auth::user()->id;
        $system = SystemSettingDB::with('admin')->findOrFail($id)->update($data);
        return redirect()->back();
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

    public function qrCode()
    {
        if(!empty(request()->no)){
            isset(request()->name) ? $name = request()->name : $name = null;
            $serialNo = request()->no;
            $qrCodeUrl = QrCode::size(150)->generate($serialNo);
            return view('gate.qrcode',compact('serialNo','qrCodeUrl','name'));
        }
    }
}
