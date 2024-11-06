<?php

namespace App\Exports\Sheets;

use DB;
use App\Models\Product as ProductDB;
use App\Models\ProductModel as ProductModelDB;
use App\Models\ProductPackageList as ProductPackageListDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use App\Traits\ProductExportFunctionTrait;

class ProductSingleSheet implements FromCollection,ShouldAutoSize,WithStrictNullComparison, WithHeadings, WithTitle, WithStyles
{
    use ProductExportFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    public function collection()
    {
        $products = $this->getProductData($this->param);
        $i=0;
        $serviceFeePercent = null;
        foreach($products as $product){
            if(strtoupper(substr($product->sku,0,3)) == 'BOM'){
                $packages = ProductPackageListDB::join('product_packages','product_packages.id','product_package_lists.product_package_id')
                    ->join('product_model','product_model.id','product_package_lists.product_model_id')
                    ->join('products','products.id','product_model.product_id')
                    ->join('vendors','vendors.id','products.vendor_id')
                    ->select([
                        'product_model.gtin13',
                        'product_model.sku',
                        'product_model.digiwin_no',
                        'product_package_lists.quantity',
                        'products.model_name',
                        'products.name as product_name',
                        'products.vendor_price',
                        'products.TMS_price',
                        'products.price',
                        'vendors.service_fee',
                    ])->where('product_packages.product_id',$product->id)->get();
                foreach($packages as $package){
                    $serviceFeePercent = 0;
                    if(!empty($package->service_fee)){
                        $package->service_fee = str_replace(":}",":0}",$package->service_fee); //補0, 正常遷移時應該已經補了
                        $serviceFee = json_decode($package->service_fee,true);
                        if(is_array($serviceFee)){
                            foreach($serviceFee as $value){
                                if($value['name']=="iCarry"){
                                    $serviceFeePercent = $value['percent'];
                                    break;
                                }
                            }
                        }
                    }
                    // 依照【商品管理 > 匯出 > 鼎新 > H欄 標準進價(MB046) 】規則
                    $flag=false;
                    $buyPrice = 0;
                    if(empty($package->vendor_price) || $package->vendor_price == 0){
                        $buyPrice = ($package->price - $package->TMS_price) * ((100-$serviceFeePercent)/100) + $package->TMS_price;
                        $buyPrice = round($buyPrice);
                        $flag=true;
                    }else{
                        $buyPrice = $package->vendor_price;
                        $flag=false;
                    }
                    $buyPrice = round($buyPrice/1,2);
                    if(empty($buyPrice) || $flag == true){
                        $setColor[] = $i;
                    }
                    $data[$i] = [
                        $product->name,
                        $product->serving_size,
                        $product->status_name,
                        $product->vendor_name,
                        $product->vendor_status,
                        $product->created_time,
                        $product->updated_at,
                        $product->category_name,
                        $product->quantity,
                        $product->safe_quantity,
                        $product->price,
                        $product->fake_price,
                        $buyPrice, //單品廠商進價
                        '',
                        $serviceFeePercent, //服務費(%)
                        $product->model_name,
                        $product->sku,
                        $package->gtin13,
                        $package->sku,
                        $package->digiwin_no,
                        $package->product_name,
                        $package->model_name,
                        $package->quantity,
                        $product->digiwin_no,
                        $product->airport_days,
                        $product->hotel_days,
                        $product->is_del,
                    ];
                    $i++;
                }
            }else{
                $serviceFeePercent = 0;
                if(!empty($product->service_fee)){
                    $product->service_fee = str_replace(":}",":0}",$product->service_fee); //補0, 正常遷移時應該已經補了
                    $serviceFee = json_decode($product->service_fee,true);
                    if(is_array($serviceFee)){
                        foreach($serviceFee as $value){
                            if($value['name']=="iCarry"){
                                $serviceFeePercent = $value['percent'];
                                break;
                            }
                        }
                    }
                }
                // 依照【商品管理 > 匯出 > 鼎新 > H欄 標準進價(MB046) 】規則
                $flag=false;
                $buyPrice = 0;
                if(empty($product->vendor_price) || $product->vendor_price == 0){
                    $buyPrice = ($product->price - $product->TMS_price) * ((100-$serviceFeePercent)/100) + $product->TMS_price;
                    $buyPrice = round($buyPrice);
                    $flag=true;
                }else{
                    $buyPrice = $product->vendor_price;
                    $flag=false;
                }
                $buyPrice = round($buyPrice/1,2);
                if(empty($buyPrice) || $flag == true){
                    $setColor[] = $i;
                }
                $data[$i] = [
                    $product->name,
                    $product->serving_size,
                    $product->status_name,
                    $product->vendor_name,
                    $product->vendor_status,
                    $product->created_time,
                    $product->updated_at,
                    $product->category_name,
                    $product->quantity,
                    $product->safe_quantity,
                    $product->price,
                    $product->fake_price,
                    $buyPrice, //單品廠商進價
                    '',
                    $serviceFeePercent, //服務費(%)
                    $product->model_name,
                    $product->sku,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $product->digiwin_no,
                    $product->airport_days,
                    $product->hotel_days,
                    $product->is_del,
                ];
                $i++;
            }
        }
        $this->setColor = $setColor;
        return collect($data);
    }


    public function styles(Worksheet $sheet)
    {
        $setColor = $this->setColor;
        $backgroundColor = [
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => 'FFFF00'],
        ];
        if(!empty($setColor)){
            for ($i=0; $i < count($setColor) ; $i++) {
                $sheet->getStyle('M'.($setColor[$i]+1))->getFill()->applyFromArray($backgroundColor);
            }
        }
        $sheet->getStyle('R')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '產品列表';
    }

    public function headings(): array
    {
        return [
            '品名',
            '內容量',
            '狀態',
            '廠商',
            '店家狀態',
            '上架時間',
            '更新時間',
            '分類',
            '庫存',
            '安全庫存',
            '單價',
            '原價',
            '單品廠商進價',
            '行郵稅種類',
            '服務費(%)',
            '規格',
            '貨號',
            '單品國際條碼',
            '單品貨號',
            '單品鼎新貨號',
            '單品品名',
            '單品規格',
            '單品數量',
            '鼎新ERP貨號',
            '機場提貨指定備貨天數',
            '旅店指定地址備貨天數',
            '已刪除',
        ];
    }

    public function columnWidths(): array
    {
        $highestColumn = 'Z';
        $highestColumn++;
        for ($column = 'A'; $column !== $highestColumn; $column++) {
            $width[$column] = 20;
        }
        return $width;
    }
}
