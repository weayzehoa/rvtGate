<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use App\Models\MachineList as MachineListDB;
use App\Models\MposRecord as MposRecordDB;
use App\Models\Vendor as VendorDB;
use App\Models\VendorAccount as VendorAccountDB;
use DB;
use Carbon\Carbon;

class ACPayMachineTableAccountingExport implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
        $param = $this->param;
        $data = [];

        $today = Carbon::now(); //Current Date and Time
        if(env('APP_ENV') == 'production'){
            $firstDayofMonth = Carbon::parse($today)->firstOfMonth()->toDateString(); //本月第一天
            $lastDayofMonth = Carbon::parse($today)->endOfMonth()->toDateString(); //本月最後一天
            $pay_time = $firstDayofMonth;
            $pay_time_end = $lastDayofMonth;
        }else{
            $pay_time = '2019-01-01';
            $pay_time_end = '2021-06-30';
        }
        //將進來的資料作參數轉換
        foreach ($param as $key => $value) {
            $$key = $value;
        }
        $machines = MposRecordDB::join('machine_lists','machine_lists.id','mpos_records.machine_list_id')
        ->whereBetween('mpos_records.pay_time',[$pay_time.' 00:00:00.000',$pay_time_end.' 23:59:59.999'])
        ->whereNotIn('mpos_records.status',[-1,0]);

        !empty($vendorId) ? $machines = $machines->where('machine_lists.vendor_id',$vendorId) : '';
        !empty($type) && $type != 'condition' ? $machines = $machines->whereIn('machine_lists.id',$param['ids']) : '';
        !empty($shop) ? $machines = $machines->where('name','like',"%$shop%") : '';

        if(!empty($account)){
            $vendorAccountIds = VendorAccountDB::where('account','like',"%$account%")->select('id')->get()->pluck('id')->all();
            $machines = $machines->whereIn('vendor_account_id',$vendorAccountIds);
        }
        if(!empty($vendor_name)){
            $vendorIds = VendorDB::where('name','like',"%$vendor_name%")->select('id')->get()->pluck('id')->all();
            $machines = $machines->whereIn('vendor_id',$vendorIds);
        }

        $machines = $machines->select([
            'machine_lists.id', //機台id
            'machine_lists.name', //店名
            DB::raw("CONCAT('C',LPAD(machine_lists.id, 5, 0)) as machine_number"), //機台編號
            'vendor_name' => VendorDB::whereColumn('vendors.id','machine_lists.vendor_id')->select('name')->limit(1), //店家名稱
            'company' => VendorDB::whereColumn('vendors.id','machine_lists.vendor_id')->select('company')->limit(1), //店家名稱
            'account' => VendorAccountDB::whereColumn('vendor_accounts.id','machine_lists.vendor_account_id')->select('account')->limit(1), //機台帳號
            DB::raw("SUM(CASE WHEN mpos_records.status > 0 THEN 1 WHEN mpos_records.status = -2 and mpos_records.amount + mpos_records.refund_amount != 0 THEN 1 ELSE 0 END) as count"), //訂單筆數
            DB::raw("SUM(CASE WHEN mpos_records.status > 0 THEN mpos_records.amount WHEN mpos_records.status = -2 THEN mpos_records.amount + mpos_records.refund_amount END) as amount"), //客人實付小計
            DB::raw("SUM(CASE WHEN mpos_records.status > 0 THEN mpos_records.base_shipping_fee + mpos_records.boxes * each_box_shipping_fee WHEN mpos_records.status = -2 and mpos_records.amount + mpos_records.refund_amount != 0 THEN mpos_records.base_shipping_fee + mpos_records.boxes * each_box_shipping_fee ELSE 0 END) as shippingFee"), //運費小計
            DB::raw("SUM(CASE WHEN mpos_records.status > 0 THEN (CASE WHEN mpos_records.free_shipping = 1 THEN mpos_records.amount ELSE mpos_records.amount - mpos_records.base_shipping_fee - mpos_records.boxes * each_box_shipping_fee END) WHEN mpos_records.status = -2 and mpos_records.amount + mpos_records.refund_amount != 0 THEN (CASE WHEN mpos_records.free_shipping = 1 THEN mpos_records.amount ELSE mpos_records.amount - mpos_records.base_shipping_fee - mpos_records.boxes * each_box_shipping_fee END) END) as productPrice"), //商品金額
            DB::raw("SUM(CASE WHEN mpos_records.status > 0 THEN (CASE WHEN mpos_records.shipping_method != 3 THEN (CASE WHEN mpos_records.free_shipping = 1 THEN mpos_records.amount ELSE mpos_records.amount - mpos_records.base_shipping_fee - mpos_records.boxes * each_box_shipping_fee END)*(mpos_records.payment_percent)/100 ELSE 0 END) WHEN mpos_records.status = -2 and mpos_records.amount + mpos_records.refund_amount != 0 THEN (CASE WHEN mpos_records.shipping_method != 3 THEN (CASE WHEN mpos_records.free_shipping = 1 THEN mpos_records.amount ELSE mpos_records.amount - mpos_records.base_shipping_fee - mpos_records.boxes * each_box_shipping_fee END)*(mpos_records.payment_percent)/100 ELSE 0 END) END) as payDraw"), //抽成金額
            'machine_lists.bank', //收款行
        ])->groupBy('machine_list_id');
        $machines = $machines->orderBy('id','asc')->get();
        $data[0] = ['交易區間：'.$pay_time.' ~ '.$pay_time_end];
        $data[1] = [''];
        $data[2] = ['帳號','機台編號','店名','商家名稱','公司','訂單筆數','客人實付小計','商品金額小計','運費小計(含基本費)','金流抽成小計','iCarry實收小計','收款行'];
        foreach($machines as $machine){
            $data[] = [
                $machine->account,
                $machine->machine_number,
                $machine->name,
                $machine->vendor_name,
                $machine->company,
                number_format($machine->count),
                number_format($machine->amount),
                number_format($machine->productPrice),
                number_format($machine->shippingFee),
                number_format(intval(ceil($machine->payDraw))),
                number_format(intval(ceil($machine->payDraw)) + $machine->shippingFee),
                $machine->bank,
            ];
        }

        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle(1)->getFont()->setSize(24)->setBold(true); //第一行字型大小
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('C')->getAlignment()->setWrapText(true);
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);
        $sheet->getStyle('E')->getAlignment()->setWrapText(true);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }
    public function title(): string
    {
        return 'ACPay機台資料';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 40,
            'D' => 30,
            'E' => 30,
            'F' => 15,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 20,
            'L' => 10,
        ];
    }
    public function properties(): array
    {
        return [
            'creator'        => 'iCarry系統管理員',
            'lastModifiedBy' => 'iCarry系統管理員',
            'title'          => 'iCarry後台管理-ACPay機台資料匯出',
            'description'    => 'iCarry後台管理-ACPay機台資料匯出',
            'subject'        => 'iCarry後台管理-ACPay機台資料匯出',
            'keywords'       => '',
            'category'       => '',
            'manager'        => 'iCarry系統管理員',
            'company'        => 'iCarry.me 直流電通股份有限公司',
        ];
    }
}
