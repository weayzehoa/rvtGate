<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SpecialVendor as SpecialVendorDB;
use App\Models\iCarryVendor as VendorDB;
use Session;

class SpecialVendorsController extends Controller
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
        $menuCode = 'M28S4';
        $appends = [];
        $compact = [];
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

        $specialvendors = SpecialVendorDB::orderBy('code','asc')->paginate($list);
        foreach($specialvendors as $spVendor){
            $spVID[] = $spVendor->vendor_id;
        }
        $vendors = VendorDB::whereNotIn('id',$spVID)->orderBy('is_on','desc')->get();
        $compact = array_merge($compact, ['menuCode','specialvendors','vendors']);
        return view('gate.purchases.special_vendors', compact($compact));
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
        if(!empty($request->vendorId)){
            $vendor = VendorDB::findOrFail($request->vendorId);
            if(!empty($vendor)){
                $chk = SpecialVendorDB::where('vendor_id',$vendor->id)->first();
                if(empty($chk)){
                    $spVendor = SpecialVendorDB::create([
                        'vendor_id' => $vendor->id,
                        'code' => 'A'.str_pad($vendor->id,5,"0",STR_PAD_LEFT),
                        'company' => $vendor->company,
                        'name' => $vendor->name,
                    ]);
                }else{
                    Session::put('error', '該商家已存在!');
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
        $spVendor = SpecialVendorDB::findOrFail($id)->delete();
        return redirect()->back();
    }
}
