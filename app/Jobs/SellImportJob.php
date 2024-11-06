<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\ErpCOPTH as ErpCOPTHDB;
use App\Models\SerialNoRecord as SerialNoRecordDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SellImport as SellImportDB;
use Carbon\Carbon;
use DB;
use Log;
use Exception;

use App\Jobs\SellImportJobOneOrderJob;

class SellImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $param;

    /**
     * 2022.08.09 討論結果修改成 一個訂單有多張銷貨單
     *
     * @return void
     */
    public function __construct($param)
    {
        empty($param['import_no']) ? $param['import_no'] = null : '';
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->param['orderNumbers'])){
            $sells = SellImportDB::where('status',0)->whereIn('order_number',$this->param['orderNumbers']);
        }elseif(!empty($this->param['order_number'])){
            $sells = SellImportDB::where([['status',0],['order_number',$this->param['order_number']]]);
        }elseif(!empty($this->param['method']) && $this->param['method'] == 'Schedule'){ //排程處理
            $now = Carbon::now()->toDateTimeString();
            $sells = SellImportDB::where([['type',$this->param['type']],['status',0],['sell_date','<=',$now]]);
        }elseif(!empty($this->param['test']) && $this->param['test'] == true){ //測試
            // $sells = SellImportDB::where('import_no',1701051908); //倉庫出貨,匯入號碼
            // $sells = $sells->where('order_number',23020280000357); //訂單號碼
            // $sells = $sells->where('gtin13','EC00241005870'); //訂單號碼
            dd('已啟動測試，請設定相關資料');
        }else{
            $sells = SellImportDB::where([['status',0],['import_no',$this->param['import_no']]]);
        }
        $sells = $sells->select([
            'order_number',
            DB::raw("GROUP_CONCAT(sell_date) as sellDates"),
        ]);
        $sells = $sells->groupBy('order_number')->orderBy('order_number','asc')->get();
        if(count($sells) > 0){
            foreach($sells as $sell){
                $temps = explode(',',$sell->sellDates);
                $temps = array_unique($temps);
                arsort($temps);
                foreach($temps as $key => $value) {
                    $sellDate = $value;
                    break;
                }
                $sellDate6 = str_replace('-','',$sellDate);
                $sellDate6 = substr($sellDate6,2);

                try {
                    //找出鼎新銷貨單的最後一筆單號
                    $chkTemp = SerialNoRecordDB::where([['type','ErpSellNo'],['serial_no','like',"$sellDate6%"]])->orderBy('serial_no','desc')->first();
                    !empty($chkTemp) ? $TG002 = $chkTemp->serial_no + 1 : $TG002 = $sellDate6.str_pad(1,5,0,STR_PAD_LEFT);
                    $chkTemp = SerialNoRecordDB::create(['type' => 'ErpSellNo','serial_no' => $TG002]);

                    //檢查鼎新銷貨單有沒有這個號碼
                    $tmp = ErpCOPTHDB::where('TH002','like',"%$sellDate6%")->select('TH002')->orderBy('TH002','desc')->first();
                    if(!empty($tmp)){
                        if($tmp->TH002 >= $TG002){
                            $TG002 = $tmp->TH002+1;
                            $chkTemp = SerialNoRecordDB::create(['type' => 'ErpSellNo','serial_no' => $TG002]);
                        }
                    }

                    //找出中繼今日最後一筆銷貨單號碼的流水號
                    $chkTemp = SerialNoRecordDB::where([['type','SellNo'],['serial_no','>=',date('ymd').'00001']])->orderBy('serial_no','desc')->first();
                    !empty($chkTemp) ? $sellNo = $chkTemp->serial_no + 1 : $sellNo = date('ymd').str_pad(1,5,0,STR_PAD_LEFT);
                    $chkTemp = SerialNoRecordDB::create(['type' => 'SellNo','serial_no' => $sellNo]);

                    //檢查中繼有沒有這個號碼
                    $tmp = SellItemSingleDB::where('sell_no','>=',date('ymd').'00001')->select('sell_no')->orderBy('sell_no','desc')->first();
                    if(!empty($tmp)){
                        if($tmp->sell_no >= $sellNo){
                            $sellNo = $tmp->sellNo+1;
                            $chkTemp = SerialNoRecordDB::create(['type' => 'ErpSellNo','serial_no' => $sellNo]);
                        }
                    }
                } catch (Exception $exception) {
                    Log::info("銷貨執行程序取銷貨單單號重複。訂單單號 ".$sell->order_number." 可能未完成銷貨。");
                    continue;
                }

                strstr(env('APP_URL'),'localhost') ? SellImportJobOneOrderJob::dispatchNow($this->param,$sell->order_number,$TG002,$sellNo) : SellImportJobOneOrderJob::dispatch($this->param,$sell->order_number,$TG002,$sellNo);
            }
        }
    }
}
