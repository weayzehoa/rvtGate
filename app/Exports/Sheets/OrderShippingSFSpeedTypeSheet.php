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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingSFSpeedTypeSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings
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
        $faraway = ["萬里區","金山區","平溪區","雙溪區","貢寮區","坪林區","烏來區","三芝區","石門區","石碇區","復興鄉","關西鎮","芎林鄉","寶山鄉","五峰鄉","橫山鄉","尖石鄉","北埔鄉","峨嵋鄉","南庄鄉","獅潭鄉","大湖鄉","泰安鄉","卓蘭鎮","東勢區","和平區","新社區","溪州鄉","竹塘鄉","二林鄉","大城鄉","中寮鄉","國姓鄉","仁愛鄉","名間鄉","集集鎮","水里鄉","魚池鄉","信義鄉","竹山鎮","鹿谷鄉","番路鄉","梅山鄉","阿里山鄉","大埔鄉","東石鄉","義竹鄉","布袋鎮","褒忠鄉","東勢鄉","台西鄉","麥寮鄉","二崙鄉","北港鎮","水林鄉","口湖鄉","四湖鄉","元長鄉","左鎮區","玉井區","楠西區","南化區","龍崎區","七股區","將軍區","北門區","白河區","東山區","大內區","田寮區","六龜區","內門區","那瑪夏區","茂林區","桃源區","甲仙區","杉林區","恆春鄉","枋寮鄉","三地門鄉","來義鄉","東港鄉","獅子鄉","牡丹鄉","林邊鄉","高樹鄉","枋山鄉","里港鄉","滿州鄉","車城鄉","鹽埔鄉","九如鄉","萬巒鄉","新園鄉","新埤鄉","春日鄉","霧台鄉","佳冬鄉","竹田鄉","泰武鄉","南州鄉","崁頂鄉","瑪家鄉","麟洛鄉","南澳鄉","冬山鄉","大同鄉","三星鄉","光復鄉","玉里鎮","新城鄉","壽豐鄉","鳳林鎮","豐濱鄉","瑞穗鄉","富里鄉","秀林鄉","萬榮鄉","卓溪鄉","成功鎮","關山鎮","卑南鄉","大武鄉","太麻里鄉","東河鄉","長濱鄉","鹿野鄉","池上鄉","延平鄉","海端鄉","達仁鄉","金峰鄉"];
        $param = $this->param;
        $data = [];
        $setColor = [];
        $key = env('APP_AESENCRYPT_KEY');
        $orderIds = $this->getOrderData($this->param,'');
        if(!empty($orderIds)){
            $company = CompanySettingDB::first();
            $items = OrderItemDB::join('orders','orders.id','order_item.order_id')
                ->join('product_model','product_model.id','order_item.product_model_id')
                ->join('product','product.id','product_model.product_id')
                ->join('vendor','vendor.id','product.vendor_id')
                ->whereIn('order_id',$orderIds)
                ->select([
                    'orders.order_number',
                    'orders.receiver_name',
                    'orders.receiver_keyword',
                    // 'orders.receiver_tel',
                    DB::raw("IF(orders.receiver_tel IS NULL,'',AES_DECRYPT(orders.receiver_tel,'$key')) as receiver_tel"),
                    'orders.receiver_address',
                    'orders.shipping_method',
                    'orders.user_memo',
                    'product_model.sku',
                    'vendor.name as vendor_name',
                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                    'product.unit_name',
                    'order_item.price',
                    'order_item.quantity',
                ])->get();
            // dd($items->toArray());
            $i = 1;
            foreach ($items as $item) {
                $item->shipping_method == 2 ? $receiverAddress = $item->receiver_address.'/'.$item->receiver_keyword : $receiverAddress = $item->receiver_address;
                strstr($item->receiver_address,"香港") ? $item->user_memo = "{$item->order_number}，附加費轉寄付，{$item->user_memo}" : $item->user_memo = "{$item->order_number}，{$item->user_memo}";
                $data[] = [
                    $item->order_number,
                    '直流電通(股)公司',
                    'iCarry-我來寄',
                    $company->service_tel,
                    $company->address,
                    '',
                    $item->receiver_name.' 收',
                    '+'.$this->phoneChange($item->receiver_tel),
                    '',
                    $receiverAddress, //J
                    '',
                    $item->sku,
                    $item->vendor_name.' '.$item->product_name,
                    $item->price,
                    '新台幣',
                    $item->quantity,
                    $item->unit_name,
                    '',//R
                    $item->user_memo,
                    '寄付月結',
                    '標準快遞',
                    '1', //V
                ];
                foreach ($faraway as $value) {
                    if (strstr($item->receiver_address, $value)) {
                        $setColor[] = $i;
                    }
                }
                $i++;
            }
        }
        $this->setColor = $setColor;
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $setColor = $this->setColor;
        if(!empty($setColor)){
            for ($i=0; $i < count($setColor) ; $i++) {
                $sheet->getStyle('J'.$setColor[$i])->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            }
        }
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('V')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('P')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        // $sheet->getStyle('J')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('M')->getAlignment()->setWrapText(true);
        // $sheet->getStyle('S')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('V')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('P')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '順豐速打單';
    }

    public function headings(): array
    {
        return [
            '用戶訂單號', //A
            '寄件公司',
            '寄件人',
            '寄件電話',
            '寄件詳細地址',
            '收件公司',
            '收件人',
            '收件電話',
            '收件手機',
            '收件詳細地址',
            '收方統編號',
            '商品編號',
            '商品名稱',
            '商品單價',
            '幣別',
            '商品數量',
            '單位',
            '包裹重量',
            '寄方備註',
            '運費付款方式',
            '業務類型',
            '件數',
            '是否正式報關',
            '報關批次',
            '代收金額',
            '保價金額',
            '標準化包裝',
            '個性化包裝',
            '簽回單',
            '自取件',
            '易碎件',
            '電子驗收',
            '超長超重服務費',
            '是否定時派送',
            '派送日期',
            '派送時段',
            '擴展欄位',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 15,
            'D' => 16,
            'E' => 45,
            'F' => 10,
            'G' => 15,
            'H' => 20,
            'I' => 15,
            'J' => 50,
            'K' => 15,
            'L' => 15,
            'M' => 45,
            'N' => 15,
            'O' => 10,
            'P' => 10,
            'Q' => 10,
            'R' => 10,
            'S' => 45,
            'T' => 20,
            'U' => 10,
            'V' => 10,
            'W' => 10,
            'X' => 10,
            'Y' => 10,
            'Z' => 10,
            'AA' => 10,
            'AB' => 10,
            'AC' => 10,
            'AD' => 10,
            'AE' => 10,
            'AF' => 10,
            'AG' => 10,
            'AH' => 10,
            'AI' => 10,
            'AJ' => 10,
            'AK' => 10,
        ];
    }
}
