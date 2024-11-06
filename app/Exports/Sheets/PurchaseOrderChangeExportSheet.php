<?php

namespace App\Exports\Sheets;

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

use App\Models\ErpPURTC as ErpPURTCDB;

use DB;
use App\Traits\PurchaseOrderFunctionTrait;

class PurchaseOrderChangeExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths,WithColumnFormatting
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
        $color = $data = [];
        $orderChanges = $this->getPurchaseOrderChangeData($purchaseNos = $this->getPurchaseOrderData($this->param,'OrderChange'));
        $i=0;
        if(count($orderChanges) > 0){
            $c = $i = 3;
            foreach($orderChanges as $item){
                $tmp = ErpPURTCDB::find($item->erp_purchase_no);
                $item->direct_shipment == 1 ? $wareHouse = '廠商倉' : $wareHouse = '成品倉';
                $orderDate = str_replace('-','/',explode(' ',$item->orderDate)[0]);
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
                !empty($item->quantity) ? $quantity = explode(' => ',$item->quantity) : $quantity = null;
                !empty($item->price) ? $price = explode(' => ',$item->price) : $price = null;
                $date = explode(' => ',$item->date);
                $data[] = [
                    $orderDate,  // 單據日期(中繼)
                    $item->purchase_no,  // 採購單號(中繼)
                    $item->created_at, // 變更時間
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
                    str_replace('-','/',$date[0]),  // 預交日
                    !empty($quantity) ? $quantity[0] : null,  // 採購數量
                    $item->unit_name,  // 單位
                    !empty($price) ? round($price[0],4) : null,  // 單    價
                    !empty($price) && !empty($quantity) ? round($quantity[0] * $price[0],2) : null,  // 金    額
                ];
                $c++;
                $totalPrice = null;
                if(!empty($price) && !empty($quantity)){
                    if(!empty($price)){
                        if(!empty($price[1])){
                            if(!empty($quantity[1]) || $quantity[1] == 0){
                                $totalPrice = round($price[1] * $quantity[1],2);
                            }else{
                                $totalPrice = round($price[1] * $quantity[0],2);
                            }
                        }else{
                            if(!empty($quantity[1]) || $quantity[1] == 0){
                                $totalPrice = round($price[0] * $quantity[1],2);
                            }else{
                                $totalPrice = round($price[0] * $quantity[0],2);
                            }
                        }
                    }
                }
                $data[] = [
                    null,  // 單據日期(中繼)
                    null,  // 採購單號(中繼)
                    null,
                    null,  // 廠商代號
                    null,  // 簡稱
                    null,  // 幣別
                    null,  // 匯率
                    null,  // 課稅別
                    null,  // 付款條件代號
                    null,  // 付款條件名稱
                    null,  // 品    號
                    null,  // 品       名
                    null,  // 規       格
                    null,  // 交貨庫
                    strstr($item->memo,'到貨日') ? str_replace('-','/',$date[1]) : null,  // 預交日
                    strstr($item->memo,'到貨日') ? null : (!empty($quantity) ? !empty($quantity[1]) || $quantity[1] == 0 ? $quantity[1] : $quantity[0] : null),  // 採購數量
                    strstr($item->memo,'到貨日') ? null : $item->unit_name,  // 單位
                    strstr($item->memo,'到貨日') ? null : (!empty($price) ? !empty($price[1]) || (isset($price[1]) && $price[1] == 0) ? round($price[1],4) : round($price[0],4) : null),  // 單    價
                    strstr($item->memo,'到貨日') ? null : $totalPrice,  // 金    額
                ];
                $color[] = $c;
                $c++;
                $i = $i+2;
            }
            $data[] = [null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null];
            $data[] = ['核淮：',null,null,null,null,null,null,'覆核：',null,null,null,null,null,null,'申請人/製表：',null,null,null];
        }
        $this->color = $color;
        $this->count = $i;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->count;
        $color = $this->color;
        for($i=1;$i<$count;$i++){
            $sheet->getStyle('A'.$i.':S'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        for($i=0;$i<count($color);$i++){
            $sheet->getStyle('A'.$color[$i].':S'.$color[$i])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00EEEEEE');
        }
        $sheet->mergeCells('A1:S1'); //合併第一行A-S
        $sheet->getRowDimension(1)->setRowHeight(24); //第一行高度30
        $sheet->getStyle('A1')->getFont()->setSize(18)->setBold(true); //第一行字型大小
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('00EEEEEE');
        $sheet->getStyle('C')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('E')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('N')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('M')->getAlignment()->setWrapText(true); //自動換行

        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('S')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('P')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A:S')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
    }

    public function title(): string
    {
        return '採購單變更明細';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 16,
            'C' => 12,
            'D' => 12,
            'E' => 25,
            'F' => 6,
            'G' => 6,
            'H' => 12,
            'I' => 15,
            'J' => 20,
            'K' => 20,
            'L' => 60,
            'M' => 30,
            'N' => 8,
            'O' => 12,
            'P' => 10,
            'Q' =>  6,
            'R' => 10,
            'S' => 10,
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
                '採購變更明細',
            ],
            [
                '單據日期',
                'iCarry採購單號',
                '變更時間',
                '廠商代號',
                '商家簡稱',
                '幣別',
                '匯率',
                '課稅別',
                '付款條件代號',
                '付款條件名稱',
                '品　　　號',
                '品　　　名',
                '規　　　格',
                '交貨庫',
                '預交日',
                '採購數量',
                '單位',
                '單　　價',
                '金　　額',
            ],
        ];
    }
}
