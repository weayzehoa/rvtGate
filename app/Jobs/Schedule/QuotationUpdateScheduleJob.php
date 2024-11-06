<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Quotation as QuotationDB;
use App\Models\ErpQuotation as erpQuotationDB;
use App\Models\Schedule as ScheduleDB;
use App\Models\Log as LogDB;

class QuotationUpdateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $erpQuotations = erpQuotationDB::get();
        if(!empty($erpQuotations)){
            foreach($erpQuotations as $erpQuotation){
                if(!empty(rtrim($erpQuotation->MB001,' '))){
                    $data['MB001'] = rtrim($erpQuotation->MB001,' ');
                    $data['MB002'] = rtrim($erpQuotation->MB002,' ');
                    $data['MB003'] = rtrim($erpQuotation->MB003,' ');
                    $data['MB004'] = (INT)$erpQuotation->MB004;
                    $data['MB008'] = $erpQuotation->MB008;
                    $data['MB017'] = $erpQuotation->MB017;
                    $data['MB018'] = $erpQuotation->MB018;
                    $quotation = QuotationDB::where([['MB001',$data['MB001']],['MB002',$data['MB002']],['MB017',$data['MB017']],['MB018',$data['MB018']]])->first();
                    if(!empty($quotation)){
                        $quotation->update($data);
                    }else{
                        $quotation = QuotationDB::create($data);
                    }
                }
            }
        }
        $schedule = ScheduleDB::where('code','erpQuotationUpdate')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
