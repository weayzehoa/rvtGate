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
use App\Models\SyncedOrderItem as SyncedOrderItemDB;

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

class PurchaseOrderDetailExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths,WithColumnFormatting
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

        $purchaseOrderItems = $this->getPurchaseOrderItemData($purchaseNos = $this->getPurchaseOrderData($this->param,'OrderDetail'));
        $i = $totalOrderCount = $totalQty = $totalPrice = 0;
        if(count($purchaseOrderItems) > 0){
            $i = 3;
            foreach($purchaseOrderItems as $item){
                $tmp = ErpPURTCDB::find($item->erp_purchase_no);
                $item->direct_shipment == 1 ? $wareHouse = '廠商倉' : $wareHouse = '成品倉';
                $orderDate = str_replace('-','/',explode(' ',$item->orderDate)[0]);
                $orderIds = explode(',',$item->order_ids);
                $orderTemp = SyncedOrderItemDB::whereIn('order_id',$orderIds)
                ->where([['product_model_id',$item->product_model_id],['purchase_no',$item->purchase_no],['is_del',0]])->get();
                $orderCount = 0;
                if(count($orderTemp) > 0){
                    foreach($orderTemp as $temp){
                        $orderCount += $temp->quantity;
                    }
                }
                $totalOrderCount += $orderCount;
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
                $price = round($item->quantity * $item->purchase_price,2);
                $quantity = $item->quantity;
                $totalQty += $quantity;
                $totalPrice += $price;
                $data[] = [
                    $orderDate,  // 單據日期(中繼)
                    $item->purchase_no,  // 採購單號(中繼)
                    $tmp->TC004,  // 廠商代號
                    $item->vendor_name,  // 簡稱
                    'NTD',  // 幣別
                    '1.00',  // 匯率
                    $taxType,  // 課稅別
                    $tmp->TC032,  // 付款條件代號
                    $tmp->TC008,  // 付款條件名稱
                    $item->digiwin_no,  // 品    號
                    $item->product_name,  // 品       名
                    $item->serving_size,  // 規       格
                    $wareHouse,  // 交貨庫
                    str_replace('-','/',$item->vendor_arrival_date),  // 預交日
                    $item->quantity,  // 採購數量
                    $item->unit_name,  // 單位
                    round($item->purchase_price,4),  // 單    價
                    round($item->quantity * $item->purchase_price,2),  // 金    額
                    $orderCount,  // 訂單數
                ];
                $i++;
            }
        }
        $data[$i] = [
            null,
            null,
            '小計：',
            null,
            null,
            0,
            count($purchaseNos).'張',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $totalQty,
            null,
            0,
            round($totalPrice,2),
            $totalOrderCount,  // 訂單數
        ];
        $i++;
        $this->count = $i;
        $data[] = [null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null];
        $data[] = ['核淮：',null,null,null,null,null,null,'覆核：',null,null,null,null,null,null,'申請人/製表：',null,null,null];
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->count;
        for($i=1;$i<$count;$i++){
            $sheet->getStyle('A'.$i.':S'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        $sheet->getStyle('A2:S2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00EEEEEE');
        $sheet->getStyle('A'.($this->count -1).':S'.($this->count -1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00EEEEEE');
        $sheet->getStyle('D')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('M')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('L')->getAlignment()->setWrapText(true); //自動換行
        $sheet->mergeCells('A1:S1'); //合併第一行A-S
        $sheet->getRowDimension(1)->setRowHeight(24); //第一行高度30
        $sheet->getStyle('A1')->getFont()->setSize(18)->setBold(true); //第一行字型大小
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('Q')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('O')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('S')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('P')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    public function title(): string
    {
        return '採購明細';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 16,
            'C' => 12,
            'D' => 25,
            'E' => 6,
            'F' => 6,
            'G' => 10,
            'H' => 15,
            'I' => 20,
            'J' => 16,
            'K' => 60,
            'L' => 30,
            'M' => 10,
            'N' => 15,
            'O' => 12,
            'P' => 8,
            'Q' => 10,
            'R' => 10,
            'S' => 8,
        ];
    }

    public function columnFormats(): array
    {
        return [
            // 'B' => NumberFormat::FORMAT_DATE_DDMMYYYY, //日期
            // 'O' => NumberFormat::FORMAT_NUMBER, //金额保留两位小数
            // 'Q' => NumberFormat::FORMAT_NUMBER_0000,
            // 'R' => NumberFormat::FORMAT_NUMBER_00,
            // 'F' => NumberFormat::FORMAT_NUMBER_0000,
        ];
    }

    public function headings(): array
    {
        return [
            [
                '採購明細',
            ],
            [
            '單據日期',
            '採購單號',
            '廠商代號',
            '簡稱',
            '幣別',
            '匯率',
            '課稅別',
            '付款條件代號',
            '付款條件名稱',
            '品    號',
            '品       名',
            '規       格',
            '交貨庫',
            '預交日',
            '採購數量',
            '單位',
            '單    價',
            '金    額',
            '訂單數',
            ]
        ];
    }
}
