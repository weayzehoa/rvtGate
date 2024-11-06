<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use App\Models\MachineList as MachineListDB;
use App\Models\MposRecord as MposRecordDB;
use App\Models\Vendor as VendorDB;
use App\Models\VendorAccount as VendorAccountDB;
use DB;
use Carbon\Carbon;

class ACPayMachineDetailAccountingExport implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings
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

        $today = Carbon::now();
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
        $orders = MposRecordDB::join('machine_lists','machine_lists.id','mpos_records.machine_list_id');
        $cate == 'record' ? $type == 'detail' ? $orders = $orders->whereIn('mpos_records.id',$record_ids) : $orders = $orders->where('mpos_records.machine_list_id',$machine_list_id) : $orders = $orders->whereIn('mpos_records.machine_list_id',$ids);
        $orders = $orders->whereBetween('mpos_records.pay_time',[$pay_time.' 00:00:00.000',$pay_time_end.' 23:59:59.999']);

        !empty($vendorId) ? $orders = $orders->where('machine_lists.vendor_id',$vendorId) : '';
        !empty($status) ? $orders = $orders->whereIn('mpos_records.status', explode(',', $status)) : '';
        !empty($order_number) ? $orders = $orders->where('mpos_records.order_number', 'like', "%$order_number%") : '';
        !empty($device_order_number) ? $orders = $orders->where('mpos_records.device_order_number', 'like', "%$device_order_number%") : '';
        !empty($free_shipping) ? $orders = $orders->where('mpos_records.free_shipping', $free_shipping) : '';

        $orders = $orders->select([
            'mpos_records.*',
            DB::raw("CONCAT('C',LPAD(machine_lists.id, 5, 0)) as machine_number"),
            'shop_name' => MachineListDB::whereColumn('machine_lists.id','mpos_records.machine_list_id')->select('name')->limit(1), //店名
            'vendor_name' => VendorDB::whereColumn('vendors.id','machine_lists.vendor_id')->select('name')->limit(1), //店家名稱
            'account' => VendorAccountDB::whereColumn('vendor_accounts.id','machine_lists.vendor_account_id')->select('account')->limit(1), //機台帳號
        ])->orderBy('id','asc')->get();

        foreach($orders as $order){
            if($order->free_shipping == 1){
                $order->shippingFee = $order->base_shipping_fee + $order->boxes * $order->each_box_shipping_fee;
            }else{
                $order->shippingFee = 0;
            }
            $order->productPice = $order->amount - $order->shippingFee;
            ($order->shippingFee + $order->productPice) >= 0 ? $order->productPice = $order->amount : '';

            if($order->shipping_method != 3){
                $order->payDraw = $order->productPice * $order->payment_percent / 100;
            }else{
                $order->payDraw = 0;
            }
            $order->payDraw = intval(ceil($order->payDraw));
            $order->icarryIncome = $order->payDraw + $order->shippingFee;

            $data[] = [
                $order->order_number,
                $order->pay_time,
                $this->statusText($order->status),
                $order->device_order_number,
                $order->free_shipping == 1 ? '是' : '否',
                $order->status == -2 ? $order->amount.'('.$order->refund_amount.')' : $order->amount,
                $order->productPice,
                $order->shippingFee,
                $order->base_shipping_fee,
                $order->payDraw,
                $order->icarryIncome,
                $order->pay_method,
                $order->payment_percent,
                $this->shippingText($order->shipping_method),
                $order->machine_number,
                $order->account,
                $order->shop_name,
                $order->vendor_name,
            ];
        }

        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#'); //數字改字串
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('K')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('M')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('Q')->getAlignment()->setWrapText(true);
        $sheet->getStyle('R')->getAlignment()->setWrapText(true);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }
    public function title(): string
    {
        return 'ACPay機台交易明細';
    }
    public function headings(): array
    {
        return [
            '訂單編號',
            '交易時間',
            '訂單狀態',
            '交易編號',
            '是否免運',
            '客人實付金額',
            '商品金額',
            '運費',
            '基本費',
            '金流抽成',
            'iCarry實收金額',
            '金流方式',
            '抽成(%)',
            '物流方式',
            '機台編號',
            '帳號',
            '店名',
            '商家名稱',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 20,
            'D' => 12,
            'E' => 12,
            'F' => 15,
            'G' => 15,
            'H' => 10,
            'I' => 10,
            'J' => 15,
            'K' => 16,
            'L' => 12,
            'M' => 12,
            'N' => 12,
            'O' => 12,
            'P' => 12,
            'Q' => 25,
            'R' => 20,
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
    public function statusText($str){
        switch($str){
            case -2:return "已退貨";break;
            case -1:return "已取消";break;
            case 0:return "尚未付款";break;
            case 1:return "已付款待出貨";break;
            case 2:return "訂單集貨中";break;
            case 3:return "訂單已出貨";break;
            case 4:return "訂單已完成";break;
        }
    }
    public function shippingText($str){
        switch($str){
            case 1:return "機場提貨";break;
            case 2:return "旅店提貨";break;
            case 3:return "現場提貨";break;
            case 4:return "寄送海外";break;
            case 5:return "寄送台灣";break;
            case 6:return "寄送當地";break;
        }
    }
}
