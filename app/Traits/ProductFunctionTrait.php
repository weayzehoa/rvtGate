<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\Schedule as ScheduleDB;
use DB;
use Carbon\Carbon;

trait ProductFunctionTrait
{
    protected function getProductData($request = null,$type = null, $name = null)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        $products = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        if(!empty($request)){
            if($request == 'schedule'){
                //依據排程設定頻率抓取時間範圍
                $schedule = ScheduleDB::where('code','productUpdate')->first();
                $date = $this->getScheduleTime($schedule->frequency);
                $products = $products->whereBetween($productTable.'.pass_time',[$date['start'],$date['end']]);
            }else{
                if(count($request->all()) > 0){
                    //將進來的資料作參數轉換
                    foreach ($request->all() as $key => $value) {
                        $$key = $value;
                    }
                }else{
                    $products = $products->where($productTable.'.id',0);
                }
            }
        }

        if (!isset($list)) {
            $list = 50;
        }

        if($type == 'index' && $name == 'productTransfer'){
            if(!empty($gtin13) || !empty($sku) || !empty($digiwin_no) || !empty($origin_digiwin_no)){
                !empty($gtin13) ? $products = $products->where($productModelTable.'.gtin13',$gtin13) : '';
                !empty($sku) ? $products = $products->where($productModelTable.'.sku',$sku) : '';
                !empty($digiwin_no) ? $products = $products->where($productModelTable.'.digiwin_no',$digiwin_no) : '';
                !empty($origin_digiwin_no) ? $products = $products->where($productModelTable.'.origin_digiwin_no',$origin_digiwin_no) : '';
            }else{
                $products = $products->where($productTable.'.id',0);
            }
            $products = $products->select([
                $productModelTable.'.*',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.digiwin_vendor_no',
                $vendorTable.'.ac_digiwin_vendor_no',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            ]);
        }else{
            //選擇資料
            $products = $products->select([
                $productTable.'.*',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee',
                $vendorTable.'.digiwin_vendor_no',
                $vendorTable.'.ac_digiwin_vendor_no',
                $productModelTable.'.id as product_model_id',
                $productModelTable.'.name as product_model_name',
                $productModelTable.'.gtin13',
                $productModelTable.'.sku',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.quantity',
                $productModelTable.'.safe_quantity',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as name"),
            ]);
        }

        if ($request == 'schedule') {
            $products = $products->orderBy($productTable.'.id', 'asc');
        }else{
            if($type == 'index'){
                $products = $products->orderBy($productTable.'.id', 'asc')->paginate($list);
            }else{
                $products = $products->orderBy($productTable.'.id', 'asc')->get();
            }
        }

        return $products;
    }

    protected function getScheduleTime($time){
        $date['start'] = null;
        $now = Carbon::now();
        $date['end'] = Carbon::now()->toDateTimeString();

        switch ($time) {
            case 'everyFiveMinutes': //每五分鐘
                $date['start'] = $now->subMinutes(5)->toDateTimeString();
                break;
            case 'everyTenMinutes': //每十分鐘
                $date['start'] = $now->subMinutes(10)->toDateTimeString();
                break;
            case 'everyFifteenMinutes': //每十五分鐘
                $date['start'] = $now->subMinutes(15)->toDateTimeString();
                break;
            case 'everyThirtyMinutes': //每三十分鐘
                $date['start'] = $now->subMinutes(30)->toDateTimeString();
                break;
            case 'hourly': //每小時
                $date['start'] = $now->subMinutes(60)->toDateTimeString();
                break;
            case 'everyThreeHours': //每三小時
                $date['start'] = $now->subMinutes(180)->toDateTimeString();
                break;
            case 'everySixHours': //每六小時
                $date['start'] = $now->subMinutes(360)->toDateTimeString();
                break;
            case 'daily': //每日午夜
                $date['start'] = $now->subDay()->toDateTimeString();
                break;
            case 'weekly': //每週六午夜
                $date['start'] = $now->subWeek()->toDateTimeString();
                break;
            case 'monthly': //每月第一天的午夜
                $date['start'] = $now->subMonth()->toDateTimeString();
                break;
            case 'quarterly': //每季第一天的午夜
                $date['start'] = $now->subQuarters(1)->toDateTimeString();
                break;
            case 'yearly': //每年第一天的午夜
                $date['start'] = $now->subYears(1)->toDateTimeString();
                break;
        }
        return $date;
    }

    protected function makeSku($input){
        if(isset($input['sku'])){
            $output['sku'] = $input['sku'];
        }else{
            //sku的編碼方式 EC 0001 000001
            $output['sku'] = "EC" . str_pad($input['vendor_id'],5,'0',STR_PAD_LEFT) . str_pad($input['product_model_id'],6,'0',STR_PAD_LEFT);
        }

        //digiwin_no的編碼方式
        $digiwinNo="5";
        $countryId = $input['from_country_id'];
        $country = CountryDB::findOrFail($countryId);
        $digiwinNo .= $country->lang; //語言代碼 1:tw, 5:jp
        $digiwinNo .= "A".str_pad($input['vendor_id'],5,"0",STR_PAD_LEFT);

        // 找出product_models與product_id跟vendor_id關聯的總數
        $vendorProductModelCounts = ProductModelDB::where('id','<=',$input['product_model_id'])
            ->whereIn('product_id', ProductDB::where('vendor_id',$input['vendor_id'])->select('id')->get())
            ->count();

        //鼎新編碼原則（包括單品與組合商品）
        if(substr($output['sku'],0,3)=="BOM"){
            $digiwinNo .= "B".str_pad(base_convert($vendorProductModelCounts, 10, 36),3,"0",STR_PAD_LEFT);
        }else{
            $digiwinNo .= str_pad(base_convert($vendorProductModelCounts, 10, 36),4,"0",STR_PAD_LEFT);
        }

        $output['digiwin_no'] = strtoupper($digiwinNo);
        return $output;
    }
}
