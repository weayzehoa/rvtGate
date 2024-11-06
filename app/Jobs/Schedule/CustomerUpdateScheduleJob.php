<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\iCarryDigiwinPayment as icarryCustomerDB;
use App\Models\Schedule as ScheduleDB;
use App\Models\Log as LogDB;

class CustomerUpdateScheduleJob implements ShouldQueue
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
        $erpCustomers = ErpCustomerDB::get();
        if(!empty($erpCustomers)){
            foreach($erpCustomers as $customer){
                $data['customer_no'] = rtrim($customer->MA001,' ');
                $data['create_type'] = $data['customer_name'] = strstr('ACPay-LPay',$customer->MA002) ? 'ACPay-LinePay' : $customer->MA002;
                // $data['set_deposit_ratio'] = $customer->MA095;
                $data['use_quotation'] = !empty($customer->MA030) ?? 0;
                $data['MA015'] = $customer->MA015;
                $data['MA016'] = $customer->MA016;
                $data['MA031'] = $customer->MA031;
                $data['MA037'] = $customer->MA037;
                $data['MA038'] = $customer->MA038;
                $data['MA048'] = $customer->MA048;
                $data['MA083'] = $customer->MA083;
                $icarryCustomer = icarryCustomerDB::where('customer_no',$data['customer_no'])->first();
                if(!empty($icarryCustomer)){
                    !empty($icarryCustomer->create_type) ? $data['create_type'] = $icarryCustomer->create_type : '';
                    $icarryCustomer->update($data);
                }else{
                    icarryCustomerDB::create($data);
                }
            }
            $schedule = ScheduleDB::where('code','erpCustomerUpdateToIcarry')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
        }
    }
}
