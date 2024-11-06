<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingLinexSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize
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
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $data[] = [
                    1,
                    $order->order_number,
                    $order->order_number,
                    'icarry直流電通股份有限公司',
                    '886-906-486688',
                    '台北市中山區南京東路三段103號11樓',
                    $order->receiver_name,
                    $order->receiver_address,
                    $this->receiverCountry($order->receiver_address,$order->user_memo),
                    $this->receiverCountry($order->receiver_address,$order->user_memo),
                    $this->receiverCountry($order->receiver_address,$order->user_memo),
                    $this->receiverCountry($order->receiver_address,$order->user_memo),
                    $order->receiver_zip_code,
                    $order->receiver_tel,
                    '',//O
                    'PACKAGE',
                    '', //Q
                    $this->serverCode($order->receiver_address,$order->user_memo),
                    'DDU',
                    $order->amount,
                    '1',
                    '1',
                    'TWD',
                    $order->user_memo,
                    'COOKIE',
                    $order->amount,
                    '1',
                    '', //AB
                    '1',
                ];
            }
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $setColor = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW'];
        foreach($setColor as $key => $value){
            $sheet->getStyle($value)->getNumberFormat()->setFormatCode('#');
            $sheet->getStyle($value)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            for ($i=1;$i<=2;$i++) {
                $sheet->getStyle("{$value}{$i}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
            }
        }
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return 'Linex物流';
    }

    public function headings(): array
    {
        return [
                    [ //第一列
                        '序號',
                        '参考编号',
                        '单号',
                        '发件人名字',
                        '发件人电话',
                        '发件人地址',
                        '收件人姓名',
                        '收件人地址 1',
                        '收件人国家',
                        '收件人省份',
                        '收件人城市',
                        '收件人地区',
                        '郵編',
                        '收件人電話',
                        '收件人电邮',
                        '文件/包裹',
                        '收件人ID（身份证）号码',
                        '服务代码 - 请参阅服务清单表',
                        '税金DDP_DDU',
                        '总申报价值',
                        '件数',
                        '总重量',
                        '声明价值货币',
                        '备注',
                        '物品名称1',
                        '物品单价1',
                        '物品数量1',
                        '物品行郵稅税号1',
                        '物品重量(kg)1',
                        '物品名称2',
                        '物品单价2',
                        '物品数量2',
                        '物品行郵稅税号2',
                        '物品重量(kg)2',
                        '物品名称3',
                        '物品单价3',
                        '物品数量3',
                        '物品行郵稅税号3',
                        '物品重量(kg)3',
                        '物品名称4',
                        '物品单价4',
                        '物品数量4',
                        '物品行郵稅税号4',
                        '物品重量(kg)4',
                        '物品名称5',
                        '物品单价5',
                        '物品数量5',
                        '物品行郵稅税号5',
                        '物品重量(kg)5',
                    ],
                    [ //第二列
                        'Nos',
                        'customer_ref',
                        'tracking_number',
                        'sender_name',
                        'sender_tel',
                        'sender_address',
                        'consignee_name',
                        'consignee_address',
                        'consignee_country',
                        'consignee_province',
                        'consignee_city',
                        'consignee_town',
                        'consignee_zip',
                        'consignee_tel',
                        'consignee_email',
                        'shipment_type',
                        'consignee_identityno',
                        'service_code',
                        'duty_type',
                        'declare_value',
                        'pieces',
                        'total_weight',
                        'declare_currency',
                        'remark',
                        'item_name1',
                        'item_price1',
                        'item_quantity1',
                        'item_hscode1',
                        'item_Weight1',
                        'item_name2',
                        'item_price2',
                        'item_quantity2',
                        'item_hscode2',
                        'item_Weight2',
                        'item_name3',
                        'item_price3',
                        'item_quantity3',
                        'item_hscode3',
                        'item_Weight3',
                        'item_name4',
                        'item_price4',
                        'item_quantity4',
                        'item_hscode4',
                        'item_Weight4',
                        'item_name5',
                        'item_price5',
                        'item_quantity5',
                        'item_hscode5',
                        'item_Weight5',
                    ],
                ];
    }
}
