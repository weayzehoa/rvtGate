<?php

namespace App\Exports\Sheets;

use App\Models\iCarryUserAddress as UserAddressDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\iCarryProductLangEn as ProductLangEnDB;
use App\Models\Country as CountryDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingGoodMajiSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize
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
        $orderIds = $this->getOrderData($this->param,'orderShipping');
        $key = env('APP_AESENCRYPT_KEY');
        if(!empty($orderIds)){
            $items = OrderItemDB::join('orders','orders.id','order_item.order_id')
                ->join('product_model','product_model.id','order_item.product_model_id')
                ->join('product','product.id','product_model.product_id')
                ->join('vendor','vendor.id','product.vendor_id')
                ->whereIn('order_id',$orderIds)
                ->select([
                    'orders.id',
                    'orders.user_id',
                    'orders.order_number',
                    'orders.receiver_name',
                    // 'receiver_country' => CountryDB::whereColumn('countries.id','orders.to')->select('lang')->limit(1),
                    'orders.ship_to as receiver_country',
                    'orders.receiver_address',
                    // 'orders.receiver_tel',
                    DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
                    'orders.receiver_email',
                    'orders.user_memo',
                    'orders.receiver_zip_code as zip_code',
                    // 'zip_code' => UserAddressDB::whereColumn([['user_address.user_id','orders.user_id'],['user_address.name','orders.receiver_name'],['user_address.phone','orders.receiver_tel'],['user_address.email','orders.receiver_email']])
                    //             ->select('zip_code')->limit(1),
                    'product_model.sku',
                    'vendor.name as vendor_name',
                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                    'product_eng_name' =>ProductLangEnDB::whereColumn('product_en.id','product.id')
                                ->select('name')->limit(1),
                    'product.unit_name',
                    'order_item.price',
                    'order_item.quantity',
                ])->get();
            // dd($items);
            $i = 2;
            foreach ($items as $item) {
                empty($item->product_eng_name) ? $setWordColor[] = $i : '';
                $item->receiver_tel = str_replace(['o','+'],['',''],$item->receiver_tel);
                $data[] = [
                    '',
                    $item->order_number,
                    '1',
                    strtoupper($item->receiver_country),
                    $item->receiver_name,
                    $item->receiver_tel,
                    '',
                    $item->zip_code,
                    ' '.$item->receiver_address, //因為國家為第一個的話會被判定0==false
                    $item->quantity,
                    round(($item->price * 0.033), 2),
                    !empty($item->product_eng_name) ? $item->product_eng_name : $item->product_name,
                    $item->product_name,
                    '', //N
                    '',
                    '',
                    '',
                    'USD',
                    '不報關',
                    '',
                    '',
                    '',
                    'Direct Current Co.,Ltd',
                    '台北市中山區南京東路三段103號11樓',
                    '+886-906-486688',
                    'TW',
                    '', //AA
                    '',
                    '',
                    '',
                    $item->user_memo, //AE
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '代取',
                    date("Y/m/d", strtotime('+1 day')).' 下午 03:30',
                ];
                $i++;
            }
            $this->setWordColor = $setWordColor;
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $setWordColor = $this->setWordColor;
        if(!empty($setWordColor)){
            for ($i=0; $i < count($setWordColor) ; $i++) {
                $sheet->getStyle('L'.$setWordColor[$i])->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            }
        }
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('#');
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '好馬吉物流';
    }

    public function headings(): array
    {
        return [
            '提單編號',
            '訂單編號',
            '箱號',
            '收件人國家',
            '收件人姓名',
            '收件人連絡電話1',
            '收件人區域',
            '收件人郵編',
            '收件人地址',
            '品項數量',
            '品項單價',
            '品項英文名稱',
            '品項中文名稱',
            '品項料號',
            '品項品牌',
            '品項產地',
            '單箱重量(KG)',
            '提單幣別',
            '報關',
            '文件包裹',
            '服務型態',
            '收費方案',
            '寄件人姓名',
            '寄件人地址',
            '寄件人連絡電話1',
            '寄送國家',
            '寄件人連絡電話2',
            '寄件人Email',
            '寄件人區域',
            '寄件人郵編',
            '寄件備註',
            '收件人連絡電話2',
            '收件人Email',
            '外箱長(cm)',
            '外箱寬(cm)',
            '外箱高(cm)',
            'COD幣別',
            'COD金額',
            '代取',
            '取件日期',
            '取件備註',
        ];
    }

    function receiverCountry($address,$memo){
        if(strstr($memo,'蝦皮訂單：(新加坡)')){
            return 'SG';
        }else if(strstr($address,'日本') || strstr($address,'JP')){
            return 'JP';
        }else if(strstr($address,'馬來西亞') || strstr($address,'MY')){
            return 'MY';
        }else if(strstr($address,'新加坡') || strstr($address,'SG')){
            return 'SG';
        }else{
            return '';
        }
    }

    function serverCode($address,$memo){
        if(strstr($memo,'蝦皮訂單：(新加坡)')){
            return 'SGETKSG';
        }else if(strstr($address,'日本') || strstr($address,'JP')){
            return 'JPEMSEP';
        }else if(strstr($address,'馬來西亞') || strstr($address,'MY')){
            return 'MYETKMY';
        }else if(strstr($address,'新加坡') || strstr($address,'SG')){
            return 'SGETKSG';
        }else{
            return '';
        }
    }
}
