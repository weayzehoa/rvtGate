<?php

namespace App\Exports\Sheets;

use App\Models\UserAddress as UserAddressDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\Country as CountryDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingSFOldSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
{
    use OrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
        $this->setBGColor = $this->setBGColor2 = [];
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
        if(count($orders) > 0){
            $c = 1;
            $b = 2;
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
            foreach ($orders as $order) {
                $i = 1;
                $orderTotalAmount = $totalAmount = 0;
                $orderNumber = $order->order_number;
                $colI = $colK = $colL = $colAH = $colAJ = $colAL = null;
                if($order->ship_to == '澳門'){
                    $colI = 1;
                    $colK ='澳门特别行政区';
                    $colL ='澳门';
                }elseif($order->ship_to == '香港'){
                    $colI = 1;
                    $colK = '香港特别行政区';
                    $colL = '香港';
                }elseif($order->ship_to == '台灣'){
                    $colI = 104;
                }elseif($order->ship_to == '中國'){
                    $colI = 59;
                    $colK=$order->receiver_province;
                    $colL=$order->receiver_city;
                 }elseif($order->ship_to == '新加坡'){
                    $colI = 24;
                    $colK = '新加坡';
                    $colL = '新加坡';
                 }elseif($order->ship_to == '南韓' || $order->ship_to == '南韩'){
                    $colI = '24';
                    $colK = 'Korea';
                    $colL = $order->receiver_city;
                    !empty($order->receiver_birthday) ? $colAL = $order->receiver_birthday : (strstr($order->user_memo,'收件人生日：') ? $colAL = substr(mb_substr(mb_strrchr($order->user_memo,'收件人生日：'),6),0,8) : $colAL = null); //收件人生日
                 }elseif($order->ship_to == '日本'){
                    $colI = 'EXD_CD';
                    $colK = 'Japan';
                    $colL = 'Japan';
                }elseif($order->ship_to == '馬來西亞' || $order->ship_to == '马来西亚'){
                    $colI = 24;
                    $colK = $order->receiver_city;
                    $colL = $order->receiver_area;
                }
                if($order->ship_to == '新加坡'){
                    $colAJ = $order->ship_to;
                    $colAH = $order->receiver_zip_code;
                }elseif($order->ship_to == '日本'){
                    $colAJ = 'Japan';
                    $colAH = $order->receiver_zip_code;
                }elseif($order->ship_to == '馬來西亞' || $order->ship_to == '马来西亚'){
                    $colAJ = '马来西亚';
                    $colAH = $order->receiver_zip_code;
                }elseif($order->ship_to == '南韓' || $order->ship_to == '南韩'){
                    $colAH = null;
                    $colAJ = 'Korea';
                }else{
                    $colAH = null;
                    $colAJ= '中国';
                }
                if(strstr($order->receiver_address,'中國')){
                    $memo = '大陸身分證：'.$order->receiver_id_card.'/'.$order->user_memo;
                }else if(strstr($order->receiver_address,'香港') || strstr($order->receiver_address, 'HONG KONG')){
                    $memo = '附加費轉寄付/'.$order->user_memo;
                }else if($order->receiver_name == '蝦皮台灣特選店'){
                    $memoLengh =  strpos($order->user_memo,'。');
                    $memo = substr($order->user_memo,0,$memoLengh);
                }else{
                    $memo = $order->user_memo;
                }
                if(!empty($order->merge_order)){
                    $shippingNumber = null;
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
                        $orderTotalAmount = $totalAmount += $mOrder->amount + $mOrder->shipping_fee + $mOrder->parcel_tax - $mOrder->spend_point - $mOrder->discount;
                        $shippingNumber .= $order->shipping_number.',';
                    }
                    $shippingNumber = join(',',array_unique(explode(',',rtrim($shippingNumber,','))));
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
                    $orderTotalAmount = $totalAmount = $order->amount + $order->shipping_fee + $order->parcel_tax - $order->spend_point - $order->discount;
                    $items = $order->items;
                    $shippingNumber = $order->shipping_number;
                }
                if(count($items) > 0){
                    $currency = '';
                    $chkDirectShipment = 0;
                    foreach ($items as $item) {
                        if($item->direct_shipment == 0 && $item->is_del == 0){ //排除直寄
                            if(strstr($item->sku,'BOM')){
                                foreach($item->package as $package){
                                    //臺灣或機場不顯示金額 其他顯示
                                    if($order->shipping_method == 1 || $order->shipping_method == 2 || $order->shipping_method == 3 || $order->shipping_method == 5 || $order->ship_to == '台灣'){
                                        $currency = 'TWD';
                                        $price = number_format($package->price);
                                    }else{
                                        if(strstr($order->receiver_address, '中國') == false){
                                            if($order->ship_to == '日本'){
                                                $currency = 'JPY';
                                                $orderTotalAmount = round($totalAmount / 0.21, 0);
                                                $price = round($package->price / 0.21,0);
                                            }else{
                                                $currency = 'USD';
                                                $orderTotalAmount = round($totalAmount * 0.03, 4 );
                                                $price = round($package->price * 0.03,4);
                                            }
                                        }else{
                                            $currency = 'RMB';
                                            $orderTotalAmount = round($totalAmount * 0.2, 4 );
                                            $price = round($package->price * 0.2,4);
                                        }
                                    }
                                    if($currency == 'RMB'){
                                        $this->setBGColor[] =  $c;
                                    }
                                    $data[] = [
                                        '886DCA',
                                        'W8860691130',
                                        '8860691130',
                                        $i,
                                        '',
                                        '',
                                        $orderNumber, //G
                                        '',
                                        $colI,
                                        in_array($item->shipping_memo,['7-11 大智通','全家 日翊','萊爾富']) ? $item->shipping_memo : $order->receiver_name,
                                        $colK,
                                        $colL,
                                        '',
                                        $order->receiver_address,
                                        '',
                                        '',
                                        '',
                                        '',
                                        empty($package->gtin13) ? $package->gtin13 = $package->sku : $package->gtin13,
                                        $package->product_name,
                                        $package->quantity,
                                        $price,
                                        $orderTotalAmount,
                                        $order->receiver_name,
                                        !empty($order->receiver_tel) ? $order->receiver_tel : '00000',
                                        !empty($order->receiver_tel) ? $order->receiver_tel : '00000',
                                        $memo,
                                        '',
                                        '',
                                        $shippingNumber,
                                        '',
                                        '',
                                        $currency,
                                        $colAH,
                                        '',
                                        $colAJ,
                                        '',
                                        $colAL,
                                        $order->user_name,
                                        $order->user_name,
                                    ];
                                    if($c%2 == 0){
                                        $this->setBGColor2[] = $b;
                                    }
                                    $i++;
                                    $b++;
                                }
                            }else{
                                //臺灣或機場不顯示金額 其他顯示
                                if($order->shipping_method == 1 || $order->shipping_method == 2 || $order->shipping_method == 3 || $order->shipping_method == 5 || $order->ship_to == '台灣'){
                                    $currency = 'TWD';
                                    $price = number_format($item->price);
                                }else{
                                    if(strstr($order->receiver_address, '中國') == false){
                                        if($order->ship_to == '日本'){
                                            $currency = 'JPY';
                                            $orderTotalAmount = round($totalAmount / 0.21, 0);
                                            $price = round($item->price / 0.21,0);
                                        }else{
                                            $currency = 'USD';
                                            $orderTotalAmount = round($totalAmount * 0.03, 4 );
                                            $price = round($item->price * 0.03,4);
                                        }
                                    }else{
                                        $currency = 'RMB';
                                        $orderTotalAmount = round($totalAmount * 0.2, 4 );
                                        $price = round($item->price * 0.2,4);
                                    }
                                }
                                if($currency == 'RMB'){
                                    $this->setBGColor[] =  $c;
                                }
                                $data[] = [
                                    '886DCA',
                                    'W8860691130',
                                    '8860691130',
                                    $i,
                                    '',
                                    '',
                                    $orderNumber, //G
                                    '',
                                    $colI,
                                    in_array($item->shipping_memo,['7-11 大智通','全家 日翊','萊爾富']) ? $item->shipping_memo : $order->receiver_name,
                                    $colK,
                                    $colL,
                                    '',
                                    $order->receiver_address,
                                    '',
                                    '',
                                    '',
                                    '',
                                    empty($item->gtin13) ? $item->gtin13 = $item->sku : $item->gtin13, //S
                                    $item->product_name,
                                    $item->quantity,
                                    $price,
                                    $orderTotalAmount,
                                    $order->receiver_name,
                                    !empty($order->receiver_tel) ? $order->receiver_tel : '00000',
                                    !empty($order->receiver_tel) ? $order->receiver_tel : '00000',
                                    $memo,
                                    '',
                                    '',
                                    $shippingNumber,
                                    '',
                                    '',
                                    $currency,
                                    $colAH,
                                    '',
                                    $colAJ,
                                    '',
                                    $colAL,
                                    $order->user_name,
                                    $order->user_name,
                                ];
                                if($c%2 == 0){
                                    $this->setBGColor2[] = $b;
                                }
                                $i++;
                                $b++;
                            }
                        }else{
                            $chkDirectShipment++;
                        }
                    }
                    if($chkDirectShipment != count($items)){
                        $c++;
                    }
                }

            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        if(!empty($this->setBGColor)){
            for ($i=0; $i < count($this->setBGColor) ; $i++) {
                $sheet->getStyle('A'.($this->setBGColor[$i]).':AL'.($this->setBGColor[$i]))->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('D3D3D3');
            }
        }
        if(!empty($this->setBGColor2)){
            for ($i=0; $i < count($this->setBGColor2) ; $i++) {
                $sheet->getStyle('A'.($this->setBGColor2[$i]).':AL'.($this->setBGColor2[$i]))->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('CCCCCC');
            }
        }

        $sheet->getStyle('B1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        $sheet->getStyle('G1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        $sheet->getStyle('J1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        $sheet->getStyle('N1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        $sheet->getStyle('AJ1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('S')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('S')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('Y')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('Y')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('Z')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('Z')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AH')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('AH')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AL')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('AL')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '順豐出貨單(OLD)';
    }
    public function columnWidths(): array
    {
        $highestColumn = 'AZ';
        $highestColumn++;
        for ($column = 'A'; $column !== $highestColumn; $column++) {
            $width[$column] = 20;
        }
        return $width;
    }
    public function headings(): array
    {
        return [
            [
                '仓库ID',
                '货主ID',
                '月结账号',
                '行号',
                '订单类型ID',
                '客户支付SF运费方式ID',
                '订单号码',
                '承运商ID',
                '承运商服务ID',
                '收件公司',
                '省',
                '市',
                '区/县',
                '地址',
                '是否货到付款',
                '代收货款金额',
                '是否保价',
                '声明价值',
                '条码',
                '预留字段1/商品名称',
                '商品出库数量',
                '价格',
                '订单总金额',
                '收件人',
                '固定电话',
                '手机',
                '订单备注',
                '是否自取件',
                '库存状态',
                '运单号',
                '批次号',
                '是否组合商品',
                '币种简码',
                '收件方邮编',
                '订单优先级',
                '收件方國家',
                '收件方证件类型',
                '收件方证件号码',
                '寄件方公司',
                '寄件方姓名',
            ],
        ];
    }
}

