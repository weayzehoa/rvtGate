<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingBlackcatSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings,WithColumnFormatting
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
        if (!empty($orders)) {
            foreach ($orders as $order) {
                //判斷商品物流是否為黑貓宅急便與黑貓-新莊
                $chkBlackcat = 0;
                foreach($order->items as $item){
                    if(strstr($item->shipping_memo,'黑貓')){
                        $chkBlackcat++;
                    }
                }
                if($chkBlackcat > 0){
                    $order->shipping_method == 2 ? $receiverAddress = $order->receiver_address.'/'.$order->receiver_keyword : $receiverAddress = $order->receiver_address;
                    // //寄件者備註新增提貨時間及訂單號碼
                    // if($order->book_shipping_date && $order->shipping_method != 4){
                    //     if($order->user_memo){
                    //         $order->user_memo = '提貨時間：'.$order->book_shipping_date.'，'.$order->user_memo;
                    //     }else{
                    //         $order->user_memo = '提貨時間：'.$order->book_shipping_date;
                    //     }
                    // }
                    // if(!empty($order->partner_order_number)){
                    //     $order->user_memo = null;
                    // }
                    // if($order->shipping_method == 1){
                    //     $order->user_memo .= '☆提貨時間：'.substr($order->receiver_key_time,0,19);
                    // }elseif($order->shipping_method == 2){
                    //     $order->user_memo .= '☆提貨時間：'.substr($order->receiver_key_time,0,10);
                    // }
                    // $order->user_memo = str_replace('<br />','',$order->user_memo);

                    // //新增提貨時間
                    // if(!empty($order->book_shipping_date) && $order->shipping_method != 4){
                    //     if($order->user_memo == '' || $order->user_memo == null){
                    //         $order->user_memo = "提貨時間：{$order->book_shipping_date}";
                    //     }else{
                    //         $order->user_memo = "提貨時間：{$order->book_shipping_date}，".$order->user_memo;
                    //     }
                    // }
                    // if($order->user_memo == '' || $order->user_memo == null){
                    //     $order->user_memo = $order->order_number;
                    // }else{
                    //     $order->user_memo = $order->order_number.'/'.$order->user_memo;
                    // }
                    $data[] = [
                        $order->order_number, //訂單編號
                        '1', //溫層
                        '1', //距離
                        '2', //規格
                        '0', //代收貨款
                        $order->receiver_name, //收件人-姓名
                        null, //收件人-電話
                        $this->phoneChange($order->receiver_tel), //收件人-手機
                        $receiverAddress, //收件人-地址
                        'ICARRY-直流電通股份有限公司', //寄件人-姓名
                        '0327531616#9', //寄件人-電話
                        '桃園市蘆竹區海湖北路309巷130號', //寄件人-地址
                        str_replace('-','',$order->book_shipping_date), //出貨日期, 預定出貨日
                        str_replace('-','',date('Y-m-d', strtotime('+1 day', strtotime($order->book_shipping_date)))), //希望配達日, 預定出貨日加1天
                        '4', //希望配合時段
                        '015', //品類代碼
                        '0015-其他', //品名
                        'Y', //易碎物品
                        'N', //精密儀器
                        $order->user_memo, //備註
                        'N', //報值(Y|N)
                        '0', //報值金額
                        'N', //到付單(Y|N)
                    ];
                }
            }
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('T')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('I')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('T')->getAlignment()->setWrapText(true); //自動換行
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '黑貓物流(台灣、機場)';
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            '溫層',
            '距離',
            '規格',
            '代收貨款',
            '收件人-姓名',
            '收件人-電話',
            '收件人-手機',
            '收件人-地址',
            '寄件人-姓名',
            '寄件人-電話',
            '寄件人-地址',
            '出貨日期',
            '希望配達日',
            '希望配合時段',
            '品類代碼',
            '品名',
            '易碎物品',
            '精密儀器',
            '備註',
            '報值(Y|N)',
            '報值金額',
            '到付單(Y|N)',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, //字串
            'B' => NumberFormat::FORMAT_TEXT, //字串
            'C' => NumberFormat::FORMAT_TEXT, //字串
            'D' => NumberFormat::FORMAT_TEXT, //字串
            'M' => NumberFormat::FORMAT_TEXT, //字串
            'N' => NumberFormat::FORMAT_TEXT, //字串
            'O' => NumberFormat::FORMAT_TEXT, //字串
            'T' => NumberFormat::FORMAT_TEXT, //字串
            'V' => NumberFormat::FORMAT_TEXT, //字串
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 8,
            'C' => 8,
            'D' => 8,
            'E' => 12,
            'F' => 15,
            'G' => 15,
            'H' => 20,
            'I' => 60,
            'J' => 30,
            'K' => 15,
            'L' => 35,
            'M' => 12,
            'N' => 12,
            'O' => 15,
            'P' => 12,
            'Q' => 12,
            'R' => 12,
            'S' => 12,
            'T' => 30,
            'U' => 12,
            'V' => 12,
            'W' => 15,
        ];
    }
}
