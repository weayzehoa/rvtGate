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
use App\Models\NidinSetBalance as NidinSetBalanceDB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

use App\Traits\NidinTicketFunctionTrait;

class NidinSetBalanceStatementExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
{
    use NidinTicketFunctionTrait;

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
        $setItemDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $setItemDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();

        $purchaseOrderItemIds = $setItemDiscountItemIds = $data = [];
        $startDate = $this->param['start_date'];
        $endDate = $this->param['end_date'];
        $vendor = $this->param['vendor'];
        $filename = $this->param['filename'];
        $stockinItems = $this->param['stockinItems'];
        $discounts = $this->param['discounts'];
        $line1 = $i = $setTotal = 0;
        $servieRate = $this->getNidinServiceFee($vendor->merchant_no,$vendor->merchant_key,'writeoff');
        //套票資料
        $setItems = NidinSetBalanceDB::where([['is_close',1],['is_lock',0]])->whereBetween('close_date',[$startDate,$endDate])->get();
        if(!empty($setItems) && count($setItems) >0 ){
            foreach($setItems as $setItem){
                if(strtotime($startDate) <= strtotime($setItem->close_date) && strtotime($setItem->close_date) <= strtotime($endDate)) {
                    $setNo = $setItem->set_no;
                    $setQty = $setItem->set_qty;
                    $setData = OrderItemDB::join($nidinOrderTable,$nidinOrderTable.'.order_id',$orderItemTable.'.order_id')
                    ->where($orderItemTable.'.set_no',$setNo)
                    ->select([
                        $orderItemTable.'.set_no',
                        $nidinOrderTable.'.nidin_order_no',
                        $nidinOrderTable.'.transaction_id',
                        DB::raw("SUM($orderItemTable.price - $orderItemTable.discount) as pay_total"),
                        DB::raw("SUM(1) as set_count"),
                        DB::raw("SUM(CASE WHEN $orderItemTable.writeoff_date is not null THEN $orderItemTable.price - $orderItemTable.discount ELSE 0 END) as writeoff_total"),
                        DB::raw("SUM(CASE WHEN $orderItemTable.return_date is not null THEN $orderItemTable.return_amount ELSE 0 END) as return_total"),
                        DB::raw("SUM(CASE WHEN $orderItemTable.writeoff_date is not null THEN 1 ELSE 0 END) as writeoff_count"),
                        DB::raw("SUM(CASE WHEN $orderItemTable.return_date is not null THEN 1 ELSE 0 END) as return_count"),
                    ])->groupBy($orderItemTable.'.set_no')->first();
                    // $setItem->update(['is_lock' => 1]);
                    $total = round($setItem->remain * (1 - $servieRate),4);
                    $setTotal += $total;
                    $data[$i] = [
                        $setItem->close_date, // '結算日期',
                        $setData->nidin_order_no, // '你訂訂單號',
                        $setData->transaction_id, // '金流序號',
                        $setData->set_no, // '套票號碼',
                        $setData->pay_total, // '付款總額',
                        $setData->writeoff_total, // '核銷總額',
                        $setData->return_total, // '退款總額',
                        $setItem->remain, // '餘　　額',
                        $servieRate, // '採購單價',
                        $total, // '合計(含稅)',
                        $setData->writeoff_count, // '核銷數量',
                        $setData->return_count, // '退貨數量',
                        $setData->set_count, // '總計數量',
                    ];
                    $i++;
                }
            }
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
                $setTotal,
                '',
                '',
                '',
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
        $sheet->getStyle('A5:M5')->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A5:M5')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A'.($this->line1 + 6).':M'.($this->line1 + 6))->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A'.($this->line1 + 6).':M'.($this->line1 + 6))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('M4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E5:M5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        $vendor = $this->param['vendor'];
        $startDate = substr(str_replace('-','',$this->param['start_date']),4);
        $endDate = substr(str_replace('-','',$this->param['end_date']),4);;
        return '套票對帳報表('.$startDate.'~'.$endDate.')';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 12,
            'J' => 12,
            'K' => 12,
            'L' => 12,
            'M' => 12,
        ];
    }

    public function headings(): array
    {
        $vendor = $this->param['vendor'];
        $startDate = $this->param['start_date'];
        $endDate = $this->param['end_date'];
        return [
            [
                $vendor->name.' 套票對帳報表', //A1
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
                '',
                '',
                "期間: $startDate 至 $endDate",
            ],
            [
                '結算日期', //A1
                '你訂訂單號',
                '金流序號',
                '套票號碼',
                '付款總額',
                '核銷總額',
                '退款總額',
                '餘　　額',
                '手續費率',
                '合　　計',
                '核銷數量',
                '退貨數量',
                '總計數量',
            ],
        ];
    }
}
