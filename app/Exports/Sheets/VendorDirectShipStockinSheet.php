<?php

namespace App\Exports\Sheets;

use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

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

class VendorDirectShipStockinSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths,WithColumnFormatting
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
        $orderIds = $this->param['orderIds'];
        $productModelIds = $this->param['productModelIds'];
        $purchaseOrderIds = $this->param['purchaseOrderIds'];
        $key = env('APP_AESENCRYPT_KEY');
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        if(count($orderIds) > 0){
            $items = SyncedOrderItemDB::join($orderTable,$orderTable.'.id',$syncedOrderItemTable.'.order_id')
            ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$syncedOrderItemTable.'.purchase_no')
            ->whereIn($purchaseOrderTable.'.id',$purchaseOrderIds)
            ->whereIn($syncedOrderItemTable.'.order_id',$orderIds)
            ->whereIn($syncedOrderItemTable.'.product_model_id',$productModelIds)
            ->where($syncedOrderItemTable.'.is_del',0)
            ->where($syncedOrderItemTable.'.direct_shipment',1) //只選直寄
            ->select([
                $syncedOrderItemTable.'.purchase_no',
                $syncedOrderItemTable.'.vendor_arrival_date',
                $syncedOrderItemTable.'.quantity',
                $syncedOrderItemTable.'.unit_name',
                $syncedOrderItemTable.'.direct_shipment',
                $productModelTable.'.digiwin_no',
                DB::raw("CONCAT($productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.serving_size',
                $orderTable.'.receiver_name',
                $orderTable.'.receiver_address',
                $orderTable.'.receiver_nation_number',
                $orderTable.'.order_number',
                $orderTable.'.receiver_name',
                $orderTable.'.user_memo',
                DB::raw("IF($orderTable.receiver_phone_number IS NULL,'',AES_DECRYPT($orderTable.receiver_phone_number,'$key')) as receiver_phone_number"),
                DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
                DB::raw("DATE_FORMAT($orderTable.pay_time,'%Y/%m/%d') as order_date"),
                $orderTable.'.partner_order_number',
            ])->get();
        }else{
            $items = PurchaseOrderItemDB::join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemTable.'.purchase_no')
            ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->whereIn($purchaseOrderTable.'.id',$purchaseOrderIds)
            ->select([
                $purchaseOrderItemTable.'.purchase_no',
                $purchaseOrderItemTable.'.vendor_arrival_date',
                $purchaseOrderItemTable.'.quantity',
                $productTable.'.unit_name',
                $purchaseOrderItemTable.'.direct_shipment',
                $productModelTable.'.digiwin_no',
                DB::raw("CONCAT($productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.serving_size',
            ])->get();
        }
        if (count($items) > 0) {
            foreach ($items as $item) {
                if($item->direct_shipment == 1){
                    $data[] = [
                        $item->purchase_no,
                        $item->vendor_arrival_date,
                        $item->digiwin_no,
                        $item->product_name,
                        $item->serving_size,
                        $item->quantity,
                        $item->unit_name,
                        'W02',
                        '廠商倉',
                        !empty($item->user_memo) ? $item->user_memo : null,
                        !empty($item->receiver_name) ? $item->receiver_name : null,
                        !empty($item->receiver_address) ? $item->receiver_address : null,
                        !empty($item->receiver_nation.$item->receiver_phone) ? $item->receiver_nation.$item->receiver_phone : (!empty($item->receiver_tel) ? $item->receiver_tel : null),
                        !empty($item->order_number) ? $item->order_number : null,
                        !empty($item->order_date) ? $item->order_date : null,
                        !empty($item->partner_order_number) ? $item->partner_order_number : null,
                        null,
                        null,
                        null,
                    ];
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:S1'); //合併
        $sheet->mergeCells('A2:S2'); //合併
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('N')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('P')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('S')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('P')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A1:S1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:S2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:S4')->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A4:S4')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    }

    public function title(): string
    {
        return $this->param['title'];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 12,
            'C' => 18,
            'D' => 60,
            'E' => 25,
            'F' => 10,
            'G' => 8,
            'H' => 10,
            'I' => 10,
            'J' => 20,
            'K' => 20,
            'L' => 50,
            'M' => 15,
            'N' => 18,
            'O' => 12,
            'P' => 18,
            'Q' => 18,
            'R' => 18,
            'S' => 18,
        ];
    }
    public function headings(): array
    {
        return [
            [
                '直流電通股份有限公司', //A1
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
                '',
                '', //O1
                '',
                '',
                '',
                '',
            ],
            [
                '直寄訂單入庫單', //A1
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
                '',
                '', //O1
                '',
                '',
                '',
                '',
            ],
            [
                '', //A1
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
                '',
                '', //O1
                '',
                '',
                '',
                '格式範例 20220105',
            ],
            [
                '採購單號',
                '指定到貨日',
                '品    號',
                '品    名',
                '規    格',
                '訂單數量',
                '單位',
                '庫別代號',
                '庫別名稱',
                '備註',
                '收貨人',
                '送貨地址',
                '行動電話',
                '訂單單號',
                '訂單日期',
                '網路訂單編號',
                '物流商',
                '物流單號',
                '出貨日(請填八個數字)'
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, //字串
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD, //日期
            'M' => NumberFormat::FORMAT_TEXT, //字串
            'N' => NumberFormat::FORMAT_TEXT, //字串
            'O' => NumberFormat::FORMAT_DATE_YYYYMMDD, //日期
            'P' => NumberFormat::FORMAT_TEXT, //字串
            // 'K' => NumberFormat::FORMAT_NUMBER_00, //金额保留两位小数
        ];
    }
}

