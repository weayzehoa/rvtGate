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

class ReferFriendOrdersExportSheet2 implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
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
        $users = DB::table($userTable.' as u')->whereNotNull('refer_id')
        ->whereBetween('create_time',[$this->param['start'],$this->param['end']])
        ->select([
            'id',
            'name',
            'nation',
            DB::raw("IF(mobile IS NULL,'',AES_DECRYPT(mobile,'$key')) as mobile"),
            'create_time',
            'refer_id',
            'refer_guy' => UserDB::whereColumn('id','u.refer_id')->select('name')->limit(1),
            'a' => OrderDB::whereColumn('user_id','u.id')->select([DB::raw("sum(amount+shipping_fee+parcel_tax) as a")])->limit(1),
            'b' => OrderDB::whereColumn('user_id','u.id')->select([DB::raw("sum(amount-spend_point-discount+shipping_fee+parcel_tax) as b")])->limit(1),
        ])->get();
        foreach($users as $user){
            if(!empty($user->a)){
                $data[] = [
                    $user->id,
                    $user->nation,
                    $user->mobile,
                    $user->name,
                    $user->create_time,
                    $user->refer_id,
                    $user->refer_guy,
                    $user->a,
                    $user->b
                ];
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
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
            'A' => 13,
            'B' => 7,
            'C' => 16,
            'D' => 20,
            'E' => 22,
            'F' => 12,
            'G' => 20,
            'H' => 30,
            'I' => 36,
        ];
    }

    public function headings(): array
    {
        return [
            '被推薦人ID',
            '國碼',
            '電話',
            '姓名',
            '註冊時間',
            '推薦人ID',
            '推薦人',
            '訂單金額(不包含扣點和折扣)',
            '消費者付款金額(包含扣點和折扣)'
        ];
    }
}

