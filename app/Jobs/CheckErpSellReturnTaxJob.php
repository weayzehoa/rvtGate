<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\ErpCOPTI as ErpCOPTIDB;
use App\Models\ErpCOPTJ as ErpCOPTJDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\SellReturn as SellReturnDB;

class CheckErpSellReturnTaxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected$erpSellReturn;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($erpSellReturn)
    {
        $this->erpSellReturn = $erpSellReturn;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $erpSellReturn = $this->erpSellReturn;
        $erpSellReturnType = $erpSellReturn->TI001;
        $erpSellReturnNo =$erpSellReturn->TI002;
        $priceWithoutTax =$erpSellReturn->TI010; //單頭未稅總金額
        $tax =$erpSellReturn->TI011; //單頭實際總稅額
        $taxType = $erpSellReturn->TI013; //稅別
        $erpItems = ErpCOPTJDB::where([['TJ001',$erpSellReturnType],['TJ002',$erpSellReturnNo]])->orderBy('TJ036','desc')->get();
        if(count($erpItems) > 0){
            if($taxType != 3){
                $erpItemPriceWithoutTax = $erpTotalPriceWithoutTax = $erpItemTotalPrice = $erpItemTax = 0;
                //單身總金額, 總稅額
                foreach($erpItems as $erpItem){
                    $erpItemTotalPrice += $erpItem->TJ012;
                    $erpItemPriceWithoutTax += $erpItem->TJ031;
                    $erpItemTax += $erpItem->TJ032;
                }

                $tax = round(round($erpItemTotalPrice / 1.05,0 ) * 0.05, 0); //單頭稅額
                $priceWithoutTax = $erpItemTotalPrice - $tax; //單頭未稅金額

                //單頭總金額 - 單身總金額 不相同時需校正
                if($erpItemTotalPrice != ($erpSellReturn->TI010 + $erpSellReturn->TI011)){
                    //更新單頭 未稅金額 與 稅金
                    ErpCOPTIDB::where([['TI001',$erpSellReturnType],['TI002',$erpSellReturnNo]])
                    ->update([
                        'TI010' => $priceWithoutTax,
                        'TI011' => $tax,
                        'TI037' => $priceWithoutTax,
                        'TI038' => $tax,
                    ]);
                    //更新中繼資料
                    SellReturnDB::where([['type','銷退'],['erp_return_type',$erpSellReturnType],['erp_return_no',$erpSellReturnNo]])
                    ->update([
                        'price' => $priceWithoutTax,
                        'tax' => $tax,
                    ]);
                }

                // 單頭未稅金額 - 單身總未稅金額 AS 未稅金額差
                // 如果有差額 逐筆加減1 分攤
                $priceWithoutTaxDiff = (INT)($priceWithoutTax - $erpItemPriceWithoutTax);
                if($priceWithoutTaxDiff > 0){
                    foreach($erpItems as $erpItem){
                        $newPrice = $erpItem->TJ031 + 1; //逐筆加1
                        ErpCOPTJDB::where([['TJ001',$erpItem->TJ001],['TJ002',$erpItem->TJ002],['TJ003',$erpItem->TJ003]])
                        ->update(['TJ031' => $newPrice, 'TJ033' => $newPrice]);
                        $priceWithoutTaxDiff--;
                        if($priceWithoutTaxDiff==0){
                            break;
                        }
                    }
                }elseif($priceWithoutTaxDiff < 0){
                    foreach($erpItems as $erpItem){
                        $newPrice = $erpItem->TJ031 - 1; //逐筆減1
                        ErpCOPTJDB::where([['TJ001',$erpItem->TJ001],['TJ002',$erpItem->TJ002],['TJ003',$erpItem->TJ003]])
                        ->update(['TJ031' => $newPrice, 'TJ033' => $newPrice]);
                        $priceWithoutTaxDiff++;
                        if($priceWithoutTaxDiff==0){
                            break;
                        }
                    }
                }

                // 單頭稅額 - 單身總稅額 AS 稅差
                //如果有稅差的時候, 逐筆加減1 分攤稅差.
                $taxDiff = (INT)($tax - $erpItemTax);
                if($taxDiff > 0){
                    foreach($erpItems as $erpItem){
                        $newTax = $erpItem->TJ032 + 1; //逐筆加1
                        ErpCOPTJDB::where([['TJ001',$erpItem->TJ001],['TJ002',$erpItem->TJ002],['TJ003',$erpItem->TJ003]])
                        ->update(['TJ032' => $newTax, 'TJ034' => $newTax]);
                        $taxDiff--;
                        if($taxDiff == 0){
                            break;
                        }
                    }
                }elseif($taxDiff < 0){
                    foreach($erpItems as $erpItem){
                        $newTax = $erpItem->TJ032 - 1; //逐筆減1
                        ErpCOPTJDB::where([['TJ001',$erpItem->TJ001],['TJ002',$erpItem->TJ002],['TJ003',$erpItem->TJ003]])
                        ->update(['TJ032' => $newTax, 'TJ034' => $newTax]);
                        $taxDiff++;
                        if($taxDiff == 0){
                            break;
                        }
                    }
                }
            }
        }
    }
}
