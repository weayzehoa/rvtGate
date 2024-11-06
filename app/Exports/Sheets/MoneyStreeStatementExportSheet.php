<?php

namespace App\Exports\Sheets;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\Statement as StatementDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\AcOrder as AcOrderDB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

class MoneyStreeStatementExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
{
    protected $param;
    protected $statementNo;

    public function __construct(array $param)
    {
        $this->param = $param;
        //找出今日建立對帳單的最後一筆單號
        $tmp = StatementDB::where('statement_no','>=',date('ymd').'00001')->select('statement_no')->orderBy('statement_no','desc')->first();
        !empty($tmp) ? $this->statementNo = $tmp->statement_no + 1 : $this->statementNo = date('ymd').'00001';
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $acOrderTable = env('DB_DATABASE').'.'.(new AcOrderDB)->getTable();

        $purchaseOrderItemIds = $returnDiscountItemIds = $data = [];
        $startDate = $this->param['start_date'];
        $endDate = $this->param['end_date'];
        $vendor = $this->param['vendor'];
        $filename = $this->param['filename'];
        $stockinItems = $this->param['stockinItems'];
        $discounts = $this->param['discounts'];
        //找進退貨資料
        $line1 = $line2 = $i = $hasReturn = $returnQtys = $returnPrice = $stockinQtys = $stockinPrice = 0;
        $purchaseNos = [];
        if(!empty($stockinItems) && count($stockinItems) > 0){
            foreach($stockinItems as $stockinItem){
                $chkItemStockin = 0;
                $item = $stockinItem->purchaseOrderItem;
                $serialNo = $stockinItem->serial_no;
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
                        $data[$i] = [
                            $stockinDate, //A1
                            $item->purchase_no,
                            $item->digiwin_no,
                            $item->product_name,
                            $item->serving_size,
                            $item->unit_name,
                            '進貨',
                            $stockinQty,
                            0,
                            in_array($vendor->id,[717,729,730]) ? round($item->purchase_price,4) : round($item->purchase_price,0),
                            in_array($vendor->id,[717,729,730]) ? round($stockinQty * $item->purchase_price,4) : round($stockinQty * $item->purchase_price,0),
                            $item->direct_shipment == 1 ? '廠商倉' : '成品倉',
                            $serialNo
                        ];
                        in_array($vendor->id,[717,729,730]) ? $stockinPrice += round($stockinQty * $item->purchase_price,4) : $stockinPrice += round($stockinQty * $item->purchase_price,0);
                        $stockinQtys += $stockinQty;
                        $i++;
                        if(count($item->returns) > 0){
                            $hasReturn = 1;
                            foreach($item->returns as $return){
                                if(strtotime($startDate) <= strtotime($return->return_date) && strtotime($return->return_date) <= strtotime($endDate)) {
                                    $return->update(['is_lock' => 1]);
                                    $returnDiscount = ReturnDiscountDB::where([['return_discount_no',$return->return_discount_no],['is_del',0],['is_lock',0]])->first();
                                    !empty($returnDiscount) ? $returnDiscount->update(['is_lock' => 1]) : '';
                                    $returnDiscountItemIds[] = $return->id;
                                    $returnQtys += $return->quantity;
                                    $returnPrice += round($return->quantity * $return->purchase_price,4);
                                    $data[$i] = [
                                        $return->return_date, //A1
                                        $item->purchase_no,
                                        $item->digiwin_no,
                                        $item->product_name,
                                        $item->serving_size,
                                        $item->unit_name,
                                        '退貨',
                                        0,
                                        $return->quantity,
                                        in_array($vendor->id,[717,729,730]) ? round($item->purchase_price,4) : round($item->purchase_price,0),
                                        in_array($vendor->id,[717,729,730]) ? -round($return->quantity * $item->purchase_price,4) : -round($return->quantity * $item->purchase_price,0),
                                        $item->direct_shipment == 1 ? '廠商倉' : '成品倉',
                                        $serialNo
                                    ];
                                    $i++;
                                }
                            }
                        }
                    }else{
                        //將所有已經進貨的資料鎖定
                        $stockinItem->update(['is_lock' => 1]);
                        $stin = StockinItemSingleDB::where([['purchase_no',$item->purchase_no],['product_model_id',$stockinItem->product_model_id]])->whereBetween('stockin_date',[$startDate,$endDate])->update(['is_lock' => 1, 'statement_no' => $this->statementNo]);
                        $purchaseOrderItemIds[] = $item->id;
                        $data[$i] = [
                            $stockinDate, //A1
                            $item->purchase_no,
                            $item->digiwin_no,
                            $item->product_name,
                            $item->serving_size,
                            $item->unit_name,
                            '進貨',
                            $stockinQty,
                            0,
                            in_array($vendor->id,[717,729,730]) ? round($item->purchase_price,4) : round($item->purchase_price,0),
                            in_array($vendor->id,[717,729,730]) ? round($stockinQty * $item->purchase_price,4) : round($stockinQty * $item->purchase_price,0),
                            $item->direct_shipment == 1 ? '廠商倉' : '成品倉',
                            $serialNo
                        ];
                        in_array($vendor->id,[717,729,730]) ? $stockinPrice += round($stockinQty * $item->purchase_price,4) : $stockinPrice += round($stockinQty * $item->purchase_price,0);
                        $stockinQtys += $stockinQty;
                        $i++;
                        if(count($item->returns) > 0){
                            $hasReturn = 1;
                            foreach($item->returns as $return){
                                if(strtotime($startDate) <= strtotime($return->return_date) && strtotime($return->return_date) <= strtotime($endDate)) {
                                    $return->update(['is_lock' => 1]);
                                    $returnDiscount = ReturnDiscountDB::where([['return_discount_no',$return->return_discount_no],['is_del',0],['is_lock',0]])->first();
                                    $returnDiscount->update(['is_lock' => 1]);
                                    $returnDiscountItemIds[] = $return->id;
                                    $returnQtys += $return->quantity;
                                    in_array($vendor->id,[717,729,730]) ? $returnPrice += round($return->quantity * $return->purchase_price,4) : $returnPrice += round($return->quantity * $return->purchase_price,0);
                                    $data[$i] = [
                                        $return->return_date, //A1
                                        $item->purchase_no,
                                        $item->digiwin_no,
                                        $item->product_name,
                                        $item->serving_size,
                                        $item->unit_name,
                                        '退貨',
                                        0,
                                        $return->quantity,
                                        in_array($vendor->id,[717,729,730]) ? round($item->purchase_price,4) : round($item->purchase_price,0),
                                        in_array($vendor->id,[717,729,730]) ? -round($return->quantity * $item->purchase_price,4) : -round($return->quantity * $item->purchase_price,0),
                                        $item->direct_shipment == 1 ? '廠商倉' : '成品倉',
                                        $serialNo
                                    ];
                                    $i++;
                                }
                            }
                        }
                    }
                }
            }
        }

        //找未被列入的退貨
        $returnItems = ReturnDiscountItemDB::join($returnDiscountTable,$returnDiscountTable.'.return_discount_no',$returnDiscountItemTable.'.return_discount_no')
        ->join($productModelTable,$productModelTable.'.id',$returnDiscountItemTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->where([
            [$returnDiscountTable.'.vendor_id',$vendor->id],
            [$returnDiscountTable.'.type','A351'],
            [$returnDiscountTable.'.is_del',0],
            [$returnDiscountTable.'.is_lock',0],
            [$returnDiscountItemTable.'.is_del',0],
            [$returnDiscountItemTable.'.is_lock',0],
        ])->whereBetween($returnDiscountTable.'.return_date',[$startDate,$endDate])
        ->select([
            $returnDiscountItemTable.'.*',
            $returnDiscountTable.'.return_date',
            $productModelTable.'.digiwin_no',
            $productTable.'.serving_size',
            $productTable.'.unit_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            'serial_no' => AcOrderDB::whereColumn($acOrderTable.'.purchase_no',$returnDiscountItemTable.'.purchase_no')->select($acOrderTable.'.serial_no')->limit(1),
        ])->get();

        if(!empty($returnItems) && count($returnItems) >0 ){
            foreach($returnItems as $return){
                if(strtotime($startDate) <= strtotime($return->return_date) && strtotime($return->return_date) <= strtotime($endDate)) {
                    $hasReturn = 1;
                    $return->update(['is_lock' => 1]);
                    $returnDiscount = ReturnDiscountDB::where([['return_discount_no',$return->return_discount_no],['is_del',0],['is_lock',0]])->first();
                    !empty($returnDiscount) ? $returnDiscount->update(['is_lock' => 1]) : '';
                    $returnDiscountItemIds[] = $return->id;
                    $returnQtys += $return->quantity;
                    in_array($vendor->id,[717,729,730]) ? $returnPrice += round($return->quantity * $return->purchase_price,4) : $returnPrice += round($return->quantity * $return->purchase_price,0);
                    $data[$i] = [
                        $return->return_date, //A1
                        $return->purchase_no,
                        $return->digiwin_no,
                        $return->product_name,
                        $return->serving_size,
                        $return->unit_name,
                        '退貨',
                        0,
                        $return->quantity,
                        in_array($vendor->id,[717,729,730]) ? round($return->purchase_price,4) : round($return->purchase_price,0),
                        in_array($vendor->id,[717,729,730]) ? -round($return->quantity * $return->purchase_price,4) : -round($return->quantity * $return->purchase_price,0),
                        $return->direct_shipment == 1 ? '廠商倉' : '成品倉',
                        $return->serial_no,
                    ];
                    $i++;
                }
            }
        }

        //找折抵單資料
        $discountQtys = $discountPrice = 0;
        if(!empty($discounts) && count($discounts) > 0){
            foreach($discounts as $discount){
                $discount->update(['is_lock' => 1]);
                foreach($discount->items as $item){
                    if($item->is_lock == 0){
                        // 將折抵單內商品鎖住，不可修改
                        $item->update(['is_lock' => 1]);
                        $returnDiscountItemIds[] = $item->id;
                        $discountQtys++;
                        in_array($vendor->id,[717,729,730]) ? $discountPrice += round($item->purchase_price,4) : $discountPrice += round($item->purchase_price,0);
                        $data[$i] = [
                            $discount->return_date, //A1
                            '',
                            $item->digiwin_no,
                            $item->product_name,
                            $item->serving_size,
                            $item->unit_name,
                            '折抵',
                            0,
                            1,
                            in_array($vendor->id,[717,729,730]) ? round($item->purchase_price,4) : round($item->purchase_price,0),
                            in_array($vendor->id,[717,729,730]) ? -round($item->purchase_price,4) : -round($item->purchase_price,0),
                            $item->direct_shipment == 1 ? '廠商倉' : '成品倉',
                            ''
                        ];
                        $i++;
                    }
                }
            }
        }

        if(count($data) > 0){
            $line1 = $i;
            //計算
            if(!empty($stockinItems) && count($stockinItems) > 0){
                $data[$i] = [
                    '', //A1
                    '',
                    '',
                    '',
                    '',
                    '小計：',
                    '進貨：',
                    $stockinQtys,
                    0,
                    '',
                    $stockinPrice,
                    '',
                    '',
                ];
                $i++;
            }
            if($hasReturn == 1){
                $data[$i] = [
                    '', //A1
                    '',
                    '',
                    '',
                    '',
                    '',
                    '退貨：',
                    0,
                    $returnQtys,
                    '',
                    -$returnPrice,
                    '',
                    '',
                ];
                $i++;
            }
            if(!empty($discounts) && count($discounts) > 0){
                $data[$i] = [
                    '', //A1
                    '',
                    '',
                    '',
                    '',
                    '',
                    '折抵：',
                    0,
                    $discountQtys,
                    '',
                    -$discountPrice,
                    '',
                    '',
                ];
                $i++;
            }
            $line2 = $i;
            $data[$i] = [
                '', //A1
                '',
                '',
                '',
                '',
                '總計：',
                '',
                $stockinQtys,
                $returnQtys + $discountQtys,
                '',
                $stockinPrice - $returnPrice - $discountPrice,
                '',
                '',
            ];
            if(count($discounts) > 0 || count($stockinItems) > 0){
                $purchaseNos = array_unique($purchaseNos);
                sort($purchaseNos);
                $statement = StatementDB::create([
                    'statement_no' => $this->statementNo,
                    'vendor_id' => $vendor->id,
                    'amount' => $stockinPrice - $returnPrice - $discountPrice,
                    'stockin_price' => $stockinPrice,
                    'return_price' => $returnPrice,
                    'discount_price' => $discountPrice,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'purchase_nos' => join(',',$purchaseNos),
                    'purchase_item_ids' => join(',',$purchaseOrderItemIds),
                    'return_discount_ids' => join(',',$returnDiscountItemIds),
                    'filename' => $filename,
                ]);
            }
        }
        $this->line1 = $line1;
        $this->line2 = $line2;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5:M5')->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A5:M5')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A'.($this->line1 + 6).':M'.($this->line1 + 6))->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A'.($this->line2 + 6).':M'.($this->line2 + 6))->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $sheet->getStyle('A'.($this->line2 + 6).':M'.($this->line2 + 6))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D")->getAlignment()->setWrapText(true);;
        $sheet->getStyle("E")->getAlignment()->setWrapText(true);;
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        return '錢街對帳報表';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 14,
            'C' => 18,
            'D' => 60,
            'E' => 40,
            'F' => 10,
            'G' => 10,
            'H' => 12,
            'I' => 12,
            'J' => 12,
            'K' => 12,
            'L' => 12,
            'M' => 24,
        ];
    }
    public function headings(): array
    {
        $vendor = $this->param['vendor'];
        $startDate = $this->param['start_date'];
        $endDate = $this->param['end_date'];
        return [
            [
                $vendor->name.' 對帳報表', //A1
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
                "期間: $startDate 至 $endDate",
                ""
            ],
            [
                '進退貨日', //A1
                '採購單號',
                '品　　號',
                '品　　名',
                '規　　格',
                '單　　位',
                '異動別',
                '計價數量',
                '退貨數量',
                '單　　價',
                '合計(含稅)',
                '交貨庫別',
                '金流序號',
            ],
        ];
    }
}
