<?php

namespace App\Exports\Sheets;

use DB;
use App\Models\Product as ProductDB;
use App\Models\ProductModel as ProductModelDB;
use App\Models\ProductPackageList as ProductPackageListDB;
use App\Models\Country as CountryDB;
use App\Models\Vendor as VendorDB;
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

class ProductDigiwinIsOnSheet implements FromCollection,ShouldAutoSize,WithStrictNullComparison, WithHeadings, WithTitle, WithStyles
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
        $setColor = null;
        $data = null;
        foreach($products as $product){
            if(empty($product->digiwin_no)){ //補鼎新號
                $input['sku'] = $product->sku;
                $input['vendor_id'] = $product->vendor_id;
                $input['product_id'] = $product->id;
                $input['product_model_id'] = $product->product_model_id;
                $input['from_country_id'] = $product->from_country_id;
                $output = $this->makeSku($input);
                $digiwinNo = $output['digiwin_no'];
                $product->update([
                    'digiwin_no' => $digiwinNo,
                ]);
            }
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
            $flag=false;
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
                $product->digiwin_no,
                $product->vendor_name.' '.$product->name.' '.$product->model_name,
                $product->serving_size,
                $product->unit_name,
                $product->gtin13,
                $product->storage_life,
                'A'.str_pad($product->vendor_id,5,'0',STR_PAD_LEFT),
                $buyPrice,
            ];
            $i++;
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
                $sheet->getStyle('H'.($setColor[$i]+1))->getFill()->applyFromArray($backgroundColor);
            }
        }
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '匯出鼎新(啟用廠商)';
    }

    public function headings(): array
    {
        return [
            '品號(MB001)',
            '品名(MB002)',
            '規格(MB003)',
            '庫存單位(MB004)',
            '條碼編號(MB013)',
            '有效天數(MB023)',
            '主供應商(MB032)',
            '標準進價(MB046)',
        ];
    }
}
