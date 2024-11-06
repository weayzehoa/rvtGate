<?php
namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Auth;

use App\Models\StockinAbnormal as StockinAbnormalDB;
use App\Models\SellAbnormal as SellAbnormalDB;
use App\Models\Mainmenu as MainmenuDB;
use App\Models\Submenu as SubmenuDB;
use App\Models\PowerAction as PowerActionDB;
use App\Models\SellImport as SellImportDB;
use App\Models\OrderImportAbnormal as OrderImportAbnormalDB;
use App\Models\OrderCancel as OrderCancelDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Models\RequisitionAbnormal as RequisitionAbnormalDB;
use App\Models\iCarryOrder as OrderDB;
use DB;

class GateIndexComposer
{
    public function compose(View $view){
        $sellReturnTable = env('DB_DATABASE').'.'.(new SellReturnDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
        $mainmenus = MainmenuDB::with('submenu')->where(['is_on' => 1])->orderBy('sort','asc')->get();
        $poweractions = PowerActionDB::all();
        $sellAbnormalCount = SellAbnormalDB::where('is_chk',0)->count();
        $stockinAbnormalCount = StockinAbnormalDB::where('is_chk',0)->count();
        $vendorSellImport = SellImportDB::where([['type','directShip'],['status', '<', 0]])->count();
        $warehouseSellImport = SellImportDB::where([['type','warehouse'],['status', '<', 0]])->count();
        $orderImportAbnormalCount = OrderImportAbnormalDB::where('is_chk',0)->count();
        $orderCancelCount = OrderCancelDB::where('is_chk',0)->count();
        $sellReturnItemCount = SellReturnItemDB::join($sellReturnTable,$sellReturnTable.'.return_no',$sellReturnItemTable.'.return_no')
        ->where([[$sellReturnTable.'.type','銷退'],[$sellReturnItemTable.'.is_chk',0],[$sellReturnItemTable.'.is_del',0]])
        ->where(function($query)use($sellReturnItemTable){ //排除運費及跨境稅
            $query->where($sellReturnItemTable.'.origin_digiwin_no','!=','901001')
            ->where($sellReturnItemTable.'.origin_digiwin_no','!=','901002');
        })->count();
        $requisitionAbnormalCount = RequisitionAbnormalDB::where('is_chk',0)->count();
        $chinaOrderCount = OrderDB::whereIn('status',[1,2])
        ->whereIn('ship_to',['中國','新加坡'])
        ->select([
            'receiver_name',
            'receiver_address',
            DB::raw("count(id) as count"),
            DB::raw("GROUP_CONCAT(order_number) as orderNumbers"),
            DB::raw("GROUP_CONCAT(id) as orderIds"),
            DB::raw("GROUP_CONCAT(id,'_',order_number) as orderData"),
        ])->groupBy('book_shipping_date','receiver_name','receiver_address')->having('count','>',1)->count();

        if(Auth::user()){
            $view->with('orderImportAbnormalCount', $orderImportAbnormalCount);
            $view->with('warehouseSellImport', $warehouseSellImport);
            $view->with('vendorSellImport', $vendorSellImport);
            $view->with('stockinAbnormalCount', $stockinAbnormalCount);
            $view->with('sellAbnormalCount', $sellAbnormalCount);
            $view->with('mainmenus', $mainmenus);
            $view->with('poweractions', $poweractions);
            $view->with('orderCancelCount', $orderCancelCount);
            $view->with('sellReturnItemCount', $sellReturnItemCount);
            $view->with('requisitionAbnormalCount', $requisitionAbnormalCount);
            $view->with('chinaOrderCount', $chinaOrderCount);
        }
    }
}
