<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

class OrderPurchaseBlackCatSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings
{
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
        $param = $this->param;
        $data = [];
        if(isset($param['id'])){
            //訂單狀態大於0的才能建立採購單(有付錢的)
            $orders = OrderDB::whereIn('id',$param['id'])->where('status','>',0)->get();
            foreach ($orders as $order) {
                $orderIds[] = $order->id;
            }
            if(!empty($orderIds)){
                //找到所有訂單的items
                $items = OrderItemDB::with('order','vendor')->join('orders','orders.id','order_item.order_id')
                        ->join('vendors','vendors.id','order_item.vendor_id')->whereIn('orders.id',$orderIds)
                        ->select([
                            'order_item.product_model_id',
                            'order_item.vendor_id',
                            'order_item.order_id',
                            'order_item.vendor_name',
                            'order_item.product_name',
                            'order_item.unit_name',
                            DB::raw('SUM(order_item.quantity) as quantity'),
                            DB::raw('GROUP_CONCAT( orders.order_number SEPARATOR ",") as orderNumbers'),
                        ])->orderBy('order_item.quantity','desc')->groupBy('order_item.product_model_id')->get();

                //將相同vendor群組起來 (這邊用到的是collection的function並非ORM的groupBy)
                $vendors = $items->groupBy('vendor_id');

                foreach($vendors as $products){ //共有幾個產品是同一個Vendor
                    $productContent = '';
                    $orderNumbers = '';
                    foreach($products as $product){ //將商品資料組合成一個字串放在物品名稱欄位
                        $orderNumbers .= $product->orderNumbers.',';
                        $productContent .= $product->product_name.' * '.$product->quantity.' '.$product->unit_name.' 、';
                        $vendorName = $product->vendor->name;
                        $vendorContactName = $product->vendor->contact;
                        $vendorContactTEL = $product->vendor->tel;
                        $vendorContactEmail = $product->vendor->email;
                        $vendorContactAddress = $product->vendor->address;
                        $vendorFactoryAddress = $product->vendor->factory_address;
                    }
                    $orderNumbers = join("\n",array_unique(explode(',',rtrim($orderNumbers,','))));
                    $productContent = rtrim($productContent,'、');
                    $data[] = [
                        date('Y-m-d'),
                        '',
                        '1',
                        'N',
                        $vendorContactName,
                        $vendorContactTEL,
                        $vendorContactTEL,
                        $vendorFactoryAddress,
                        date('Y-m-d')." 取貨-".$vendorName,
                        $vendorContactEmail,
                        '闕鉅錦',//k
                        '02-8791-0686#13',
                        '',
                        '台北市內湖區潭美街331號',
                        '',
                        $productContent,
                        $orderNumbers,
                        '',
                        '',
                        '',
                        '',
                        '',
                    ];
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#'); //C欄格式數字改字串
        $sheet->getStyle('Q')->getNumberFormat()->setFormatCode('#'); //Q欄格式數字改字串
        $sheet->getStyle('Q')->getAlignment()->setWrapText(true);
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('Q')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '黑貓叫貨';
    }

    public function headings(): array
    {
        return [
            '日期',
            '物流單編號',
            '溫層(1.常溫/2.冷藏/3.冷凍)',
            '是否到付(Y/N)',
            '寄件人姓名',
            '寄件人電話',
            '寄件人手機',
            '寄件人地址',
            '備註',
            '備註Mail',
            '收件人姓名',
            '收件人電話',
            '收件人手機',
            '收件人地址',
            '指定送達時間(1.(9點到12點)/2.(12點到17點)/3.(17點到20點)/4.(不指定))',
            '物品名稱',
            '對應訂單號',
            '備註',
            '請款否',
            '付款日',
            '請款人',
            '請款日期',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 25,
            'G' => 25,
            'H' => 40,
            'I' => 40,
            'J' => 30,
            'K' => 15,
            'L' => 20,
            'M' => 15,
            'N' => 30,
            'O' => 20,
            'P' => 70,
            'Q' => 20,
            'R' => 10,
            'S' => 10,
            'T' => 10,
            'U' => 10,
            'V' => 10,
        ];
    }
}
