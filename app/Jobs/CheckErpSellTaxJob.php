<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\Sell as SellDB;

class CheckErpSellTaxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $erpSell;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($erpSell)
    {
        $this->erpSell = $erpSell;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $erpSell = $this->erpSell;
        $erpSellType = $erpSell->TG001;
        $erpSellNo = $erpSell->TG002;
        $priceWithoutTax = $erpSell->TG013; //單頭未稅總金額
        $tax = $erpSell->TG025; //單頭實際總稅額
        $erpItems = ErpCOPTHDB::where([['TH001',$erpSellType],['TH002',$erpSellNo]])->orderBy('TH036','desc')->get();
        if(count($erpItems) > 0){
            foreach($erpItems as $erpItem){
                $erpOrderType = $erpItem->TH014;
                $erpOrderNo = $erpItem->TH015;
            }
            $erpOrder = ErpOrderDB::where([['TC001',$erpOrderType],['TC002',$erpOrderNo]])->first();
            if($erpOrder->TC016 != 3){
                $erpItemPriceWithoutTax = $erpTotalPriceWithoutTax = $erpItemTotalPrice = $erpItemTax = 0;
                //單身總金額, 總稅額
                foreach($erpItems as $erpItem){
                    $erpItemTotalPrice += $erpItem->TH013;
                    $erpItemPriceWithoutTax += $erpItem->TH035;
                    $erpItemTax += $erpItem->TH036;
                }

                if($erpOrder->TC016 == 2){
                    $tax = round(round($erpItemTotalPrice,0 ) * 0.05, 0); //單頭稅額
                    $priceWithoutTax = round($erpItemTotalPrice,0 );
                }else{
                    $tax = round(round($erpItemTotalPrice / 1.05,0 ) * 0.05, 0); //單頭稅額
                    $priceWithoutTax = $erpItemTotalPrice - $tax; //單頭未稅金額
                }

                if($priceWithoutTax != $erpSell->TG013 || $tax != $erpSell->TG025){
                    //更新單頭 未稅金額 與 稅金
                    ErpCOPTGDB::where([['TG001',$erpSellType],['TG002',$erpSellNo]])->update([
                        'TG013' => $priceWithoutTax,
                        'TG025' => $tax,
                        'TG045' => $priceWithoutTax,
                        'TG046' => $tax,
                    ]);
                    //更新中繼資料
                    SellDB::where([['erp_sell_no',$erpSellNo],['erp_order_number',$erpSell->TG060]])
                    ->update([
                        'amount' => round(($priceWithoutTax + $tax),0),
                        'tax' => $tax,
                    ]);
                }

                // 單頭未稅金額 - 單身總未稅金額 AS 未稅金額差
                // 如果有差額 逐筆加減1 分攤
                $priceWithoutTaxDiff = (INT)($priceWithoutTax - $erpItemPriceWithoutTax);
                if($priceWithoutTaxDiff > 0){
                    foreach($erpItems as $erpItem){
                        $newPrice = $erpItem->TH035 + 1; //逐筆加1
                        ErpCOPTHDB::where([['TH001',$erpItem->TH001],['TH002',$erpItem->TH002],['TH003',$erpItem->TH003]])
                        ->update(['TH035' => $newPrice, 'TH037' => $newPrice]);
                        $priceWithoutTaxDiff--;
                        if($priceWithoutTaxDiff==0){
                            break;
                        }
                    }
                }elseif($priceWithoutTaxDiff < 0){
                    foreach($erpItems as $erpItem){
                        $newPrice = $erpItem->TH035 - 1; //逐筆減1
                        ErpCOPTHDB::where([['TH001',$erpItem->TH001],['TH002',$erpItem->TH002],['TH003',$erpItem->TH003]])
                        ->update(['TH035' => $newPrice, 'TH037' => $newPrice]);
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
                        $newTax = $erpItem->TH036 + 1; //逐筆加1
                        ErpCOPTHDB::where([['TH001',$erpItem->TH001],['TH002',$erpItem->TH002],['TH003',$erpItem->TH003]])
                        ->update(['TH036' => $newTax, 'TH038' => $newTax]);
                        $taxDiff--;
                        if($taxDiff == 0){
                            break;
                        }
                    }
                }elseif($taxDiff < 0){
                    foreach($erpItems as $erpItem){
                        $newTax = $erpItem->TH036 - 1; //逐筆減1
                        ErpCOPTHDB::where([['TH001',$erpItem->TH001],['TH002',$erpItem->TH002],['TH003',$erpItem->TH003]])
                        ->update(['TH036' => $newTax, 'TH038' => $newTax]);
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
