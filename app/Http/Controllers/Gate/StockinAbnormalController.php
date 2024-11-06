<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\StockinAbnormal as StockinAbnormalDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

class StockinAbnormalController extends Controller
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
        $menuCode = 'M29S3';
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

        if (!isset($status)) {
            $status = '0';
            $compact = array_merge($compact, ['status']);
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $stockinAbnormalTable = env('DB_DATABASE').'.'.(new StockinAbnormalDB)->getTable();

        $stockinAbnormals = StockinAbnormalDB::orderBy('is_chk','asc')->orderBy('created_at','desc')->paginate($list);
        foreach($stockinAbnormals as $stockinAbnormal){
            $stockinAbnormal->product_model_id = 1000;
            $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->select([
                    $vendorTable.'.name as vendor_name',
            ])->find($stockinAbnormal->product_model_id);
            !empty($product) ? $stockinAbnormal->vendor_name = $product->vendor_name : $stockinAbnormal->vendor_name = null;
            $admin = AdminDB::find($stockinAbnormal->admin_id);
            !empty($admin) ? $stockinAbnormal->admin_name = $admin->name : $stockinAbnormal->admin_name = null;
        }

        $compact = array_merge($compact, ['menuCode','stockinAbnormals','appends']);
        return view('gate.purchases.stockin_abnormal', compact($compact));
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
        $stockinAbnormal = StockinAbnormalDB::findOrFail($id);
        $stockinImport = StockinImportDB::where([['id',$stockinAbnormal->stockin_import_id],['direct_shipment',0]])->first();
        !empty($stockinImport) ? $stockinImport->delete() : '';
        $stockinAbnormal->update([
            'admin_id' => auth('gate')->id(),
            'chk_date' => date('Y-m-d H:i:s'),
            'is_chk' => 1,
        ]);
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
}
