<?php

namespace App\Exports\Sheets;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\Statement as StatementDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\NidinOrder as NidinOrderDB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

class NidinReturnStatementExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
{
    protected $param;
    protected $statementNo;

    public function __construct(array $param)
    {
        $this->param = $param;
        $this->statementNo = $param['statementNo'];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $nidinOrderTable = env('DB_DATABASE').'.'.(new NidinOrderDB)->getTable();
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();

        $purchaseOrderItemIds = $returnDiscountItemIds = $data = [];
        $startDate = $this->param['start_date'];
        $endDate = $this->param['end_date'];
        $vendor = $this->param['vendor'];
        $filename = $this->param['filename'];
        $stockinItems = $this->param['stockinItems'];
        $discounts = $this->param['discounts'];
        //找進貨核銷資料
        $line1 = $i = $discountQtys = $discountPrice = $returnQtys = $returnPrice = $stockinQtys = $stockinPrice = 0;
        $purchaseNos = [];

        //退貨資料
        $returnItems = OrderItemDB::join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->join($nidinOrderTable,$nidinOrderTable.'.order_id',$orderItemTable.'.order_id')
        ->whereBetween($orderItemTable.'.return_date',[$startDate,$endDate])
        ->whereNotNull($orderItemTable.'.ticket_no')
        ->where([
            [$vendorTable.'.id',$vendor->id],
            [$orderItemTable.'.is_del',1],
            [$orderItemTable.'.is_statement',0]
        ])->select([
            $orderItemTable.'.*',
            $productModelTable.'.digiwin_no',
            $productTable.'.serving_size',
            $productTable.'.unit_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            $nidinOrderTable.'.order_number',
            $nidinOrderTable.'.nidin_order_no',
            $nidinOrderTable.'.transaction_id',
        ])->get();
        $orderItemIds = $orderIds = [];
        if(!empty($returnItems) && count($returnItems) >0 ){
            foreach($returnItems as $return){
                if(strtotime($startDate) <= strtotime($return->return_date) && strtotime($return->return_date) <= strtotime($endDate)) {
                    $orderIds[] = $return->order_id;
                    $orderItemIds[] = $return->id;
                    $return->update(['is_statement' => 1]);
                    $returnQtys++;
                    $returnPrice += $return->return_amount;
                    $data[$i] = [
                        $return->return_date, //A1
                        $return->nidin_order_no,
                        $return->transaction_id,
                        $return->set_no,
                        $return->ticket_no,
                        $return->digiwin_no,
                        $return->product_name,
                        $return->unit_name,
                        '退貨',
                        1,
                        $return->return_amount,
                    ];
                    $i++;
                }
            }
            $orderIds = array_unique($orderIds);
            sort($orderIds);
        }

        if(count($data) > 0){
            $line1 = $i;
            $data[$i] = [
                '', //A1
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '總計：',
                $returnQtys,
                $returnPrice,
            ];
        }
        $this->line1 = $line1;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5:K5')->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A5:K5')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A'.($this->line1 + 6).':K'.($this->line1 + 6))->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A'.($this->line1 + 6).':K'.($this->line1 + 6))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("G")->getAlignment()->setWrapText(true);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('K4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        $vendor = $this->param['vendor'];
        $startDate = substr(str_replace('-','',$this->param['start_date']),4);
        $endDate = substr(str_replace('-','',$this->param['end_date']),4);;
        return '退貨對帳報表('.$startDate.'~'.$endDate.')';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 60,
            'H' => 6,
            'I' => 12,
            'J' => 12,
            'K' => 12,
        ];
    }

    public function headings(): array
    {
        $vendor = $this->param['vendor'];
        $startDate = $this->param['start_date'];
        $endDate = $this->param['end_date'];
        return [
            [
                $vendor->name.' 退貨對帳報表', //A1
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
            ],
            [
                '廠商代號： A'.str_pad($vendor->id,5,'0',STR_PAD_LEFT), //A1
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
            ],
            [
                '廠商簡稱： '.$vendor->name, //A1
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
            ],
            [
                '對帳單號： '.$this->statementNo, //A1
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                "期間: $startDate 至 $endDate",
            ],
            [
                '退貨日期', //A1
                '你訂訂單號',
                '金流序號',
                '套票號碼',
                '票券號碼',
                '品　　號',
                '品　　名',
                '單位',
                '異動別',
                '計價數量',
                '退款金額',
            ],
        ];
    }
}
