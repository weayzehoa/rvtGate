<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AutoStockinProduct as AutoStockinProductDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

use Session;
use DB;

class AutoStockinProductController extends Controller
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
        $menuCode = 'M28S7';
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

        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $autoStockinProductTable = env('DB_DATABASE').'.'.(new AutoStockinProductDB)->getTable();

        $products = AutoStockinProductDB::join($productModelTable,$productModelTable.'.digiwin_no',$autoStockinProductTable.'.digiwin_no')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        !empty($digiwin_no) ? $products = $products->where($autoStockinProductTable.'.digiwin_no','like',"%$digiwin_no%") : '';

        $products = $products->select([
            $autoStockinProductTable.'.*',
            $vendorTable.'.name as vendor_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ])->orderBy($autoStockinProductTable.'.id','desc')->paginate($list);

        $compact = array_merge($compact, ['menuCode','products']);
        return view('gate.purchases.autoStockinProduct', compact($compact));
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
        if(!empty($request->digiwin_no)){
            $productModel = ProductModelDB::where('digiwin_no',$request->digiwin_no)->first();
            if(empty($productModel)){
                Session::put('error',"$request->digiwin_no 查無商品資料");
            }else{
                $product = AutoStockinProductDB::where('digiwin_no',$productModel->digiwin_no)->first();
                if(!empty($product)){
                    Session::put('error',"$request->digiwin_no 已存在自動入庫商品清單。");
                }else{
                    if(strstr($productModel->sku,'BOM')){
                        Session::put('error',"$request->digiwin_no 組合商品不可列入自動入庫商品清單。");
                    }else{
                        $product = AutoStockinProductDB::create([
                            'digiwin_no' => $productModel->digiwin_no
                        ]);
                        Session::put('success',"$request->digiwin_no 建立完成。");
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
        $product = AutoStockinProductDB::findOrFail($id);
        $product->delete();
        return redirect()->back();
    }
}
