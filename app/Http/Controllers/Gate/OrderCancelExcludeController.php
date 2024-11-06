<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryCategory as CategoryDB;
use App\Models\OrderCancelExcludeProduct as ExcludeProductDB;
use DB;

class OrderCancelExcludeController extends Controller
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
        $menuCode = 'M28S8';
        $appends = [];
        $compact = [];
        $categories = CategoryDB::select(['id','name','is_on'])->orderBy('is_on','desc')->get();
        $vendors = VendorDB::select(['id','name','is_on'])->orderBy('is_on','desc')->orderBy('name','asc')->get();
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
        $excludeProductTable = env('DB_DATABASE').'.'.(new ExcludeProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $products = ExcludeProductDB::join($productModelTable,$productModelTable.'.id',$excludeProductTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        // $products = $products->where(function($query) use ($productTable) {
        //         $query->whereRaw( " $productTable.package_data is null or $productTable.package_data = '' ");
        //     });

        $products = $products->select([
                $excludeProductTable.'.*',
                $vendorTable.'.name as vendor_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.serving_size',
                $productTable.'.status',
                $productModelTable.'.sku',
                $productModelTable.'.digiwin_no',
            ])->orderBy($vendorTable.'.id','asc')->paginate($list);

        $selectedProducts = ExcludeProductDB::join($productModelTable,$productModelTable.'.id',$excludeProductTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        // $selectedProducts = $selectedProducts->where(function($query) use ($productTable) {
        //     $query->whereNull($productTable.'.package_data')
        //         ->orWhere($productTable.'.package_data','');
        // });

        $selectedProducts = $selectedProducts->select([
            $excludeProductTable.'.*',
            $vendorTable.'.name as vendor_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ])->orderBy($vendorTable.'.id','asc')->get();

        $compact = array_merge($compact, ['menuCode','products','appends','vendors','categories','selectedProducts']);
        return view('gate.purchases.order_cancel_exclude_products_index', compact($compact));
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
        if(!empty($request->product_model_id)){
            ExcludeProductDB::truncate(); //先清空資料表
            foreach ($request->product_model_id as $key => $value) {
                $data[] = [
                    'product_model_id' => $value,
                ];
            }
            ExcludeProductDB::insert($data);
        }else{
            ExcludeProductDB::truncate(); //先清空資料表
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
        $product = ExcludeProductDB::findOrFail($id);
        $product->delete();
        return redirect()->back();
    }

    public function getProducts(Request $request)
    {
        $excludeProductTable = env('DB_DATABASE').'.'.(new ExcludeProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();

        $products = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        if(!empty($request->category)){
            $products = $products->where($productTable.'.category_id',$request->category);
        }
        if(!empty($request->vendor)){
            $products = $products->where($productTable.'.vendor_id',$request->vendor);
        }
        if(!empty($request->keyword)){
            $keyword = $request->keyword;
            $products = $products->where(function ($query) use ($keyword,$productTable,$vendorTable) {
                $query->where($productTable.'.name', 'like', "%$keyword%")
                ->orWhere($vendorTable.'.name', 'like', "%$keyword%");
            });
        }

        //去除掉被選擇的商品
        if($request->ids){
            $products = $products->whereNotIn($productModelTable.'.id',$request->ids);
        }

        $products = $products->distinct()->select([$productModelTable.'.id', $vendorTable.'.name as vendor_name', $productTable.'.name'])->orderBy($productModelTable.'.id','desc')->get();
        return $products;
    }
}
