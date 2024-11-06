<?php

namespace App\Exports\Sheets;

use App\Models\iCarryUser as UserDB;
use App\Models\iCarryOrder as OrderDB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

class ReferFriendOrdersExportSheet3 implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
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
        $key = env('APP_AESENCRYPT_KEY');
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $userTable = env('DB_ICARRY').'.'.(new UserDB)->getTable();
        $year = $this->param['year'];
        $month = $this->param['month'];
        $data = [];
        $orders = OrderDB::join($userTable,$userTable.'.id',$orderTable.'.user_id')
        ->whereNotNull($userTable.'.refer_id')
        ->whereBetween($userTable.'.create_time',[$this->param['start'],$this->param['end']])
        ->select([
            $orderTable.'.order_number',
            $orderTable.'.user_id',
            $orderTable.'.receiver_name',
            $orderTable.'.amount',
            $orderTable.'.parcel_tax',
            $orderTable.'.shipping_fee',
            $orderTable.'.discount',
            $orderTable.'.spend_point',
            $orderTable.'.create_time',
            $orderTable.'.status',
        ])->get();
        foreach($orders as $order){
            $data[] = [
                $order->order_number,
                $order->user_id,
                $order->receiver_name,
                $order->amount,
                $order->parcel_tax,
                $order->shipping_fee,
                $order->discount,
                $order->spend_point,
                $order->create_time,
                $order->status == -1 ? '訂單取消' : ($order->status == 0 ? '未付款' : ($order->status > 0 && $order->status <=2 ? '尚未出貨' : ($order->status >= 3 ? '已完成' : ''))),
            ];
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return "前述條件，扣除沒有訂單的後如下";
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 12,
            'C' => 30,
            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 20,
            'J' => 12,
        ];
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            'user id',
            '收件人',
            '商品金額',
            '行郵稅',
            '運費',
            '折扣',
            '使用點數',
            '訂單建立時間',
            '訂單狀態'
        ];
    }
}

