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

class OrderShippingScooterShippingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
            $i = 0;
            foreach ($orders as $order) {
                $address1 = mb_substr($order->receiver_address,0,16,'utf-8');
                $address2 = mb_substr($order->receiver_address,16,null,'utf-8');

                for($j=1;$j<=2;$j++){ //空2行
                    $data[$i] = [''];
                    $i++;
                }
                $data[$i] = ['收件人：'.$order->receiver_name];
                $i++;
                $data[$i] = ['PO# '.$order->order_number,'','']; //空一行
                $i++;
                $data[$i] = ['電話：'.$this->phoneChange($order->receiver_tel)];
                $setSize11[] = $i;
                $i++;
                $data[$i] = [$address1];
                $i++;
                $data[$i] = [$address2];
                $i++;
            }
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {

        $count = $this->count;

        $sheet->getStyle('A')->getAlignment()->setWrapText(true); //自動換行

        for($i=1;$i<=$count;$i++){ //全部字型16
            $sheet->getStyle('A'.$i)->getFont()->setSize(16);
        }
    }

    public function title(): string
    {
        return '巨邦機車快遞';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
        ];
    }
}
