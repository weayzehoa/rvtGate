<?php

namespace App\Exports\Sheets;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\PurchaseOrderFunctionTrait;

class PurchaseOrderStockinExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
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
        $vendor_arrival_date = null;
        $vendor_arrival_date_end = null;
        !empty($this->param['vendor_arrival_date']) ? $vendor_arrival_date = $this->param['vendor_arrival_date'] : '';
        !empty($this->param['vendor_arrival_date_end']) ? $vendor_arrival_date_end = $this->param['vendor_arrival_date_end'] : '';
        if(!empty($this->param['con'])){
            //將進來的資料作參數轉換
            foreach ($this->param['con'] as $key => $value) {
                $$key = $value;
            }
        }

        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        $purchaseNos = $this->getPurchaseOrderData($this->param);
        $items = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemSingleTable.'.purchase_no')
            ->where($purchaseOrderItemSingleTable.'.quantity','>',0)
            ->whereIn($purchaseOrderItemSingleTable.'.purchase_no',$purchaseNos);

        !empty($vendor_arrival_date) ? $items = $items->where($purchaseOrderItemSingleTable.'.vendor_arrival_date','>=',$vendor_arrival_date) : '';
        !empty($vendor_arrival_date_end) ? $items = $items->where($purchaseOrderItemSingleTable.'.vendor_arrival_date','<=',$vendor_arrival_date_end) : '';

        $items = $items->where($purchaseOrderItemSingleTable.'.is_del',0)
            ->whereNotIn($purchaseOrderTable.'.vendor_id',[717,723,729,730]) //排除錢街跟你訂
            ->where(function($query) use ($purchaseOrderItemSingleTable){ //排除直寄
                $query->where($purchaseOrderItemSingleTable.'.direct_shipment',0)
                ->orWhereNull($purchaseOrderItemSingleTable.'.direct_shipment');
            })->select([
                $purchaseOrderItemSingleTable.'.*',
                // $productTable.'.name as product_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productTable.'.vendor_price',
                $productTable.'.price as product_price',
                $productTable.'.serving_size',
                $productTable.'.direct_shipment',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.sku',
                // $productModelTable.'.gtin13', 改由記錄當下的gtin13替代
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee',
                DB::raw("DATE_FORMAT($purchaseOrderTable.synced_time ,'%Y-%m-%d') as purchase_date"),
                DB::raw("SUM($purchaseOrderItemSingleTable.quantity) as purchase_quantity")
            ])->groupBy($purchaseOrderItemSingleTable.'.vendor_arrival_date',$purchaseOrderItemSingleTable.'.product_model_id')
            ->orderBy($purchaseOrderItemSingleTable.'.vendor_arrival_date','asc')->get();

        $i=0;
        if(count($items) > 0){
            $i = 1;
            foreach($items as $item){
                $data[] = [
                    $i,
                    !empty($item->gtin13) ? $item->gtin13 : $item->sku,
                    '',
                    '',
                    '',
                    $item->product_name,
                    '',
                    $item->purchase_quantity,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $item->vendor_arrival_date,
                    '',
                ];
                $i++;
            }
        }
        $this->total = $i;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $total = $this->total;
        $border = [ //全部框,細線
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        for($i=2;$i<=$total+2;$i++){
            $sheet->getStyle('A'.($i).':Q'.($i))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('M1:O1');
        $sheet->mergeCells('P1:Q1');
        $sheet->mergeCells('A2:A3');
        $sheet->mergeCells('B2:B3');
        $sheet->mergeCells('C2:C3');
        $sheet->mergeCells('D2:D3');
        $sheet->mergeCells('E2:E3');
        $sheet->mergeCells('F2:F3');
        $sheet->mergeCells('G2:G3');
        $sheet->mergeCells('H2:H3');
        $sheet->mergeCells('I2:I3');
        $sheet->mergeCells('M2:M3');
        $sheet->mergeCells('N2:N3');
        $sheet->mergeCells('O2:O3');
        $sheet->mergeCells('P2:P3');
        $sheet->mergeCells('Q2:Q3');
        $sheet->mergeCells('J2:L2');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle("F")->getAlignment()->setWrapText(true);;
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('O1')->getFont()->setSize(12)->setBold(true);
        $sheet->getStyle('S1')->getFont()->setSize(12)->setBold(true);
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    public function title(): string
    {
        return '入庫單';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 16,
            'C' => 18,
            'D' => 12,
            'E' => 18,
            'F' => 60,
            'G' => 16,
            'H' => 12,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 10,
            'M' => 10,
            'N' => 10,
            'O' => 15,
            'P' => 15,
            'Q' => 15,
        ];
    }
    public function headings(): array
    {

        $vendor_arrival_date = null;
        $vendor_arrival_date_end = null;
        !empty($this->param['vendor_arrival_date']) ? $vendor_arrival_date = $this->param['vendor_arrival_date'] : '';
        !empty($this->param['vendor_arrival_date_end']) ? $vendor_arrival_date_end = $this->param['vendor_arrival_date_end'] : '';
        if(!empty($this->param['con'])){
            //將進來的資料作參數轉換
            foreach ($this->param['con'] as $key => $value) {
                $$key = $value;
            }
        }
        $start = $vendor_arrival_date;
        $end = $vendor_arrival_date_end;
        if(!empty($start)){
            if(!empty($end)){
                $date = $start.' ~ '.$end;
            }else{
                $date = $start.' ~ 日期未填寫';
            }
        }elseif(!empty($end)){
            $date = '日期未填寫 ~ '.$end;
        }else{
            $date = null;
        }
        return [
            [
                '【直流電通】商品入庫管理表', //A1
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
                '貨運公司:', //M
                '',
                '', //O1
                '日期: '.$date,
                '',
            ],
            [
                '序號',
                '商品條碼(貨號)',
                '參考號(國際條碼)',
                '入庫單號',
                '廠商',
                '商品名稱',
                '商品規格',
                '預收數量',
                '實收數量',
                '商品尺寸(cm)',
                '',
                '',
                '重量(kg)',
                '生產日期',
                '有效日期',
                '預計到貨日',
                '備註',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '長度',
                '宽度',
                '高度',
                '',
                '',
                '',
                '',
                '',
            ],
        ];
    }
}

