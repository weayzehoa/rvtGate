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

class NidinStatementExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
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
        $line1 = $line1 = $i = $discountQtys = $discountPrice = $returnQtys = $returnPrice = $stockinQtys = $stockinPrice = 0;
        $purchaseNos = [];
        if(!empty($stockinItems) && count($stockinItems) > 0){
            foreach($stockinItems as $stockinItem){
                $chkItemStockin = 0;
                $item = $stockinItem->purchaseOrderItem;
                $purchaseNos[] = $stockinItem->purchase_no;
                if($item->is_lock == 0){
                    $stockinQty = 0;
                    if($item->is_close == 1){
                        $chkItemStockin = 1;
                        if(strstr($item->sku,'BOM')){ //被指定結案且又是組合品時, 需要算出實際上組合品的進貨數量
                            foreach($item->exportPackage as $package){
                                if(count($package->stockins) > 0){
                                    $packageStockinQty = 0;
                                    foreach($package->stockins as $stockin){
                                        $packageStockinQty += $stockin->stockin_quantity;
                                        $stockinDate = $stockin->stockin_date;
                                    }
                                    $useQty = 0;
                                    $packageData = json_decode(str_replace('	','',$item->package_data));
                                    if(count($packageData) > 0){
                                        foreach($packageData as $pp){
                                            $lists = $pp->lists;
                                            if(count($lists) > 0){
                                                foreach($lists as $list){
                                                    if($list->sku == $package->sku){
                                                        $useQty = $list->quantity;
                                                        if($useQty > 0){
                                                            $stockinQty = $packageStockinQty / $useQty;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }else{
                            if(count($item->stockins) > 0){
                                foreach($item->stockins as $stockin){
                                    $stockinQty += $stockin->stockin_quantity;
                                    $stockinDate = $stockin->stockin_date;
                                }
                            }
                        }
                    }else{
                        if(strstr($item->sku,'BOM')){
                            $chkPackageStockin = 0;
                            foreach($item->exportPackage as $package){
                                if(count($package->stockins) > 0){
                                    $chkStockinQty = 0;
                                    foreach($package->stockins as $stockin){
                                        if(strtotime($startDate) <= strtotime($stockin->stockin_date) && strtotime($stockin->stockin_date) <= strtotime($endDate)){
                                            $chkStockinQty += $stockin->stockin_quantity;
                                            $stockinDate = $stockin->stockin_date;
                                        }
                                    }
                                }
                                if($chkStockinQty == $package->quantity){
                                    $chkPackageStockin++;
                                }else{ //部分入庫須找出實際進貨組合品數量
                                    $useQty = 0;
                                    $packageData = json_decode(str_replace('	','',$item->package_data));
                                    if(count($packageData) > 0){
                                        foreach($packageData as $pp){
                                            $lists = $pp->lists;
                                            if(count($lists) > 0){
                                                foreach($lists as $list){
                                                    if($list->sku == $package->sku){
                                                        $useQty = $list->quantity;
                                                        if($useQty > 0){
                                                            $stockinQty = $chkStockinQty / $useQty;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if($chkPackageStockin == count($item->exportPackage)){ //組合商品完成進貨
                                $chkItemStockin = 1;
                                $stockinQty = $item->quantity;
                            }
                        }else{
                            if(count($item->stockins) > 0){
                                $chkStockinQty = 0;
                                foreach($item->stockins as $stockin){
                                    if(strtotime($startDate) <= strtotime($stockin->stockin_date) && strtotime($stockin->stockin_date) <= strtotime($endDate)){
                                        $stockinQty = $chkStockinQty += $stockin->stockin_quantity;
                                        $stockinDate = $stockin->stockin_date;
                                    }
                                }
                            }
                            if($chkStockinQty == $item->quantity){ //單品商品完成進貨
                                $chkItemStockin = 1;
                            }
                        }
                    }
                    if($chkItemStockin == 1){
                        // 將採購單內商品鎖住，不可修改
                        $item->update(['is_lock' => 1]);
                        $stin = StockinItemSingleDB::where([['purchase_no',$item->purchase_no],['product_model_id',$stockinItem->product_model_id]])->whereBetween('stockin_date',[$startDate,$endDate])->update(['is_lock' => 1, 'statement_no' => $this->statementNo]);
                        $purchaseOrderItemIds[] = $item->id;
                        foreach($stockinItem->nidinItems as $nidinItem){
                            $data[$i] = [
                                $stockinDate, //A1
                                $nidinItem->nidin_order_no,
                                $nidinItem->transaction_id,
                                $item->purchase_no,
                                $nidinItem->set_no,
                                $nidinItem->ticket_no,
                                $item->digiwin_no,
                                $item->product_name,
                                '核銷',
                                1,
                                round($nidinItem->price - $nidinItem->discount,4),
                                round($nidinItem->purchase_price,4),
                                round($nidinItem->purchase_price,4),
                            ];
                            $stockinPrice += round($nidinItem->purchase_price,4);
                            $stockinQtys++;
                            $i++;
                        }
                    }else{
                        //將所有已經進貨的資料鎖定
                        $stockinItem->update(['is_lock' => 1]);
                        $stin = StockinItemSingleDB::where([['purchase_no',$item->purchase_no],['product_model_id',$stockinItem->product_model_id]])->whereBetween('stockin_date',[$startDate,$endDate])->update(['is_lock' => 1, 'statement_no' => $this->statementNo]);
                        $purchaseOrderItemIds[] = $item->id;
                        foreach($stockinItem->nidinItems as $nidinItem){
                            $data[$i] = [
                                $stockinDate, //A1
                                $nidinItem->nidin_order_no,
                                $nidinItem->transaction_id,
                                $item->purchase_no,
                                $nidinItem->set_no,
                                $nidinItem->ticket_no,
                                $item->digiwin_no,
                                $item->product_name,
                                '核銷',
                                1,
                                round($nidinItem->price - $nidinItem->discount,4),
                                round($nidinItem->purchase_price,4),
                                round($nidinItem->purchase_price,4),
                            ];
                            $stockinPrice += round($nidinItem->purchase_price,4);
                            $stockinQtys++;
                            $i++;
                        }
                    }
                }
            }
        }

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
        $setItemIds = $orderItemIds = $orderIds = [];
        if(!empty($returnItems) && count($returnItems) >0 ){
            foreach($returnItems as $return){
                if(strtotime($startDate) <= strtotime($return->return_date) && strtotime($return->return_date) <= strtotime($endDate)) {
                    $orderIds[] = $return->order_id;
                    $orderItemIds[] = $return->id;
                    $returnPrice += $return->return_amount;
                }
            }
            $orderIds = array_unique($orderIds);
            sort($orderIds);
        }
        //套票資料
        $setItems = NidinSetBalanceDB::where([['is_close',1],['is_lock',0]])->whereBetween('close_date',[$startDate,$endDate])->get();
        if(!empty($setItems) && count($setItems) > 0){
            foreach($setItems as $setItem){
                $setItemIds[] = $setItem->id;
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
                $stockinQtys,
                '',
                '',
                $stockinPrice,
            ];
            if((!empty($discounts) && count($discounts) > 0) || (!empty($stockinItems) && count($stockinItems) > 0) || (!empty($returnItems) && count($returnItems) > 0)){
                $purchaseNos = array_unique($purchaseNos);
                sort($purchaseNos);
                $statement = StatementDB::create([
                    'statement_no' => $this->statementNo,
                    'vendor_id' => $vendor->id,
                    'amount' => $stockinPrice,
                    'stockin_price' => $stockinPrice,
                    'return_price' => 0,
                    'discount_price' => 0,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'purchase_nos' => join(',',$purchaseNos),
                    'purchase_item_ids' => join(',',$purchaseOrderItemIds),
                    'return_discount_ids' => join(',',$returnDiscountItemIds),
                    'return_order_ids' => join(',',$orderIds),
                    'return_order_item_ids' => join(',',$orderItemIds),
                    'set_item_ids' => join(',',$setItemIds),
                    'filename' => $filename,
                ]);
            }
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
        $sheet->getStyle("H")->getAlignment()->setWrapText(true);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('M4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        $vendor = $this->param['vendor'];
        $startDate = substr(str_replace('-','',$this->param['start_date']),4);
        $endDate = substr(str_replace('-','',$this->param['end_date']),4);;
        return '採購核銷對帳報表('.$startDate.'~'.$endDate.')';
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
            'G' => 20,
            'H' => 60,
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
                $vendor->name.' 採購核銷對帳報表', //A1
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
                '核銷採購日', //A1
                '你訂訂單號',
                '金流序號',
                '採購單號',
                '套票號碼',
                '票券號碼',
                '品　　號',
                '品　　名',
                '異動別',
                '計價數量',
                '銷售單價',
                '採購單價',
                '合計(含稅)',
            ],
        ];
    }
}
