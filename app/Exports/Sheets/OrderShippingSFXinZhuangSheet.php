<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\Country as CountryDB;
use App\Models\UserAddress as UserAddressDB;
use App\Models\iCarryProductLangEn as ProductLangDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingSFXinZhuangSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,ShouldAutoSize,WithHeadings
{
    use OrderFunctionTrait;
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
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $orderItemPackageTable = env('DB_ICARRY').'.'.(new OrderItemPackageDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $data = [];
        $orders = $this->getOrderData($this->param,'orderShipping');
        //先找出有被合併的訂單號碼
        $mergeOrderNumbers = null;
        foreach($orders as $order){
            if(!empty($order->merge_order)){
                $mergeOrderNumbers .= $order->merge_order.',';
            }
        }
        !empty($mergeOrderNumbers) ? $mergeOrderNumbers = explode(',',rtrim($mergeOrderNumbers,',')) : '';
        if(!empty($mergeOrderNumbers)){
            for( $i = 0; $i < count($mergeOrderNumbers); $i++){
                foreach($orders as $key => $order){
                    if($order->order_number == $mergeOrderNumbers[$i]){
                        $orders->forget($key);
                        break;
                    }
                }
            }
        }
        if(!empty($orders)){
            foreach ($orders as $order) {
                if($order->ship_to == '台灣'){
                    if(!empty($order->merge_order)){
                        $orderIds = [];
                        $orderNumbers = explode(',',$order->order_number.','.$order->merge_order);
                        $mergeOrders = OrderDB::whereIn('order_number',$orderNumbers)->select([
                            'id',
                            'amount',
                            'shipping_fee',
                            'parcel_tax',
                            'spend_point',
                            'discount',
                            'shipping_memo',
                            // DB::raw("SUM(amount + shipping_fee + parcel_tax - spend_point - discount) as total"),
                            ])->get();
                        //重新找出所有$order_id,$items,及相關資料
                        foreach($mergeOrders as $mOrder){
                            $orderIds[] = $mOrder->id;
                        }
                        $items = OrderItemDB::with('package')
                            ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                            ->whereIn($orderItemTable.'.order_id',$orderIds)
                            ->where($orderItemTable.'.is_del',0)
                            ->select([
                                $orderItemTable.'.*',
                                $productTable.'.package_data',
                                $productModelTable.'.sku',
                                $productModelTable.'.gtin13',
                                $productModelTable.'.digiwin_no',
                                $productModelTable.'.origin_digiwin_no',
                                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                DB::raw("SUM($orderItemTable.quantity) as quantity"),
                        ])->groupBy($orderItemTable.'.product_model_id')->get();
                        foreach($items as $item){
                            $item = $this->oneItemTransfer($item);
                            //組合品數量重算
                            if(strstr($item->sku,'BOM')){
                                $useQty = 0;
                                $packageData = json_decode(str_replace('	','',$item->package_data));
                                if(is_array($packageData) && count($packageData) > 0){
                                    foreach($packageData as $pp){
                                        if(isset($pp->is_del)){
                                            if($pp->is_del == 0){
                                                if($item->sku == $pp->bom){
                                                    foreach($pp->lists as $list) {
                                                        $useQty = $list->quantity;
                                                        foreach($item->package as $package){
                                                            $package = $this->oneItemTransfer($package);
                                                            $package->quantity = $useQty * $item->quantity;
                                                        }
                                                    }
                                                }
                                            }
                                        }else{
                                            if($item->sku == $pp->bom){
                                                foreach($pp->lists as $list) {
                                                    $useQty = $list->quantity;
                                                    foreach($item->package as $package){
                                                        $package = $this->oneItemTransfer($package);
                                                        $package->quantity = $useQty * $item->quantity;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        $items = $order->items;
                    }
                    foreach ($items as $item) {
                        if($item->shipping_memo == '順豐-新莊'){
                            $data[] = [
                                "$order->order_number",
                                null,
                                null,
                                null,
                                '直流電通股份有限公司',
                                '0906486688',
                                null,
                                '台湾新北市新莊區中正路665號9樓(從後面福營路進去碼頭下貨)',
                                null,
                                '新北市',
                                '台湾省',
                                '中國臺灣',
                                '24257',
                                null,
                                '個人件',
                                null,
                                $order->receiver_name,
                                $order->receiver_tel,
                                null,
                                $order->receiver_address,
                                null,
                                null,
                                $order->receiver_city,
                                $order->receiver_province,
                                '台湾省',
                                '中國臺灣',
                                $order->receiver_zip_code,
                                null,
                                '個人件',
                                null,
                                $item->product_name,
                                $item->quantity,
                                $item->unit_name,
                                null,
                                $item->price,
                                1,
                                null,
                                null,
                                null,
                                null,
                                'NTD',
                                '順豐特快',
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                '自行聯系快遞員或自寄',
                                null,
                                null,
                                '第三方付',
                                '8860522632',
                                null,
                            ];
                        }
                    }
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('M')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('R')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AA')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('AA')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AF')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('AF')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AI')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('AI')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AJ')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('AJ')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('BC')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('BC')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('T')->setAutoSize(true);
        $sheet->getColumnDimension('U')->setAutoSize(true);
        $sheet->getColumnDimension('V')->setAutoSize(true);
        $sheet->getColumnDimension('W')->setAutoSize(true);
        $sheet->getColumnDimension('X')->setAutoSize(true);
        $sheet->getColumnDimension('Y')->setAutoSize(true);
        $sheet->getColumnDimension('Z')->setAutoSize(true);
        $sheet->getColumnDimension('AD')->setAutoSize(true);
        $sheet->getColumnDimension('AE')->setAutoSize(true);
        $sheet->getColumnDimension('AF')->setAutoSize(true);
        $sheet->getColumnDimension('AG')->setAutoSize(true);
        $sheet->getColumnDimension('AH')->setAutoSize(true);
        $sheet->getColumnDimension('AI')->setAutoSize(true);
        $sheet->getColumnDimension('AJ')->setAutoSize(true);
        $sheet->getColumnDimension('AK')->setAutoSize(true);
        $sheet->getColumnDimension('AL')->setAutoSize(true);
        $sheet->getColumnDimension('AM')->setAutoSize(true);
        $sheet->getColumnDimension('AN')->setAutoSize(true);
        $sheet->getColumnDimension('AO')->setAutoSize(true);
        $sheet->getColumnDimension('AP')->setAutoSize(true);
        $sheet->getColumnDimension('AQ')->setAutoSize(true);
        $sheet->getColumnDimension('AR')->setAutoSize(true);
        $sheet->getColumnDimension('AS')->setAutoSize(true);
        $sheet->getColumnDimension('AT')->setAutoSize(true);
        $sheet->getColumnDimension('AU')->setAutoSize(true);
        $sheet->getColumnDimension('AV')->setAutoSize(true);
        $sheet->getColumnDimension('AW')->setAutoSize(true);
        $sheet->getColumnDimension('AX')->setAutoSize(true);
        $sheet->getColumnDimension('AY')->setAutoSize(true);
        $sheet->getColumnDimension('AZ')->setAutoSize(true);
        $sheet->getColumnDimension('BA')->setAutoSize(true);
        $sheet->getColumnDimension('BB')->setAutoSize(true);
        $sheet->getColumnDimension('BC')->setAutoSize(true);
        $sheet->getColumnDimension('BD')->setAutoSize(true);
    }

    public function title(): string
    {
        return 'DHL';
    }

    public function headings(): array
    {
        return [
            [
                '*客戶訂單號',
                '客戶訂單號2',
                '代理運單號',
                '運單號',
                '*寄件方姓名',
                '寄件方手機號',
                '寄件方固定電話',
                '*寄件方詳細地址',
                '寄件方縣/區',
                '*寄件方城市',
                '*寄件方州/省',
                '*寄件方國家/地區',
                '*寄件方郵編',
                '寄件方郵箱',
                '寄件類型',
                '寄件方公司',
                '*收件方姓名',
                '收件方手機號',
                '收件方固定電話',
                '*收件方詳細地址',
                '道路名',
                '建築編號',
                '收件方縣/區',
                '*收件方城市',
                '*收件方州/省',
                '*收件方國家/地區',
                '*收件方郵編',
                '收件方郵箱',
                '收件類型',
                '收件方公司',
                '*商品名稱',
                '*商品數量',
                '*單位',
                '單位重量',
                '*商品單價',
                '*包裹總件數',
                '長度單位',
                '長',
                '寬',
                '高',
                '*商品貨幣',
                '*快件類型',
                '附加服務1',
                '附加服務1代收貨款卡號',
                '附加服務1內容',
                '附加服務2',
                '附加服務2內容',
                '附加服務3',
                '簽單返還內容',
                '簽單返還備註',
                '*寄件方式',
                '預約時間',
                '運單備註',
                '*付款方式',
                '月結卡號',
                'PO Number',
            ],
            [
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
                '此行必須存在',
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 15,
            'D' => 16,
            'E' => 60,
            'F' => 10,
            'G' => 15,
            'H' => 20,
            'I' => 15,
            'J' => 50,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 50,
            'O' => 10,
            'P' => 10,
            'Q' => 10,
            'R' => 10,
            'S' => 10,
            'T' => 10,
            'U' => 10,
            'V' => 10,
            'W' => 10,
            'X' => 10,
            'Y' => 10,
            'Z' => 10,
            'AA' => 15,
            'AB' => 10,
            'AC' => 20,
            'AD' => 25,
            'AE' => 25,
            'AF' => 20,
            'AG' => 8,
            'AH' => 8,
            'AI' => 10,
            'AJ' => 18,
            'AK' => 25,
            'AL' => 10,
            'AM' => 10,
            'AN' => 10,
            'AO' => 10,
            'AP' => 10,
            'AQ' => 10,
            'AR' => 10,
            'AS' => 10,
            'AT' => 10,
            'AU' => 10,
            'AV' => 10,
            'AW' => 10,
            'AX' => 10,
            'AY' => 10,
            'AZ' => 10,
            'BA' => 10,
            'BB' => 10,
            'BC' => 10,
            'BD' => 10,
        ];
    }
}
