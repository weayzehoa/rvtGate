<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\iCarryDigiwinProductCategory as DigiwinProductCategoryDB;
use App\Models\ErpINVMA as ErpINVMADB;

class ErpProductCategoryUpdateToIcarryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    //讓job不會timeout, 此設定需用 queue:work 才會優先於預設
    public $timeout = 0;

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
        $data = [];
        $erpINVMA = ErpINVMADB::where('MA001',2)->get();
        if(count($erpINVMA) > 0){
            foreach($erpINVMA as $invma){
                $code = rtrim($invma->MA002,' ');
                $name = $invma->MA003;
                $chk = DigiwinProductCategoryDB::where('code',$code)->first();
                if(!empty($chk)){
                    $chk->update([
                        'name' => $name,
                    ]);
                }else{
                    DigiwinProductCategoryDB::create([
                        'name' => $name,
                        'code' => $code,
                    ]);
                }
            }
        }
    }
}
