<?php

namespace App\Exports\Sheets;

use App\Models\iCarryUser as UserDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
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

class OrderExcelShopcomSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize
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
            $orders = OrderItemDB::join('orders','orders.id','order_item.order_id')
                ->join('shopcom_orders','shopcom_orders.order_id','order_item.order_id')
                ->join('product_model','product_model.id','order_item.product_model_id')
                ->join('product','product.id','product_model.product_id')
                ->join('vendor','vendor.id','product.vendor_id')
                ->whereIn('order_item.order_id',$orderIds)
                ->whereIn('shopcom_orders.order_id',$orderIds)
                ->select([
                    'orders.id',
                    DB::raw("DATE_FORMAT(orders.create_time,'%Y/%m/%d') as createTime"),
                    'orders.order_number',
                    'user_name' => UserDB::whereColumn('users.id','orders.user_id')->select('name')->limit(1),
                    'orders.receiver_name',
                    'shopcom_orders.RID',
                    'shopcom_orders.Click_ID',
                    'vendor.name as vendor_name',
                    'order_item.product_name',
                    'product_model.name',
                    'product_model.sku',
                    'order_item.quantity',
                    'order_item.price',
                ])->get();
            $i = 0;
            $totalPrice = 0;
            foreach ($orders as $order) {
                $order->user_name = empty($order->user_name) ? $order->receiver_name : $order->user_name;
                if($order->product_model_name == '單一規格'){
                    $order->product_name = $order->vendor_name.'-'.$order->product_name;
                }else{
                    $order->product_name = $order->vendor_name.'-'.$order->product_name.'-'.$order->product_model_name;
                }
                $data[] = [
                    $order->createTime,
                    $order->order_number,
                    $order->user_name,
                    $order->RID,
                    $order->Click_ID,
                    $order->sku,
                    $order->product_name,
                    $order->quantity,
                    $order->price,
                    $order->quantity * $order->price,
                ];
                $i++;
                $totalPrice += $order->quantity * $order->price;
            }
            $con = round($totalPrice * 0.06, 0); //傭金
            $tax = round($con * 0.05, 0);
            $data[$i+1] = ['','','','','','','','','',$totalPrice];
            $data[$i+2] = ['','','','','','','','右欄填入佣金%','6%',$con];
            $data[$i+3] = ['','','','','','','','營業稅%','5%',$tax];
            $data[$i+4] = ['','','','','','','','應付佣金總數','',$con + $tax];
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '美安匯出';
    }

    public function headings(): array
    {
        return [
            [
                '訂購日期',
                '訂單編號',
                '購買人',
                'Market Taiwan RID number',
                'Click_ID',
                '商品編號',
                '購買商品',
                '數量',
                '網路銷售價',
                '總計',
            ],
            [
                'Order Date',
                'Order Serial Number',
                'Buyer Name',
                '',
                '',
                'Product Serial Number',
                'Product description',
                'Product Quantity',
                'Unit price',
                'Sale Amount',
            ],
        ];
    }
}
