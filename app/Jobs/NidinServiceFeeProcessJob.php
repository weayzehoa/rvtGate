<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\ErpACRTA as ErpACRTADB;
use App\Models\ErpACRTB as ErpACRTBDB;
use App\Models\ErpACPTA as ErpACPTADB;
use App\Models\ErpACPTB as ErpACPTBDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ACErpCustomer as ACErpCustomerDB;
use App\Models\ACErpVendor as ACErpVendorDB;
use App\Models\ACErpACRTA as ACErpACRTADB;
use App\Models\ACErpACRTB as ACErpACRTBDB;
use App\Models\ACErpACPTA as ACErpACPTADB;
use App\Models\ACErpACPTB as ACErpACPTBDB;
use App\Models\SerialNoRecord as SerialNoRecordDB;

class NidinServiceFeeProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param = $this->param;
        $param['admin_id'] = 1;
        $order = OrderDB::with('customer','erpOrder')->find($param['orderId']);
        $vendor = VendorDB::find($param['vendorId']);
        $erpVendor = ErpVendorDB::where('MA001',$vendor->digiwin_vendor_no)->first(); //直流的廠商
        $acErpVendor = ACErpVendorDB::where('MA001',$vendor->ac_digiwin_vendor_no)->first(); //交流的廠商
        $acErpCustomer = ACErpCustomerDB::find('A00002'); //交流的直流客戶
        $date6 = date('ymd');
        //找出交流鼎新單據號碼當日最後一筆結帳單單號
        $chkTemp = SerialNoRecordDB::where([['type','ACErpACRTBNo'],['serial_no','like',"$date6%"]])->orderBy('serial_no','desc')->first();
        !empty($chkTemp) ? $ACTB002No = $chkTemp->serial_no + 1 : $ACTB002No = $date6.str_pad(1,5,0,STR_PAD_LEFT);
        $chkTemp = SerialNoRecordDB::create(['type' => 'ACErpACRTBNo','serial_no' => $ACTB002No]);
        //檢查鼎新預收結帳單有沒有這個號碼
        $tmp = ACErpACRTBDB::where('TB002','like',"%$date6%")->select('TB002')->orderBy('TB002','desc')->first();
        if(!empty($tmp)){
            if($tmp->TB002 >= $ACTB002No){
                $ACTB002No = $tmp->TB002+1;
                $chkTemp = SerialNoRecordDB::create(['type' => 'ACErpACRTBNo','serial_no' => $ACTB002No]);
            }
        }
        //找出交流鼎新單據號碼當日最後一筆應付單號
        $chkTemp = SerialNoRecordDB::where([['type','ACErpACPTANo'],['serial_no','like',"$date6%"]])->orderBy('serial_no','desc')->first();
        !empty($chkTemp) ? $ACTA002No = $chkTemp->serial_no + 1 : $ACTA002No = $date6.str_pad(1,5,0,STR_PAD_LEFT);
        $chkTemp = SerialNoRecordDB::create(['type' => 'ACErpACPTANo','serial_no' => $ACTA002No]);
        //檢查交流鼎新應付單有沒有這個號碼
        $tmp = ACErpACPTADB::where('TA002','like',"%$date6%")->select('TA002')->orderBy('TA002','desc')->first();
        if(!empty($tmp)){
            if($tmp->TA002 >= $ACTA002No){
                $ACTA002No = $tmp->TA002+1;
                $chkTemp = SerialNoRecordDB::create(['type' => 'ACErpACPTANo','serial_no' => $ACTA002No]);
            }
        }
        //找出直流鼎新單據號碼當日最後一筆應付單號
        $chkTemp = SerialNoRecordDB::where([['type','ACPTANo'],['serial_no','like',"$date6%"]])->orderBy('serial_no','desc')->first();
        !empty($chkTemp) ? $TA002No = $chkTemp->serial_no + 1 : $TA002No = $date6.str_pad(1,5,0,STR_PAD_LEFT);
        $chkTemp = SerialNoRecordDB::create(['type' => 'ACPTANo','serial_no' => $TA002No]);
        //檢查交流鼎新應付單有沒有這個號碼
        $tmp = ACErpACPTADB::where('TA002','like',"%$date6%")->select('TA002')->orderBy('TA002','desc')->first();
        if(!empty($tmp)){
            if($tmp->TA002 >= $TA002No){
                $TA002No = $tmp->TA002+1;
                $chkTemp = SerialNoRecordDB::create(['type' => 'ACPTANo','serial_no' => $TA002No]);
            }
        }
        //稅金計算
        if($order->customer['MA038'] == 1){
            $serviceFee = round($param['returnServiceFeeTotal'],0);
            $tax = round($serviceFee - $serviceFee / 1.05,0);
            $serviceFeeWithoutTax = $serviceFee - $tax;
        }elseif($order->customer['MA038'] == 2){
            $serviceFeeWithoutTax = $serviceFee = round($param['returnServiceFeeTotal'] / 1.05,0);
            $tax = round($serviceFee * 0.05,0);
        }elseif($order->customer['MA038'] == 3){
            $serviceFee = round($param['returnServiceFeeTotal'],0);
            $tax = 0;
            $serviceFeeWithoutTax = $serviceFee - $tax;
        }
        //備註
        $memo = mb_substr($param['returnServiceFee'].',費率'.$param['returnServiceRate'].',票券號碼:'.$param['returnTicketNos'],0,200);

        //直流建立(負數)應付單
        $this->createErpACPTA($date6,$TA002No,$erpVendor,$serviceFee,$serviceFeeWithoutTax,$tax,$memo);
        //交流建立(負數)應付單
        $this->createACErpACPTA($date6,$ACTA002No,$acErpVendor,$serviceFee,$serviceFeeWithoutTax,$tax,$memo);
        //交流建立(負數)結帳單
        $this->createACErpACRTA($date6,$ACTB002No,$order,$serviceFee,$serviceFeeWithoutTax,$tax,$memo);
    }


    private function createErpACPTA($date6,$TA002No,$erpVendor,$serviceFee,$serviceFeeWithoutTax,$tax,$memo)
    {
        //直流建立(負數)應付單
        ErpACPTBDB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => 'iCarryGate',
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => date('Ymd'),
            'FLAG' => 1,
            'CREATE_TIME' => date('H:i:s'),
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'ACRI02',
            'TB001' => 'A711',  //憑單單別
            'TB002' => $TA002No,  //憑單單號
            'TB003' => '0001',  //憑單序號
            'TB004' => 9,  //來源
            'TB005' => '',  //憑證單別
            'TB006' => '',  //憑證單號
            'TB007' => '',  //憑證序號
            'TB008' => date('Ymd'),  //憑證日期
            'TB009' => -round($serviceFee,0),  //應付金額
            'TB010' => 0,  //差額
            'TB011' => $memo,  //備註
            'TB012' => 'Y',  //確認碼
            'TB013' => 5133,  //科目編號
            'TB014' => '',  //費用部門
            'TB015' => -round($serviceFeeWithoutTax,0),  //原幣未稅金額
            'TB016' => -round($tax,0),  //原幣稅額
            'TB017' => -round($serviceFeeWithoutTax,0),  //本幣未稅金額
            'TB018' => -round($tax,0),  //本幣稅額
            'TB019' => '',  //專案代號
            'TB020' => 0,  //營業稅稅基
            'TB021' => '',  //訂金序號
        ]);
        ErpACPTADB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => 'iCarryGate',
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => date('Ymd'),
            'FLAG' => 1,
            'CREATE_TIME' => date('H:i:s'),
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI06',
            'TA001' => 'A711', //憑單單別
            'TA002' => $TA002No, //憑單單號
            'TA003' => $date6, //憑單日期
            'TA004' => $erpVendor->MA001, //供應廠商
            'TA005' => '001', //廠別
            'TA006' => $erpVendor->MA005, //統一編號
            'TA008' => 'NTD', //幣別
            'TA009' => 1, //匯率
            'TA010' => $erpVendor->MA030, //發票聯數
            'TA011' => $erpVendor->MA044, //課稅別
            'TA012' => 1, //扣抵區分
            'TA013' => 'N', //煙酒註記
            'TA014' => '', //發票號碼
            'TA015' => '', //發票日期
            'TA016' => -round($serviceFee,0), //發票貨款
            'TA017' => -round($tax,0), //發票稅額
            'TA018' => 'N', //發票作廢
            'TA019' => '', //預計付款日
            'TA020' => '', //預計兌現日
            'TA021' => '', //備註
            'TA022' => '', //採購單別
            'TA023' => '', //採購單號
            'TA024' => 'Y', //確認碼
            'TA025' => 'N', //更新碼
            'TA026' => 'N', //結案碼
            'TA027' => 0, //列印次數
            'TA028' => -round($serviceFee,0), //應付金額
            'TA029' => -round($tax,0), //營業稅額
            'TA030' => 0, //已付金額
            'TA031' => 'N', //產生分錄碼
            'TA032' => date('Ym'), //申報年月
            'TA033' => 'N', //凍結付款碼
            'TA034' => date('Ymd'), //單據日期
            'TA035' => 'iCarry', //確認者
            'TA036' => 0.05, //營業稅率
            'TA037' => -round($serviceFee,0), //本幣應付金額
            'TA038' => -round($tax,0), //本幣營業稅額
            'TA039' => 'N', //簽核狀態碼
            'TA040' => 0, //本幣已付金額
            'TA041' => 0, //已沖稅額
            'TA042' => 0, //傳送次數
            'TA043' => 0, //代徵營業稅
            'TA044' => 0, //本幣完稅價格
            'TA045' => $erpVendor->MA055, //付款條件代號
            'TA052' => '', //訂金序號
            'TA056' => '', //來源
            'TA071' => '', //連絡人EMAIL
            'TA082' => '', //作廢日期
            'TA083' => '', //作廢時間
            'TA084' => '', //作廢原因
            'TA085' => '', //預留欄位
            'TA086' => '', //預留欄位
            'TA087' => '', //作廢折讓單號
            'TA088' => '', //折讓證明單號碼
            'TA089' => '', //折讓單簽回日期
            'TA090' => '', //買方開立折讓單
        ]);
    }

    private function createACErpACPTA($date6,$ACTA002No,$acErpVendor,$serviceFee,$serviceFeeWithoutTax,$tax,$memo)
    {
        //交流建立(負數)應付單
        ACErpACPTBDB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => 'iCarryGate',
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => date('Ymd'),
            'FLAG' => 1,
            'CREATE_TIME' => date('H:i:s'),
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'ACRI02',
            'TB001' => 'A711',  //憑單單別
            'TB002' => $ACTA002No,  //憑單單號
            'TB003' => '0001',  //憑單序號
            'TB004' => 9,  //來源
            'TB005' => '',  //憑證單別
            'TB006' => '',  //憑證單號
            'TB007' => '',  //憑證序號
            'TB008' => date('Ymd'),  //憑證日期
            'TB009' => -round($serviceFee,0),  //應付金額
            'TB010' => 0,  //差額
            'TB011' => $memo,  //備註
            'TB012' => 'Y',  //確認碼
            'TB013' => 5136,  //科目編號
            'TB014' => '',  //費用部門
            'TB015' => -round($serviceFeeWithoutTax,0),  //原幣未稅金額
            'TB016' => -round($tax,0),  //原幣稅額
            'TB017' => -round($serviceFeeWithoutTax,0),  //本幣未稅金額
            'TB018' => -round($tax,0),  //本幣稅額
            'TB019' => '',  //專案代號
            'TB020' => 0,  //營業稅稅基
            'TB021' => '',  //訂金序號
        ]);
        ACErpACPTADB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => 'iCarryGate',
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => date('Ymd'),
            'FLAG' => 1,
            'CREATE_TIME' => date('H:i:s'),
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI06',
            'TA001' => 'A711', //憑單單別
            'TA002' => $ACTA002No, //憑單單號
            'TA003' => $date6, //憑單日期
            'TA004' => $acErpVendor->MA001, //供應廠商
            'TA005' => '001', //廠別
            'TA006' => $acErpVendor->MA005, //統一編號
            'TA008' => 'NTD', //幣別
            'TA009' => 1, //匯率
            'TA010' => $acErpVendor->MA030, //發票聯數
            'TA011' => $acErpVendor->MA044, //課稅別
            'TA012' => 1, //扣抵區分
            'TA013' => 'N', //煙酒註記
            'TA014' => '', //發票號碼
            'TA015' => '', //發票日期
            'TA016' => -round($serviceFee,0), //發票貨款
            'TA017' => -round($tax,0), //發票稅額
            'TA018' => 'N', //發票作廢
            'TA019' => '', //預計付款日
            'TA020' => '', //預計兌現日
            'TA021' => '', //備註
            'TA022' => '', //採購單別
            'TA023' => '', //採購單號
            'TA024' => 'Y', //確認碼
            'TA025' => 'N', //更新碼
            'TA026' => 'N', //結案碼
            'TA027' => 0, //列印次數
            'TA028' => -round($serviceFee,0), //應付金額
            'TA029' => -round($tax,0), //營業稅額
            'TA030' => 0, //已付金額
            'TA031' => 'N', //產生分錄碼
            'TA032' => date('Ym'), //申報年月
            'TA033' => 'N', //凍結付款碼
            'TA034' => date('Ymd'), //單據日期
            'TA035' => 'iCarry', //確認者
            'TA036' => 0.05, //營業稅率
            'TA037' => -round($serviceFee,0), //本幣應付金額
            'TA038' => -round($tax,0), //本幣營業稅額
            'TA039' => 'N', //簽核狀態碼
            'TA040' => 0, //本幣已付金額
            'TA041' => 0, //已沖稅額
            'TA042' => 0, //傳送次數
            'TA043' => 0, //代徵營業稅
            'TA044' => 0, //本幣完稅價格
            'TA045' => $acErpVendor->MA055, //付款條件代號
            'TA052' => '', //訂金序號
            'TA056' => '', //來源
            'TA071' => '', //連絡人EMAIL
            'TA082' => '', //作廢日期
            'TA083' => '', //作廢時間
            'TA084' => '', //作廢原因
            'TA085' => '', //預留欄位
            'TA086' => '', //預留欄位
            'TA087' => '', //作廢折讓單號
            'TA088' => '', //折讓證明單號碼
            'TA089' => '', //折讓單簽回日期
            'TA090' => '', //買方開立折讓單
        ]);
    }

    private function createACErpACRTA($date6,$ACTB002No,$acErpCustomer,$serviceFee,$serviceFeeWithoutTax,$tax,$memo)
    {
        //交流建立(負數)結帳單
        ACErpACRTBDB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => 'iCarryGate',
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => date('Ymd'),
            'FLAG' => 1,
            'CREATE_TIME' => date('H:i:s'),
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'ACRI02',
            'TB001' => 'A611',  //結帳單別
            'TB002' => $ACTB002No,  //結帳單號
            'TB003' => '0001',  //序號
            'TB004' => 9,  //來源
            'TB005' => '',  //憑證單別
            'TB006' => '',  //憑證單號
            'TB008' => '',  //憑證日期
            'TB009' => -round($serviceFee,0),  //應收金額
            'TB010' => 0,  //差額
            'TB011' => $memo,  //備註
            'TB012' => 'Y',  //確認碼
            'TB013' => 5136,  //科目編號
            'TB014' => 0,  //抽成前金額
            'TB015' => 1,  //抽成率
            'TB017' => -round($serviceFeeWithoutTax,0),  //原幣未稅金額
            'TB018' => -round($tax,0),  //原幣稅額
            'TB019' => -round($serviceFeeWithoutTax,0),  //本幣未稅金額
            'TB020' => -round($tax,0),  //本幣稅額
            'TB024' => 'N',  //訂金未開發票
        ]);
        ACErpACRTADB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => 'iCarryGate',
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => date('Ymd'),
            'FLAG' => 1,
            'CREATE_TIME' => date('H:i:s'),
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI06',
            'TA001' => 'A611',  //單別
            'TA002' => $ACTB002No,  //單號
            'TA003' => $date6,  //日期
            'TA004' => $acErpCustomer->MA001,  //客戶代號
            'TA006' => '001',
            'TA008' => $acErpCustomer->MA002,
            'TA009' => 'NTD',
            'TA010' => 1,
            'TA011' => $acErpCustomer->MA037, //依客戶資料的發票聯數COPMA.MA037
            'TA012' =>$acErpCustomer->MA038, //依訂單的課稅別COPTC.TC016
            'TA013' => 'N',
            'TA014' => 1,
            'TA017' => -round($serviceFee,0),  //發票貨款
            'TA018' => -round($tax,0),  //發票稅額
            'TA019' => 'N',  //發票作廢
            'TA020' => '',  //預計收款日
            'TA021' => '',  //預計兌現日
            'TA025' => 'Y',  //確認碼
            'TA026' => 'N',  //更新碼
            'TA027' => 'N',  //結案碼
            'TA028' => 0,  //列印次數
            'TA029' => -round($serviceFee,0),  //應收金額
            'TA030' => -round($tax,0),  //應收稅額
            'TA031' => 0,  //已收金額
            'TA032' => date('Ym'),  //申報年月
            'TA034' => 0,  //其他金額
            'TA037' => 0,  //發票列印次數
            'TA038' => date('Ymd'),  //單據日期
            'TA039' => 'AC',  //確認者
            'TA040' => 0.05,  //營業稅率
            'TA041' => -round($serviceFee,0),  //本幣應收帳款
            'TA042' => -round($tax,0),  //本幣營業稅額
            'TA043' => 'N',  //簽核狀態碼
            'TA044' => 0,  //本幣已收金額
            'TA045' => 0,  //傳送次數
            'TA046' => 0,  //發票傳送次數
            'TA047' => 0,  //已沖稅額
            'TA048' => $acErpCustomer->MA083,  //付款條件代號
            'TA074' => 'Y',  //訂金不開發票
            'TA075' => 0,  //訂金不開發票金額
            'TA076' => 0,  //訂金不開發票稅額
            'TA077' => 0,  //訂金未開發票原幣未稅
            'TA078' => 0,  //訂金未開發票原幣稅額
        ]);
    }
}
