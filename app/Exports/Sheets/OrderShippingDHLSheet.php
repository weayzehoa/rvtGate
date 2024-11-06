<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\Country as CountryDB;
use App\Models\UserAddress as UserAddressDB;
use App\Models\iCarryProductLangEn as ProductLangDB;
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

class OrderShippingDHLSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,ShouldAutoSize,WithHeadings
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
        $countryChineseName = ["美國","英國","加拿大","南韓","日本","澳洲","紐西蘭"];//國家中文
        $countryName = ["US","United Kiongdom","Canada","Korea","Japan","Australia","New Zealand"];//國家全名
        $countryNickname = ["US","GB","CA","KR","JP","AU","NZ"];//國家暱稱
        $orders = $this->getOrderData($this->param,'orderShipping');
        if(!empty($orders)){
            $i = 0;
            $setWordColor = [];
            foreach ($orders as $order) {
                $countryCode = null;
                foreach($countryChineseName as $key => $value){
                    if(strstr($order->receiver_address,$value) || strstr($order->receiver_address,$countryName[$key])){ //搜索字串
                        $order->receiver_address = str_replace($value,$countryName[$key],$order->receiver_address); //搜索中文字串變更為英文
                        $countryCode = $countryNickname[$key];//存入國家代碼 後面國家簡稱要用
                        break;
                    }
                }
                $items = $order->items;
                $totalGrossWeight = 0;
                $totalPrice = 0;
                foreach ($items as $item) {
                    if($item->shipping_memo == 'DHL'){
                        if($item->category_id == 3 || $item->vendor_id == 235 || $item->vendor_id == 228 || $item->vendor_id == 190){
                            $price = number_format($item->price * 0.033 * 0.3,2);
                            $totalPrice += $item->quantity * $price;
                        }else{
                            $price = number_format($item->price * 0.033 * 0.5,2);
                            $totalPrice += $item->quantity * $price;
                        }
                        $totalGrossWeight += $item->quantity * $item->gross_weight;
                    }
                }
                $data1[$i] = [
                    $order->order_number,
                    '',
                    '1',
                    '',
                    '',
                    '',
                    ceil($totalGrossWeight/1000),
                    $totalPrice,
                    'USD',
                    $order->order_number,
                ];
                $tmp = [];
                $c = 0;
                $d = 0;
                foreach($items as $item){
                    if($item->shipping_memo == 'DHL'){
                        if($item->category_id == 3 || $item->vendor_id == 235 || $item->vendor_id == 228 || $item->vendor_id == 190){
                            $price = $item->price * 0.033 * 0.3;
                        }else{
                            $price = $item->price * 0.033 * 0.5;
                        }
                        $item->product_eng_name = ProductLangDB::find($item->product_id);
                        if(!empty($item->product_eng_name)){
                            $item->product_name = $item->product_eng_name->name;
                        }else{
                            $setWordColor[$i] = $c + 1;
                        }
                        $tmp2 = [$price,$item->product_name,$item->quantity];
                        $tmp = array_merge($tmp,$tmp2);
                        $d++;
                        $c++;
                        if($c == 10){
                            break;
                        };
                    }
                }
                //補空白欄位
                $x = 10 - $d;
                if($x >= 1){
                    for ($j=1; $j <= $x ; $j++) {
                        $tmp3 = ['','',''];
                        $tmp = array_merge($tmp,$tmp3);
                    }
                }
                $data2[$i] = $tmp;
                $data3[$i] = [
                    $order->receiver_name,
                    $order->receiver_tel,
                    '',
                    $order->receiver_address,
                    '',
                    '',
                    $countryCode,
                    '',
                    '',
                    $order->receiver_zip_code,
                    '',
                    'N'
                ];
                $data[$i] = array_merge(array_merge($data1[$i],$data2[$i]),$data3[$i]);
                $i++;
            }
            $this->setWordColor = $setWordColor;
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('#');
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return 'DHL';
    }

    public function headings(): array
    {
        $head1 = [
            '商業發票號碼*',
            '寄件人員',
            '包裹數量*',
            '長',
            '寬',
            '高',
            '包裹重量*',
            '海關總值*',
            '幣別*',
            '提單貨件參考資料',
        ];
        for($i=1; $i<=10; $i++){
            if($i==1){
                $head2[] = '報關商品單價'.$i.'*';
                $head2[] = '報關商品說明'.$i.'*';
                $head2[] = '報關商品數量'.$i.'*';
            }else{
                $head2[] = '報關商品單價'.$i;
                $head2[] = '報關商品說明'.$i;
                $head2[] = '報關商品數量'.$i;
            }
        }
        $head3 = [
            '收件聯絡人*',
            '收件人聯絡電話*',
            '收件人公司名稱',
            '收件人地址(一)*',
            '收件人地址(二)',
            '收件人地址(三)',
            '收件人國家代碼*',
            '收件人州/省',
            '收件人城市*',
            '收件人郵政編碼*',
            '單位',
            'DTP'
        ];
        return $head = array_merge(array_merge($head1,$head2),$head3);
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
            'AA' => 10,
            'AB' => 10,
            'AC' => 15,
            'AD' => 15,
            'AE' => 10,
        ];
    }
}
