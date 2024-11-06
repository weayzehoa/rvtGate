<?php

namespace App\Exports\Sheets;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\ErpPURTC as ErpPURTCDB;
// use App\Models\ErpVendor as ErpVendorDB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use DB;
use App\Traits\PurchaseOrderFunctionTrait;

class PurchaseOrderWithSingleExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths,WithColumnFormatting
{
    use PurchaseOrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();

        $purchaeOrders = $this->getPurchaseOrderData($this->param);
        $t=0;
        if(count($purchaeOrders) > 0){
            $i = 1;
            $t = 1;
            foreach($purchaeOrders as $order){
                foreach($order->exportItems as $item){
                    if($item->quantity > 0){
                        $tmp = ErpPURTCDB::find($order->erp_purchase_no);
                        if($tmp->TC018 == 1){
                            $taxType = '應稅內含';
                            $purchasePrice = $item->purchase_price;
                        }elseif($tmp->TC018 == 2){
                            $taxType = '應稅外加';
                            $purchasePrice = $item->purchase_price / 1.05;
                        }elseif($tmp->TC018 == 3){
                            $taxType = '零稅率';
                            $purchasePrice = $item->purchase_price / 1.05;
                        }elseif($tmp->TC018 == 4){
                            $taxType = '免稅';
                            $purchasePrice = $item->purchase_price / 1.05;
                        }elseif($tmp->TC018 == 9){
                            $taxType = '不計稅';
                            $purchasePrice = $item->purchase_price / 1.05;
                        }else{
                            $taxType = null;
                            $purchasePrice = $item->purchase_price;
                        }
                        $item->direct_shipment == 1 ? $wareHouse = 'W02' : $wareHouse = 'W01';
                        $data[] = [
                            str_pad($i,4,'0',STR_PAD_LEFT),
                            $order->type,
                            $order->erp_purchase_no,
                            'A'.str_pad($item->vendor_id,5,'0',STR_PAD_LEFT),
                            $item->vendor_name,
                            $item->digiwin_no,
                            $item->product_name,
                            $item->serving_size,
                            $item->unit_name,
                            $item->quantity,
                            $purchasePrice, //小數點第二位
                            round($item->quantity * $purchasePrice,0), //單價 * 數量, 四捨五入
                            $taxType,
                            $wareHouse,
                            $item->vendor_arrival_date,
                            null,
                        ];
                        $t++;
                        $i++;
                        if(strstr($item->sku,'BOM')){
                            foreach($item->exportPackage as $package){
                                if($tmp->TC018 == 1){
                                    $packagePurchasePrice = $package->purchase_price;
                                }elseif($tmp->TC018 == 2){
                                    $packagePurchasePrice = $package->purchase_price / 1.05;
                                }elseif($tmp->TC018 == 3){
                                    $packagePurchasePrice = $package->purchase_price / 1.05;
                                }elseif($tmp->TC018 == 4){
                                    $packagePurchasePrice = $package->purchase_price / 1.05;
                                }elseif($tmp->TC018 == 9){
                                    $packagePurchasePrice = $package->purchase_price / 1.05;
                                }else{
                                    $packagePurchasePrice = $package->purchase_price;
                                }
                                $data[] = [
                                    null,
                                    null,
                                    null,
                                    null,
                                    null,
                                    $package->digiwin_no,
                                    $package->product_name,
                                    $package->serving_size,
                                    $package->unit_name,
                                    $package->quantity,
                                    round($packagePurchasePrice,2), //小數點第二位
                                    null,
                                    null,
                                    null,
                                    null,
                                    null,
                                ];
                                $t++;
                            }
                        }
                    }
                }
                if(count($purchaeOrders) > 1){
                    $data[] = [null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null]; //空一行
                }
                $t++;
            }
        }
        $this->total = $t;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $total = $this->total;
        for($i=2;$i<=$total;$i++){
            $sheet->getStyle('A'.($i).':P'.($i))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        $sheet->mergeCells('A1:M1'); //合併A1-M1
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('N1:P1');
        $sheet->getStyle('N1:P1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('G')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('N1')->getFont()->setSize(12)->setBold(true);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        return '採購單組合-單品';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 10,
            'C' => 14,
            'D' => 10,
            'E' => 30,
            'F' => 20,
            'G' => 60,
            'H' => 25,
            'I' => 8,
            'J' => 10,
            'K' => 12,
            'L' => 12,
            'M' => 12,
            'N' => 10,
            'O' => 12,
            'P' => 15,
        ];
    }

    public function columnFormats(): array
    {
        return [
            // 'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, //日期
            'K' => NumberFormat::FORMAT_NUMBER_00, //金额保留两位小数
        ];
    }

    public function headings(): array
    {
        return [
            [
                '【直流電通】訂單採購單', //A1
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                "單據日期: ".date('Y-m-d'),
                '',
                '',
            ],
            [
                '序號',
                '採購單別',
                '採購單號',
                '廠商代號',
                '廠商名稱',
                '品號',
                '品名',
                '規格',
                '單位',
                '採購數量',
                '採購單價', //小數點第二位
                '採購金額', //單價 * 數量, 四捨五入
                '課稅別',
                '交貨庫別',
                '指定到貨日',
                '備註',
            ],
        ];
    }
}

