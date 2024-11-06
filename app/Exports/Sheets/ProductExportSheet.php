<?php

namespace App\Exports\Sheets;

use DB;
use App\Models\ProductModel as ProductModelDB;
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

class ProductExportSheet implements FromCollection, ShouldAutoSize, WithStrictNullComparison, WithHeadings, WithTitle, WithStyles
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
            $buyPrice = 0;
            if(empty($product->vendor_price) || $product->vendor_price == 0){
                $buyPrice = ($product->price - $product->TMS_price) * ((100-$serviceFeePercent)/100) + $product->TMS_price;
                $buyPrice = round($buyPrice);
                $flag=true;
            }else{
                $buyPrice = $product->vendor_price;
                $flag=false;
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
                $buyPrice,
                '', //行郵稅種類
                $serviceFeePercent.'%',
                $product->model_name,
                $product->sku,
                $product->digiwin_no,
                $product->airport_days,
                $product->hotel_days,
                $product->is_del,
            ];
            $i++;
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('R')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function headings(): array
    {
        return [
            '品名',
            '內容量',
            '狀態',
            '廠商/品牌',
            '店家狀態',
            '上架時間',
            '更新時間',
            '分類',
            '庫存',
            '安全庫存',
            '單價',
            '原價',
            '採購價',
            '行郵稅種類',
            '服務費(%)',
            '規格',
            '貨號',
            '鼎新ERP貨號',
            '機場指定備貨天數',
            '旅店指定備貨天數',
            '已刪除',
        ];
    }
    public function title(): string
    {
        return '產品列表';
    }
}
