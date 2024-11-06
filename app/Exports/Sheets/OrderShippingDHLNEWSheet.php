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

class OrderShippingDHLNEWSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,ShouldAutoSize,WithHeadings
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
        $country = [
            "美國" => "America",
            "英國" => "United Kingdom",
            "加拿大" => "Canada",
            "南韓" => "South Korea",
            "韓國" => "South Korea",
            "日本" => "Japan",
            "台灣" => "Taiwan",
            "澳洲" => "Australia",
            "新加坡" => "Singapore",
            "法國" => "France",
            "泰國" => "Thailand",
            "泰國-曼谷" => "Thailand",
            "中國" => "China",
            "中国" => "China",
            "香港" => "Hongkong",
            "澳門" => "Macao",
            "馬來西亞" => "Malaysia",
            "紐西蘭" => "New Zealand",
        ]; //國家英文名字
        $countryCode = [
            "美國" => "US",
            "英國" => "UK",
            "加拿大" => "CA",
            "南韓" => "KR",
            "韓國" => "KR",
            "日本" => "JP",
            "台灣" => "TW",
            "澳洲" => "AU",
            "新加坡" => "SG",
            "法國" => "FR",
            "泰國" => "TH",
            "泰國-曼谷" => "TH",
            "中國" => "CN",
            "中国" => "CN",
            "香港" => "HK",
            "澳門" => "MO",
            "馬來西亞" => "MY",
            "紐西蘭" => "NZ",
        ]; //國家代碼
        $orders = $this->getOrderData($this->param,'orderShipping');
        if(!empty($orders)){
            foreach ($orders as $order) {
                $items = $order->items;
                $totalGrossWeight = 0;
                $totalPrice = 0;
                foreach ($items as $item) {
                    if($item->is_del == 0){
                        if($item->category_id == 3 || $item->vendor_id == 235 || $item->vendor_id == 228 || $item->vendor_id == 190){
                            $price = $item->price * 0.033 * 0.3;
                        }else{
                            $price = $item->price * 0.033 * 0.5;
                        }
                        $data[] =[
                            $order->order_number,
                            $order->createTime,
                            $order->receiver_name,
                            null,
                            ltrim(str_replace($order->ship_to,'',$order->receiver_address),' '),
                            null,
                            '  ',
                            $order->receiver_zip_code,
                            null,
                            $country[$order->ship_to], //Destination Country
                            $order->receiver_email,
                            !empty($order->receiver_tel) ? $order->receiver_tel : '00000000',
                            !empty($item->product_eng_name) ? $item->product_eng_name : $item->product_name,
                            round($price,2), //N
                            null,
                            round($item->gross_weight / 1000,2),
                            null,
                            null,
                            $item->sku,
                            $item->quantity,
                            null,
                            null,
                            'N',
                            $countryCode[$order->ship_to],
                            null,
                            null, //Z
                            null,
                            null,
                            null,
                            null,
                            'USD',
                            '1905.901050',
                            null,
                            null,
                            'Pastries',
                            'N',
                            'Taiwan',
                            'N',
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                        ];
                    }
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('L')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('L')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('T')->setAutoSize(true);
        $sheet->getColumnDimension('U')->setAutoSize(true);
        $sheet->getColumnDimension('V')->setAutoSize(true);
        $sheet->getColumnDimension('W')->setAutoSize(true);
        $sheet->getColumnDimension('X')->setAutoSize(true);
        $sheet->getColumnDimension('Y')->setAutoSize(true);
        $sheet->getColumnDimension('Z')->setAutoSize(true);
        $sheet->getColumnDimension('AD')->setAutoSize(true);
        $sheet->getColumnDimension('AE')->setAutoSize(true);
        $sheet->getColumnDimension('AF')->setAutoSize(true);
        $sheet->getColumnDimension('AG')->setAutoSize(true);
        $sheet->getColumnDimension('AH')->setAutoSize(true);
        $sheet->getColumnDimension('AI')->setAutoSize(true);
        $sheet->getColumnDimension('AJ')->setAutoSize(true);
        $sheet->getColumnDimension('AK')->setAutoSize(true);
        $sheet->getColumnDimension('AL')->setAutoSize(true);
    }

    public function title(): string
    {
        return 'DHL';
    }

    public function headings(): array
    {
        return [
            'Order Number',
            'Date',
            'To Name',
            'Destination Building',
            'Destination Street',
            'Destination Suburb',
            'Destination City',
            'Destination Postcode',
            'Destination State',
            'Destination Country',
            'Destination Email',
            'Destination Phone',
            'Item Name',
            'Item Price',
            'Instructions',
            'Weight',
            'Shipping Method',
            'Reference',
            'SKU',
            'Qty',
            'Company',
            'Signature Required',
            'ATL',
            'Country Code',
            'Package Height',
            'Package Width',
            'Package Length',
            'Carrier',
            'Carrier Product Code',
            'Carrier Product Unit Type',
            'Declared Value Currency',
            'Code',
            'Color',
            'Size',
            'Contents',
            'Dangerous Goods',
            'Country of Manufacturer',
            'DDP',
            'ReceiverVAT',
            'ReceiverEORI',
            'ShippingFreightValue',
            'Brand',
            'Usage',
            'Material',
            'Model',
            'MID Code',
            'Receiver National ID',
            'Receiver Passport Number',
            'Receiver USCI',
            'Receiver CR',
            'Receiver Brazil CNP',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 15,
            'D' => 16,
            'E' => 60,
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
            'AA' => 15,
            'AB' => 10,
            'AC' => 20,
            'AD' => 25,
            'AE' => 25,
            'AF' => 20,
            'AG' => 8,
            'AH' => 8,
            'AI' => 10,
            'AJ' => 18,
            'AK' => 25,
            'AL' => 10,
            'AM' => 10,
            'AN' => 10,
            'AO' => 10,
            'AP' => 10,
            'AQ' => 10,
            'AR' => 10,
            'AS' => 10,
            'AT' => 10,
            'AU' => 10,
            'AV' => 10,
            'AW' => 10,
            'AX' => 10,
            'AY' => 10,
        ];
    }
}
