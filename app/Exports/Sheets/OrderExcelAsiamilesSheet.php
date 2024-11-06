<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderAsiamiles as OrderAsiamilesDB;
use App\Models\iCarryOrderPromotion as OrderPromotionDB;
use App\Models\CompanySetting as CompanySettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderExcelAsiamilesSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize
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
        $data = [];
        $orderIds = $this->getOrderData($this->param);
        if (!empty($orderIds)) {
            $orders = OrderAsiamilesDB::join('orders','orders.id','order_asiamiles.order_id')
                ->whereIn('order_id',$orderIds)
                ->select([
                    'orders.id',
                    'order_asiamiles.asiamiles_account',
                    'order_asiamiles.asiamiles_name',
                    'order_asiamiles.asiamiles_last_name',
                    'orders.order_number',
                    DB::raw("DATE_FORMAT(pay_time,'%Y%m%d') as pay_time"),
                    DB::raw("DATE_FORMAT(create_time,'%Y%m%d') as createTime"),
                    'orders.pay_method',
                    'orders.promotion_code',
                    DB::raw("(orders.amount + orders.shipping_fee + orders.parcel_tax - orders.discount - orders.spend_point) as pay_amount"),
                    // 'vendors' => OrderItemDB::whereColumn('orders.id','order_item.order_id')
                    //             ->join('product_model','product_model.id','order_item.product_model_id')
                    //             ->join('product','product.id','product_model.product_id')
                    //             ->join('vendor','vendor.id','product.vendor_id')
                    //             ->select([
                    //                 DB::raw("GROUP_CONCAT(DISTINCT vendor.name) as name")
                    //             ])->groupBy('order_id')->limit(1),
                    // 'promotion22' => OrderPromotionDB::whereColumn('order_with_promotion.order_id','orders.id')
                    //                 ->where('promotion_ids','like','%22%')->count(),
                ])->get();
                // dd($orders);
            $i = 0;
            foreach ($orders as $order) {
                $order->promotion22 = OrderPromotionDB::where('order_id',$order->id)->where('promotion_ids','like','%22%')->count();
                $vendors = OrderItemDB::where('order_id',$order->id)
                            ->join('product_model','product_model.id','order_item.product_model_id')
                            ->join('product','product.id','product_model.product_id')
                            ->join('vendor','vendor.id','product.vendor_id')
                            ->select([
                                'vendor.name',
                            ])->groupBy('order_id')->get()->pluck('name')->all();
                $order->vendors = join(',',$vendors);
                if($order->pay_time >= 20171216){
                    $miles=30;
                    $establishmentCode="ICARRY";
                }elseif($order->pay_method == "元大銀聯卡"){
                    $miles=10;
                    $establishmentCode="ICYTRIPLE";
                }else{
                    $miles=15;
                    $establishmentCode="ICYDOUBLE";
                }
                //輸入優惠代碼 CU 的訂單，F欄里數計算為 每 10 元 1 里，H欄 EstablishmentCode 為 ICYCUBAM3X
                if($order->promotion_code == "CU" && $order->createTime <= 20180630){
                    $miles=10;
                    $establishmentCode="ICYCUBAM3X";
                }
                //春節預購活動的訂單，F欄里數計算為 每 10 元 1 里，H欄 EstablishmentCode 為 ICYCNYX318
                if($order->promotion22 > 0){
                    $miles=10;
                    $establishmentCode="ICYCNYX318";
                }
                if(strstr($order->vendors,"閃購有到禮") && $order->createTime <= 20180630){
                    $miles=10;
                    $establishmentCode="ICY3XAPR18";
                }
                if($order->createTime >= 20180608 && $order->promotion_code == "UP"){
                    $miles=10;
                    $establishmentCode="ICYUNIAM3X";
                }
                $icarryF = round($order->pay_amount / 30);
                $activityDate = '20'.substr($order->order_number,0,6);
                $ActivityDate = substr($activityDate,0,4).'-'.substr($activityDate,4,2).'-'.substr($activityDate,6,2);
                if ($establishmentCode == "ICYTRIPLE" || $establishmentCode == "ICYCNYX318" || $establishmentCode == "ICYCUBAM3X" || $establishmentCode == "ICY3XAPR18" || $establishmentCode == "ICYUNIAM3X") {
                    //icarry row
                    $data[$i] = [
                        'AC',
                        $order->asiamiles_account,
                        $order->asiamiles_last_name,
                        $order->asiamiles_name,
                        $ActivityDate,
                        $icarryF,
                        'ICY',
                        'ICARRY',
                        substr($order->order_number, -10),
                        $order->order_number,
                        $order->pay_method,
                        $order->pay_amount,
                        $order->vendors,
                    ];
                    $i++;
                    //not icarry row
                    $data[$i] = [
                        'AC',
                        $order->asiamiles_account,
                        $order->asiamiles_last_name,
                        $order->asiamiles_name,
                        $ActivityDate,
                        $icarryF,
                        'ICY',
                        $establishmentCode,
                        substr($order->order_number, -10),
                        $order->order_number,
                        $order->pay_method,
                        $order->pay_amount,
                        $order->vendors,
                    ];
                }else{
                    $data[$i] = [
                        'AC',
                        $order->asiamiles_account,
                        $order->asiamiles_last_name,
                        $order->asiamiles_name,
                        $ActivityDate,
                        $icarryF,
                        'ICY',
                        $establishmentCode,
                        substr($order->order_number, -10),
                        $order->order_number,
                        $order->pay_method,
                        $order->pay_amount,
                        $order->vendors,
                    ];
                }
            $i++;
            }
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return 'Asiamiles匯出';
    }

    public function headings(): array
    {
        return [
            'TransactionCode',
            'MembershipNo',
            'FamilyName',
            'GivenName',
            'ActivityDate',
            'Miles',
            'PartnerCode',
            'EstablishmentCode',
            'RefNo',
            '訂單編號',
            '付款方式',
            '商品付款金額',
            '廠商',
        ];
    }
}
