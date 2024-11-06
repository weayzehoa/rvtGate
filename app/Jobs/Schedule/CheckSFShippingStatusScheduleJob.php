<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\SfShipping as SFShippingDB;
use App\Models\iCarryVendor as VendorDB;

use App\Traits\SFApiFunctionTrait;

class CheckSFShippingStatusScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,SFApiFunctionTrait;

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
        $sfShippings = SFShippingDB::whereIn('status',[6,9])->orderBy('status','asc')->get();
        if(count($sfShippings) > 0){
            $sfShippings = $sfShippings->groupBy('phone')->all();
            foreach($sfShippings as $phone => $shippingNos){
                $phoneNo = mb_substr(mb_substr($phone,0,20), -4); //寄件方
                $phoneNo = 3161; //收件方
                $sNos = [];
                foreach($shippingNos as $nos){
                    $sNos[] = $nos->sf_express_no;
                }
                $array = array_chunk($sNos,10);
                for($i=0;$i<count($array);$i++){
                    $result = $this->chkSFShippingNumber($array[$i],$phoneNo);
                    if(!empty($result) && count($result) > 0){
                        for($j=0;$j<count($result);$j++){
                            $sfShippingNo = $result[$j]['sfWaybillNo'];
                            if($result[$j]['code'] == 0 && $result[$j]['msg'] == 'success'){
                                $sfShipping = SFShippingDB::where('sf_express_no',$sfShippingNo)->first();
                                $items = $result[$j]['trackDetailItems'];
                                if(count($items) > 0){
                                    $sfShipping->update(['status' => 6]);
                                    for($x=0;$x<count($items);$x++){
                                        $x==0 ? $sfShipping->update(['trace_address' => $items[$x]['trackAddr']]) : ''; //最後一筆
                                        if($items[$x]['opCode'] == 80){
                                            $sfShipping->update(['status' => 1, 'stockin_date' => explode(' ',$items[$x]['localTm'])[0]]);
                                        }
                                    }
                                }else{
                                    $sfShipping->update(['trace_address' => '順豐尚未取件。']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
