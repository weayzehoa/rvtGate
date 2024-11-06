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
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarrySiteSetup as SiteSetupDB;
use App\Models\iCarryProductUpdateRecord as ProductUpdateRecordDB;

use App\Models\ErpProduct as ErpProductDB;
use App\Models\ErpVendor as ErpVendorDB;
use DB;

trait OrderImportFunctionTrait
{
    protected function checkProduct($sku)
    {
        $memo = null;
        if(!empty($sku)){
            $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
            $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
            $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->where($productTable.'.is_del',0)
            ->where($productModelTable.'.is_del',0)
            ->where(function($query)use($productModelTable,$sku){
                $query = $query->where($productModelTable.'.sku',$sku)->orWhere($productModelTable.'.digiwin_no',$sku);
            })->select([
                $productModelTable.'.*',
                $productTable.'.name as product_name',
                $productTable.'.vendor_id',
                $productTable.'.package_data',
            ])->first();
            if(empty($productModel)){
                $memo .= $sku.' 商品不存在iCarry系統中。';
            }else{
                //vendor id = 293 or 短效品 不檢查 鼎新商家
                if($productModel->vendor_id != 293 && !strstr($productModel->product_name,'短效品')){
                    $vendor = VendorDB::find($productModel->vendor_id);
                    !empty($vendor) && !empty($vendor->digiwin_vendor_no) ? $erpVendorId = $vendor->digiwin_vendor_no : $erpVendorId = 'A'.str_pad($productModel->vendor_id,5,0,STR_PAD_LEFT);
                    $erpVendor = ErpVendorDB::find($erpVendorId);
                    empty($erpVendor) ? $memo .= $sku.' 商品的商家不存在於鼎新系統中。' : '';
                }
                if(strstr($productModel->sku,'BOM')){
                    $errors = [];
                    if(!empty($productModel->package_data)){
                        $packageData = json_decode(str_replace('	','',$productModel->package_data));
                        if(is_array($packageData)){
                            $chkPackage = 0;
                            foreach($packageData as $package){
                                if(isset($package->is_del)){
                                    if($package->is_del == 0){
                                        if($package->bom == $productModel->sku){
                                            $chkPackage++;
                                            foreach($package->lists as $list){
                                                $pm = ProductModelDB::where('sku',$list->sku)->first();
                                                if(!empty($pm)){
                                                    $erpProduct = ErpProductDB::where('MB001',$pm->digiwin_no)->first();
                                                    empty($erpProduct) ? $memo .= "$sku 組合商品中 $list->sku 不存在鼎新系統中。" : '';
                                                }else{
                                                    $memo .= "$sku 組合商品中 $list->sku 不存在iCarry系統中。";
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }else{
                                    if($package->bom == $productModel->sku){
                                        $chkPackage++;
                                        foreach($package->lists as $list){
                                            $pm = ProductModelDB::where('sku',$list->sku)->first();
                                            if(!empty($pm)){
                                                $erpProduct = ErpProductDB::where('MB001',$pm->digiwin_no)->first();
                                                empty($erpProduct) ? $memo .= "$sku 組合商品中 $list->sku 不存在鼎新系統中。" : '';
                                            }else{
                                                $memo .= "$sku 組合商品中 $list->sku 不存在iCarry系統中。";
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                            if($chkPackage == 0){
                                $memo .= $sku.' 組合商品資料已被刪除。';
                            }
                        }else{
                            $memo .= $sku.' 組合商品資料在iCarry系統中錯誤。';
                        }
                    }else{
                        $memo .= $sku.' 組合商品不存在iCarry系統中。';
                    }
                }else{
                    $erpProduct = ErpProductDB::where('MB001',$productModel->digiwin_no)->first();
                    empty($erpProduct) ? $memo .= $sku.' 商品不存在於鼎新系統中。' : '';
                }
            }
            return $memo;
        }else{
            $memo .= "貨號不可為空值。";
        }
    }

    protected function getProductData($sku,$payTime) {
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        if(!empty($sku)){
            $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where('sku',$sku)->orWhere('digiwin_no',$sku)
            ->select([
                $productModelTable.'.*',
                $productModelTable.'.id as product_model_id',
                $productTable.'.id as product_id',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productTable.'.price',
                $productTable.'.gross_weight',
                $productTable.'.net_weight',
                $productTable.'.direct_shipment',
                $productTable.'.is_tax_free',
                $productTable.'.vendor_price',
                $productTable.'.service_fee_percent as product_service_fee_percent',
                $productTable.'.package_data',
                $productTable.'.hotel_days',
                $productTable.'.vendor_earliest_delivery_date',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee as vendor_service_fee',
                $vendorTable.'.shipping_verdor_percent',
            ])->first();
            if(!empty($product)){
                if(empty($product->vendor_price) || $product->vendor_price <= 0){
                    //vendor_price修改紀錄
                    $beforeProductPrice = $afterProductPrice = $beforePrice = $afterPrice = null;
                    $tmp = $tmp2 = $tmp3 = $tmp4 = [];
                    $tmp = ProductUpdateRecordDB::where([['product_id',$product->product_id],['column','vendor_price'],['create_time','<=',$payTime]])->select('after_value')->first();
                    $tmp2 = ProductUpdateRecordDB::where([['product_id',$product->product_id],['column','vendor_price'],['create_time','>',$payTime]])->select('before_value')->first();
                    !empty($tmp) ? $afterPrice = $tmp->after_value : '';
                    !empty($tmp2) ? $beforePrice = $tmp2->before_value : '';
                    if(!empty($afterPrice)){
                        $product->vendor_price = $afterPrice;
                    }elseif(!empty($beforePrice)){
                        $product->vendor_price = $beforePrice;
                    }
                }
                $tmp3 = ProductUpdateRecordDB::where([['product_id',$product->product_id],['column','price'],['create_time','<=',$payTime]])->select('after_value')->first();
                $tmp4 = ProductUpdateRecordDB::where([['product_id',$product->product_id],['column','price'],['create_time','>',$payTime]])->select('before_value')->first();
                !empty($tm3) ? $afterProductPrice = $tmp3->after_value : '';
                !empty($tmp4) ? $beforeProductPrice = $tmp4->before_value : '';
                if(!empty($afterProductPrice)){
                    $product->price = $afterProductPrice;
                }elseif(!empty($beforeProductPrice)){
                    $product->price = $beforeProductPrice;
                }
                $originDigiwinNo = $product->origin_digiwin_no;
                if(!empty($originDigiwinNo)){
                    $beforeProductPrice = $afterProductPrice = $beforePrice = $afterPrice = null;
                    $temp = $tmp = $tmp2 = $tmp3 = $tmp4 = [];
                    $temp = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                    ->where('digiwin_no',$originDigiwinNo)
                    ->select([
                        $productTable.'.hotel_days',
                        $productTable.'.price as origin_product_price',
                        $productTable.'.id as origin_product_id',
                        $productTable.'.vendor_earliest_delivery_date',
                        $productTable.'.vendor_price as origin_vendor_price',
                        $vendorTable.'.service_fee as origin_service_fee',
                    ])->first();
                    $product->price = $temp->origin_product_price;
                    $product->vendor_earliest_delivery_date = $temp->vendor_earliest_delivery_date;
                    $product->vendor_price = $temp->origin_vendor_price;
                    $product->vendor_service_fee = $temp->origin_service_fee;
                    if(empty($product->vendor_price) || $product->vendor_price <= 0){
                        //vendor_price修改紀錄
                        $tmp = ProductUpdateRecordDB::where([['product_id',$temp->origin_product_id],['column','vendor_price'],['create_time','<=',$payTime]])->select('after_value')->first();
                        $tmp2 = ProductUpdateRecordDB::where([['product_id',$temp->origin_product_id],['column','vendor_price'],['create_time','>',$payTime]])->select('before_value')->first();
                        !empty($tmp) ? $afterPrice = $tmp->after_value : '';
                        !empty($tmp2) ? $beforePrice = $tmp2->before_value : '';
                        if(!empty($afterPrice)){
                            $product->vendor_price = $afterPrice;
                        }elseif(!empty($beforePrice)){
                            $product->vendor_price = $beforePrice;
                        }
                    }
                    $tmp3 = ProductUpdateRecordDB::where([['product_id',$temp->origin_product_id],['column','price'],['create_time','<=',$payTime]])->select('after_value')->first();
                    $tmp4 = ProductUpdateRecordDB::where([['product_id',$temp->origin_product_id],['column','price'],['create_time','>',$payTime]])->select('before_value')->first();
                    !empty($tmp3) ? $afterProductPrice = $tmp3->after_value : '';
                    !empty($tmp4) ? $beforeProductPrice = $tmp4->before_value : '';
                    if(!empty($afterProductPrice)){
                        $product->price = $afterProductPrice;
                    }elseif(!empty($beforeProductPrice)){
                        $product->price = $beforeProductPrice;
                    }
                }
                $vendorServiceFeePercent = 0;
                if(!empty($product->vendor_service_fee)){
                    $vendorServiceFee = $this->serviceFee($product->vendor_service_fee);
                    foreach ($vendorServiceFee as $sf_key=>$sf_val) {
                        if ($sf_val->name=="iCarry") {
                            $vendorServiceFeePercent = $sf_val->percent;
                            break;
                        }
                    }
                }
                $product->vendor_service_fee_percent = $vendorServiceFeePercent;
                if(empty($product->vendor_price) || $product->vendor_price == 0){
                    $product->vendor_price = $product->price - $product->price * ( $vendorServiceFeePercent / 100 );
                }
                return $product;
            }
        }
        return null;
    }

    protected function getPurchasePrice($product)
    {
        $vendorServiceFeePercent = $itemPurchasePrice = 0;
        if($product->vendor_price > 0 ){
            $itemPurchasePrice = $product->vendor_price;
        }else{
            if(!empty($product->vendor_service_fee)){
                $vendorServiceFee = $this->serviceFee($product->vendor_service_fee);
                foreach ($vendorServiceFee as $sf_key=>$sf_val) {
                    if ($sf_val->name=="iCarry") {
                        $vendorServiceFeePercent=$sf_val->percent;
                        break;
                    }
                }
                $itemPurchasePrice = $product->price - $product->price * ( $vendorServiceFeePercent / 100 );
            }
        }
        $data['itemPurchasePrice'] = $itemPurchasePrice;
        $data['vendorServiceFeePercent'] = $vendorServiceFeePercent;
        return $data;
    }

    /*
        整理Servce_fee資料
        1. 檢驗是否存在
        2. 檢驗是否為陣列
        3. 轉換percent空值為0
    */
    protected function serviceFee($input = ''){
        if($input == ''){
            $serviceFees = json_decode('[{"name":"天虹","percent":0},{"name":"閃店","percent":0},{"name":"iCarry","percent":0},{"name":"現場提貨","percent":0}]');
        }elseif(is_array($input)){
            for($i=0;$i<count($input['name']);$i++){
                $serviceFees[$i]['name'] = $input['name'][$i];
                $serviceFees[$i]['percent'] = $input['percent'][$i];
            }
            $serviceFees = json_encode($serviceFees);
        }else{
            $serviceFees = json_decode(str_replace('"percent":}','"percent":0}',$input));
        }
        return $serviceFees;
    }

    protected function bigintval($value) {
        $value = trim($value);
        if (ctype_digit($value)) {
              if(substr( $value , 0 , 1 )=="0"){
                return substr( $value , 1 );
              }else{
                  return $value;
              }
        }
        $value = preg_replace("/[^0-9](.*)$/", '', $value);
        if (ctype_digit($value)) {
          if(substr($value,0,1)=="0"){
              $value=substr($value,1);
          }
          return $value;
        }
        return 0;
    }

    protected function customerNo($n,$payMethod){
        switch($n){
            case '001':return 'admin';break;
            case '002':return 'admin';break;
            case '003':return 'admin';break;
            case '004':return 'admin';break;
            case '005':return 'admin';break;
            case '006':return 'admin';break;
            case '007':return 'admin';break;
            case '008':return 'admin';break;
            case '009':return 'admin';break;
            case '037':return 'admin';break;
            case '012':return '客路';break;
            case '018':return 'hutchgo';break;
            case '023':return '生活市集';break;
            case '027':return 'myhuo';break;
            case '021':return '松果';break;
            case '028':return '17life';break;
            default:return $payMethod;break;
        }
    }
}
