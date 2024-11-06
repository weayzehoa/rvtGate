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

class ReferFriendOrdersExportSheet4 implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
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
        $userIds = OrderDB::join($userTable,$userTable.'.id',$orderTable.'.user_id')
        ->where($orderTable.'.status','>=',1)
        ->whereNotNull($userTable.'.refer_id')
        ->whereNotIn($userTable.'.id',[2020,54961,47253])
        ->whereBetween($orderTable.'.pay_time',[$this->param['start'],$this->param['end']])
        ->select([
            $orderTable.'.user_id',
        ])->groupBy('user_id')->get()->pluck('user_id')->all();

        if(count($userIds) > 0){
            for($i=0;$i<count($userIds);$i++){
                $userId = $userIds[$i];
                $order = OrderDB::where('user_id',$userId)->where('status','>=',1)->where('pay_time','<',$this->param['start'])->select(['pay_time'])->orderBy('pay_time','asc')->first();
                if(empty($order)){
                    $order = OrderDB::join($userTable,$userTable.'.id',$orderTable.'.user_id')
                    ->where($orderTable.'.user_id',$userId)
                    ->where($orderTable.'.status','>=',1)
                    ->where($orderTable.'.pay_time','>=',$this->param['start'])
                    ->select([
                        $orderTable.'.order_number',
                        $orderTable.'.pay_time',
                        $orderTable.'.amount',
                        $orderTable.'.spend_point',
                        $orderTable.'.discount',
                        $orderTable.'.shipping_fee',
                        $orderTable.'.parcel_tax',
                        $orderTable.'.status',
                        DB::raw("sum($orderTable.amount - $orderTable.spend_point - $orderTable.discount + $orderTable.shipping_fee + $orderTable.parcel_tax) as total"),
                    ])->orderBy($orderTable.'.pay_time','asc')->first();
                    $user = DB::table($userTable.' as u')->whereNotNull('u.refer_id')
                    ->where('u.id',$userId)
                    ->select([
                        'id',
                        'name',
                        'nation',
                        DB::raw("IF(mobile IS NULL,'',AES_DECRYPT(mobile,'$key')) as mobile"),
                        'create_time',
                        'refer_id',
                        'refer_guy' => UserDB::whereColumn('id','u.refer_id')->select('name')->limit(1),
                    ])->first();
                    $data[] = [
                        $order->order_number,
                        $order->pay_time,
                        $user->id,
                        $user->nation,
                        $user->mobile,
                        $user->name,
                        $user->create_time,
                        $user->refer_id,
                        $user->refer_guy,
                        $order->total,
                        $order->amount,
                        $order->spend_point,
                        $order->discount,
                        $order->shipping_fee,
                        $order->parcel_tax,
                        $order->status > 0 && $order->status <=2 ? '尚未出貨' : ($order->status >= 3 ? '已完成' : ''),
                    ];
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('N')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('O')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return "訂單成立時間";
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 15,
            'D' => 12,
            'E' => 15,
            'F' => 15,
            'G' => 20,
            'H' => 12,
            'I' => 20,
            'J' => 12,
            'L' => 12,
            'M' => 12,
            'N' => 12,
            'O' => 12,
            'P' => 12,
        ];
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            '訂單付款時間',
            '被推薦人ID',
            '國碼',
            '電話',
            '姓名',
            '註冊時間',
            '推薦人ID',
            '推薦人',
            '付款金額',
            '商品金額',
            '行郵稅',
            '運費',
            '折扣',
            '使用點數',
            '訂單狀態'
        ];
    }
}

