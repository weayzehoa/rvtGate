<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\ProductModel as ProductModelDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\SystemSetting as SystemSettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingPSEShippingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
        $key = env('APP_AESENCRYPT_KEY');
        $order = OrderDB::with('items')->select([
            'id',
            'is_print',
            'vendor_arrival_date',
            'shipping_memo',
            'shipping_number',
            'order_number',
            'user_id',
            'origin_country',
            'ship_to',
            'book_shipping_date',
            'receiver_name',
            'receiver_email',
            'receiver_address',
            'receiver_zip_code',
            'receiver_keyword',
            'receiver_key_time',
            'shipping_method',
            'invoice_type',
            'invoice_sub_type',
            'invoice_number',
            'is_invoice_no',
            'love_code',
            'invoice_title',
            'carrier_type',
            'spend_point',
            'amount',
            'shipping_fee',
            'parcel_tax',
            'pay_method',
            'exchange_rate',
            'discount',
            'user_memo',
            'partner_order_number',
            'pay_time',
            'buyer_name',
            'buyer_email',
            'print_flag',
            'create_type',
            'status',
            'digiwin_payment_id',
            'is_call',
            'create_time',
            'admin_memo',
            'greeting_card',
            'shipping_kg_price',
            'shipping_base_price',
            DB::raw("IF(receiver_phone_number IS NULL,'',AES_DECRYPT(receiver_phone_number,'$key')) as receiver_phone_number"),
            DB::raw("IF(receiver_tel IS NULL,'',AES_DECRYPT(receiver_tel,'$key')) as receiver_tel"),
        ])->find($this->param['order_id']);
        if(!empty($order)){
            $systemSetting = SystemSettingDB::find(1);
            $usdRate = $systemSetting->exchange_rate_USD;
            $usd = round(($order->amount - $order->discount ) / $usdRate,2);
            $tmp = '';
            $nation = $this->checkNation($order->receiver_address);
            $address1=mb_substr($order->receiver_address,0,16,'utf-8');
            $address2=mb_substr($order->receiver_address,16,NULL,'utf-8');
            if(mb_strlen(mb_substr($order->receiver_address,16,NULL,'utf-8'))==0){
                $address2.="　\n　";
            }elseif(mb_strlen(mb_substr($order->receiver_address,16,NULL,'utf-8'))<16){
                $address2.="\n　";
            }
            $items = '';
            if(!empty($order->items)){
                foreach($order->items as $item){
                    $productModelName = '';
                    $productModelName = str_replace("單一規格","","-".$item->product_name);
                    $price = round($item->price / $usdRate,2);
                    $items .= $item->vendor_name.'--'.$productModelName.'*'.$item->quantity.'，';
                }
                $items = rtrim($items,'，');
            }
            $data[0] = ['','',''];
            $data[1] = [$order->created_time,'',''];
            for($i=2;$i<=6;$i++){
                $data[$i] = ['','','']; //空行
            }
            $data[7] = ['',$address1,''];
            $data[8] = ['',$address2,''];
            $data[9] = ['','',$nation];
            $data[10] = ['','',''];
            $data[11] = ['PO#'.$order->order_number,'',''];
            $data[12] = ['','',''];
            $data[13] = ['',$order->receiver_name.'　　　　　　　'.$order->receiver_tel,''];
            $data[14] = $data[15] = $data[16] = ['','',''];
            $data[17] = [$items,'US$'.$usd.'-',''];
            $data[18] = ['','',''];
            $data[19] = ['','',''];
            $data[20] = ['','','PO#'.$order->order_number."\r\niCarry",''];
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        for($i=1;$i<=21;$i++){ //全部字型12
            $sheet->getStyle('A'.$i)->getFont()->setSize(12);
            $sheet->getStyle('B'.$i)->getFont()->setSize(12);
            $sheet->getStyle('C'.$i)->getFont()->setSize(12);
        }
        $sheet->getStyle('A1:B1')->getFont()->setSize(21);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle('A18')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('B8')->getAlignment()->setWrapText(true);
        $sheet->getStyle('B9')->getAlignment()->setWrapText(true);
        $sheet->getStyle('C21')->getAlignment()->setWrapText(true);
        $sheet->getStyle('C10')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A18')->getFont()->setSize(9);
        $sheet->getStyle('A18')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
        $sheet->getStyle('B18')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        $sheet->mergeCells('A18:A20'); //合併第一欄A18-20
        $sheet->mergeCells('B18:B20'); //合併第一欄A18-20

        $sheet->getStyle('C21')->getFont()->setSize(8);
        $sheet->getStyle('C21')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
        $sheet->getStyle('C21')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        return $this->param['title'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 40,
            'C' => 18,
        ];
    }
}
