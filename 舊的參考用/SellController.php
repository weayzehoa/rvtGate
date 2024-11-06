<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\SellImport as SellImportDB;
use App\Models\Sell as SellDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpVendor as ErpVendorDB;
use DB;
use Session;

use App\Jobs\SellImportJob;
use App\Jobs\SellFileImportJob;
use App\Jobs\CheckOrderSellAndCreateDigiwinSellJob;

use App\Traits\SellFunctionTrait;

class SellController extends Controller
{
    use SellFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M29S1';
        $appends = [];
        $compact = [];
        $sells = $this->getSellData(request(),'index');

        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
            !empty($value) ? $con[$key] = $value : '';
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }
        //找出運費及其他資料
        foreach($sells as $sell){
            if(count($sell->items) == 0){
                $sell->items = SellItemSingleDB::where('sell_no',$sell->sell_no)->get();
            }
        }

        $shippingVendors = ShippingVendorDB::orderBy('sort','asc')->get();
        $compact = array_merge($compact, ['menuCode','sells','appends','shippingVendors']);
        return view('gate.sell.index', compact($compact));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $menuCode = 'M29S1';
        $compact = [];
        $sell = SellDB::findOrFail($id);
        $compact = array_merge($compact, ['menuCode','sell']);
        return view('gate.sell.show', compact($compact));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        Session::put('error','此功能已取消。');
        return redirect()->back();
    }

    /**
     * 此功能已取消
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateOLD(Request $request, $id)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        $data = $request->data;
        sort($data);
        $sell = SellDB::with('order','items','order.items','order.items.package')->findOrFail($id);
        $order = $sell->order;
        $erpSell = ErpCOPTGDB::where('TG002',$sell->erp_sell_no)->first();
        if(empty($erpSell) || !empty($erpSell) && $erpSell->TG023 == 'N'){
            if(!empty($erpSell) && $erpSell->TG023 == 'N'){
                for($i=0;$i<count($data);$i++){
                    foreach($sell->items as $item){
                        if($data[$i]['id'] == $item->id){
                            if($data[$i]['quantity'] != $item->sell_quantity){
                                $erpSellItem = ErpCOPTHDB::where([['TH002',$erpSell->TG002],['TH003',$item->erp_sell_sno]])->first();
                                $qty = $data[$i]['quantity'];
                                $amountWithTax = $qty * $item->sell_price;
                                $amountWithoutTax = $amountWithTax / 1.05;
                                $itemTax = $amountWithTax - $amountWithoutTax;
                                ErpCOPTHDB::where([['TH002',$erpSell->TG002],['TH003',$item->erp_sell_sno]])
                                ->update([
                                    'TH008' => $data[$i]['quantity'],
                                    'TH013' => round($amountWithTax,0),
                                    'TH035' => round($amountWithoutTax,0),
                                    'TH036' => round($itemTax,0),
                                    'TH037' => round($amountWithoutTax,0),
                                    'TH038' => round($itemTax,0),
                                ]);
                                $item->update(['sell_quantity' => $data[$i]['quantity'], 'sell_date' => $request->sell_date]);
                            }
                            break;
                        }
                    }
                }
                $totalQty = $totalAmount = $totalTax = 0;
                $erpSellItems = ErpCOPTHDB::where('TH002',$erpSell->TG002)->get();
                foreach($erpSellItems as $erpSellItem){
                    $totalQty += $erpSellItem->TH008;
                    $totalAmount += $erpSellItem->TH035;
                    $totalTax += $erpSellItem->TH036;
                }
                $erpSell->update([
                    'TG003' => str_replace('-','',$request->sell_date),
                    'TG013' => round($totalAmount,0),
                    'TG025' => round($totalTax,0),
                    'TG033' => $totalQty,
                    'TG045' => $totalAmount,
                    'TG046' => $totalTax,
                ]);
                $sell->update([
                    'sell_date' => $request->sell_date,
                    'quantity' => $totalQty,
                    'amount' => round($totalAmount,0),
                    'tax' => round($totalTax,0),
                ]);
            }else{
                for($i=0;$i<count($data);$i++){
                    foreach($sell->items as $item){
                        if($data[$i]['id'] == $item->id){
                            if($data[$i]['quantity'] != $item->sell_quantity){
                                $qty = $data[$i]['quantity'];
                                $amountWithTax = $qty * $item->sell_price;
                                $amountWithoutTax = $amountWithTax / 1.05;
                                $itemTax = $amountWithTax - $amountWithoutTax;
                                $item->update(['sell_quantity' => $data[$i]['quantity'], 'sell_date' => $request->sell_date]);
                            }
                            break;
                        }
                    }
                }
                $totalQty = $totalAmount = $totalTax = 0;
                foreach($sell->items as $item){
                    $totalQty += $item->sell_quantity;
                    $totalAmount += ($item->sell_quantity * $item->sell_price) / 1.05;
                    $totalTax += (($item->sell_quantity * $item->sell_price) / 1.05 ) * 0.05;
                }
                $sell->update([
                    'sell_date' => $request->sell_date,
                    'quantity' => $totalQty,
                    'amount' => round($totalAmount,0),
                    'tax' => round($totalTax,0),
                ]);
            }
            //檢查訂單是否全部已出貨並產生鼎新銷貨單
            CheckOrderSellAndCreateDigiwinSellJob::dispatchNow($order);
            Session::put('success','該銷貨單已修改完成。');
        }else{
            Session::put('error','該銷貨單已於鼎新確認鎖定，請至鼎新解除確認才可修改。');
        }
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function import(Request $request)
    {
        // $request->request->add(['test' => true]); //測試用, 測試完關閉
        $request->request->add(['admin_id' => auth('gate')->user()->id]); //加入request
        $request->request->add(['import_no' => time()]); //加入request
        if(!empty($request->type)){
            if($request->hasFile('filename')){
                $file = $request->file('filename');
                $uploadedFileMimeType = $file->getMimeType();
                $mimes = array('application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/CDFV2','application/octet-stream');
                if(in_array($uploadedFileMimeType, $mimes)){
                    //檔案不可以直接放入Job中使用dispatch去跑
                    $result = SellFileImportJob::dispatchNow($request); //直接馬上處理
                    if($result == 'rows error'){
                        $message = '檔案內資料欄數錯誤，請檢查檔案是否符合。';
                        Session::put('error', $message);
                    }elseif($result == 'sheets error'){
                        $message = '檔案內資料的 Sheet 數超過 1 個，請檢查檔案資料是否只有 1 個 Sheet。';
                        Session::put('error', $message);
                    }elseif($result == 'no data'){
                        $message = '檔案內資料未被儲存，請檢查所有資料是否正確或已經處理完成。';
                        Session::put('warning', $message);
                    }elseif(!empty($result['import_no'])){
                        if(!empty($request->test) && $request->test == true){
                            SellImportJob::dispatchNow($result);
                        }else{
                            env('APP_ENV') == 'local' ? SellImportJob::dispatchNow($result) : SellImportJob::dispatch($result);
                        }
                        $request->type == 'warehouse' ? $type = '倉庫出庫單匯入' : $type = '廠商直寄單匯入';
                        $message = $type.'已於背端執行，請過一段時間重新整理頁面。';
                        Session::put('success', $message);
                    }
                    return redirect()->back();
                } else{
                    $message = '只接受 xls 或 xlsx 檔案格式';
                    Session::put('error', $message);
                    return redirect()->back();
                }
            }
        }
        $message = '類別錯誤';
        Session::put('error', $message);
        return redirect()->back();
    }

    public function cancel(Request $request)
    {
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();
        if(!empty($request->id)){
            $sell = SellDB::findOrFail($request->id);
            $sellItemSingles = SellItemSingleDB::join($productModelTable,$productModelTable.'.id',$sellItemSingleTable.'.product_model_id')
            ->where('sell_no',$sell->sell_no)
            ->select([
                $sellItemSingleTable.'.*',
                $productModelTable.'.gtin13',
                $productModelTable.'.digiwin_no',
            ])->get();
            if(count($sellItemSingles) > 0){
                foreach($sellItemSingles as $sellItem){
                    $sellItem->direct_shipment == 1 ? $type = 'directShip' : $type = 'warehouse';
                    $orderItem = OrderItemDB::find($sellItem->order_item_id);
                    $productModel = ProductModelDB::find($orderItem->product_model_id);
                    $sellItemImport = SellImportDB::where([['order_number',$sellItem->order_number],['type',$type]])
                    ->where(function($query)use($productModel){
                        $query->where('gtin13',$productModel->gtin13)
                        ->orWhere('digiwin_no',$productModel->digiwin_no);
                    })->first();
                    if(!empty($sellItemImport)){
                        $sellItemImport->delete();
                    }
                    $sellItem->update(['is_del' => 1]);
                }
            }
            $sell->update(['is_del' => 1]);
            Session::put('success', '選擇的銷貨單已被取消。');
        }
        return redirect()->back();
    }

    public function modifyDate(Request $request)
    {
        if(!empty($request->id)){
            $sell = SellDB::with('erpSell','stockin','items','erpSellItems')->findOrFail($request->id);
            if(!empty($sell) && $sell->sell_date != $request->sell_date){
                $chkErpSell = 0;
                $erpSell = ErpCOPTGDB::where('TG002',$sell->erp_sell_no)->first();
                $erpSell->TG023 != 'V' ? $chkErpSell++ : '';
                foreach($sell->erpSellItems as $erpItem){
                    if($erpItem->TH020 != 'V'){
                        $chkErpSell++;
                    }
                }
                if($chkErpSell == 0){
                    //廠商直寄會有入庫單需要重建
                    if(!empty($sell->stockin)){
                        $erpStockinNo = $sell->stockin->erp_stockin_no;
                        $erpStockin = ErpPURTGDB::with('items')->where('TG002',$erpStockinNo)->first();
                        $chkErpStockin = 0;
                        $erpStockin->TG013 != 'V' ? $chkErpStockin++ : '';
                        foreach($erpStockin->items as $item){
                            $item->TH030 != 'V' ? $chkErpStockin++ : '';
                        }
                        if($chkErpStockin != 0){
                            $message = "鼎新入庫單 $erpStockinNo 尚未作廢。";
                            Session::put('error', $message);
                            return redirect()->back();
                        }
                    }
                    //重新建立出貨單及銷貨單
                    $result = $this->reBuildSellandStockin($request->sell_date,$sell);
                    if($result == true){
                        $message = '出貨單日期修改完成。';
                        Session::put('success', $message);
                    }
                }else{
                    $message = "鼎新銷貨單 $sell->erp_sell_no 尚未作廢。";
                    Session::put('error', $message);
                }
            }else{
                $message = '新的出貨單日期與舊的相同';
                Session::put('error', $message);
            }
        }
        return redirect()->back();
    }

    protected function reBuildSellandStockin($newSellDate,$sell)
    {
        $creator = strtoupper(auth('gate')->user()->account);
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        //用$newSellDate找出新的流水號
        $TG014 = $newSellDate8 = str_replace('-','',$newSellDate);
        $newSellDate6 = substr($newSellDate8,2);
        $createMonth = substr($newSellDate8,0,6);
        $ErpPURTGDB = ErpPURTGDB::where('TG002','like',"$newSellDate6%")->select('TG002')->orderBy('TG002','desc')->first();
        !empty($ErpPURTGDB) ? $erpStockinNo = $ErpPURTGDB->TG002 + 1 : $erpStockinNo = $newSellDate6.str_pad(1,5,0,STR_PAD_LEFT);
        $ErpCOPTGDB = ErpCOPTGDB::where('TG002','like',"$newSellDate6%")->select('TG002')->orderBy('TG002','desc')->first();
        !empty($ErpCOPTGDB) ? $erpSellNo = $ErpCOPTGDB->TG002 + 1 : $erpSellNo = $newSellDate6.str_pad(1,5,0,STR_PAD_LEFT);
        if(!empty($sell->stockin)){
            $stockins = StockinItemSingleDB::with('product')->where('sell_no',$sell->sell_no)->get();
            $i = 0;
            foreach($stockins as $stockin){
                $erpStockinItem = ErpPURTHDB::where([['TH002',$stockin->erp_stockin_no],['TH003',$stockin->erp_stockin_sno]])->first();
                $erpStockin = ErpPURTGDB::where('TG002',$stockin->erp_stockin_no)->first();
                $product = $stockin->product;
                $vendorId = $product->vendor_id == '293' ? '350' : $product->vendor_id;
                $erpVendor = ErpVendorDB::find('A'.str_pad($vendorId,5,'0',STR_PAD_LEFT));
                $erpPURTH = [
                    'COMPANY' => 'iCarry',
                    'CREATOR' => $creator,
                    'USR_GROUP' => 'DSC',
                    'CREATE_DATE' => $createDate,
                    'FLAG' => 1,
                    'CREATE_TIME' => $createTime,
                    'CREATE_AP' => 'iCarry',
                    'CREATE_PRID' => 'PURI07',
                    'TH001' => 'A341', //單別
                    'TH002' => $erpStockinNo, //單號
                    'TH031' => 'N', //結帳碼
                    'TH014' => $TG014, //驗收日期
                    'TH017' => 0, //驗退數量
                    'TH020' => 0, //原幣扣款金額
                    'TH024' => 0, //進貨費用
                    'TH026' => 'N', //暫不付款
                    'TH027' => 'N', //逾期碼
                    'TH028' => 0, //檢驗狀態
                    'TH029' => 'N', //驗退碼
                    'TH030' => 'N', //確認碼
                    'TH032' => 'N', //更新碼
                    'TH033' => '', //備註
                    'TH034' => 0, //庫存數量
                    'TH038' => '', //確認者
                    'TH039' => '', //應付憑單別
                    'TH040' => '', //應付憑單號
                    'TH041' => '', //應付憑單序號
                    'TH042' => '', //專案代號
                    'TH043' => 'N', //產生分錄碼
                    'TH044' => 'N', //沖自籌額碼
                    'TH050' => 'N', //簽核狀態碼
                    'TH051' => 0, //原幣沖自籌額
                    'TH052' => 0, //本幣沖自籌額
                    'TH054' => 0, //抽樣數量
                    'TH055' => 0, //不良數量
                    'TH058' => 0, //缺點數
                    'TH059' => 0, //進貨包裝數量
                    'TH060' => 0, //驗收包裝數量
                    'TH061' => 0, //驗退包裝數量
                    'TH064' => 0, //產品序號數量
                    'EF_ERPMA001' => '',
                    'EF_ERPMA002' => '',
                    'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                    'TH004' => $erpStockinItem->TH004, //品號
                    'TH005' => $erpStockinItem->TH005, //品名
                    'TH006' => $erpStockinItem->TH006, //規格
                    'TH007' => $erpStockinItem->TH007, //進貨數量
                    'TH008' => $erpStockinItem->TH008, //單位
                    'TH009' => $erpStockinItem->TH009, //庫別
                    'TH011' => $erpStockinItem->TH011, //採購單別
                    'TH012' => $erpStockinItem->TH012, //採購單號
                    'TH013' => $erpStockinItem->TH013, //採購序號
                    'TH015' => $erpStockinItem->TH015, //驗收數量
                    'TH016' => $erpStockinItem->TH016, //計價數量
                    'TH018' => $erpStockinItem->TH018, //原幣單位進價
                    'TH019' => $erpStockinItem->TH019, //原幣進貨金額
                    'TH045' => $erpStockinItem->TH045, //原幣未稅金額
                    'TH046' => $erpStockinItem->TH046, //原幣稅額
                    'TH047' => $erpStockinItem->TH047, //本幣未稅金額
                    'TH048' => $erpStockinItem->TH048, //本幣稅額
                    'TH049' => $erpStockinItem->TH049, //計價單位
                ];
                // dd($erpPURTH);
                $erpPURTH = ErpPURTHDB::create($erpPURTH);
                $stockin->update([
                    'erp_stockin_no' => $erpStockinNo,
                    'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                    'stockin_date' => $newSellDate,
                ]);
                $i++;
            }
            //建立入庫單頭
            $erpPURTG = [
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'FLAG' => 1,
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'PURI07',
                'TG001' => 'A341', //單別
                'TG002' => $erpStockinNo, //單號
                'TG003' => $TG014, //進貨日期
                'TG004' => '001', //廠別
                'TG005' => 'A'.str_pad($vendorId,5,'0',STR_PAD_LEFT), //供應廠商
                'TG007' => 'NTD', //幣別
                'TG008' => 1, //匯率
                'TG009' => $erpStockin->TG009, //發票聯數
                'TG010' => $erpStockin->TG010, //課稅別
                'TG012' => 0, //列印次數
                'TG013' => 'N', //確認碼
                'TG014' => $TG014, //單據日期
                'TG015' => 'N', //更新碼
                'TG016' => '', //備註
                'TG017' => $erpStockin->TG017, //進貨金額
                'TG018' => 0, //扣款金額
                'TG019' => $erpStockin->TG019, //原幣稅額
                'TG020' => 0, //進貨費用
                'TG021' => $erpStockin->TG021, //廠商全名
                'TG022' => $erpStockin->TG022, //統一編號
                'TG023' => 1, //扣抵區分
                'TG024' => 'N', //菸酒註記
                'TG025' => 0, //件數
                'TG026' => $erpStockin->TG026, //數量合計
                'TG027' => '', //發票日期
                'TG028' => $erpStockin->TG028, //原幣貨款金額
                'TG029' => $createMonth, //申報年月
                'TG030' => 0.05, //營業稅率
                'TG031' => $erpStockin->TG031, //本幣貨款金額
                'TG032' => $erpStockin->TG032, //本幣稅額
                'TG033' => 'N', //簽核狀態碼
                'TG038' => 0, //沖抵金額
                'TG039' => 0, //沖抵稅額
                'TG040' => 0, //預留欄位
                'TG041' => 0, //本幣沖自籌額
                'TG045' => 0, //預留欄位
                'TG046' => 0, //原幣沖自籌額
                'TG047' => 0, //包裝數量合計
                'TG048' => 0, //傳送次數
                'TG049' => $erpStockin->TG049, //付款條件代號
                'EF_ERPMA001' => '',
                'EF_ERPMA002' => '',
            ];
            ErpPURTGDB::create($erpPURTG);
        }
        //重建銷貨單
        $i=0;
        $oldErpSell = $sell->erpSell;
        foreach($sell->items as $item){
            $erpItem = ErpCOPTHDB::where([['TH002',$item->erp_sell_no],['TH003',$item->erp_sell_sno]])->first();
            $erpCOPTH = [
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'MODIFIER' => '',
                'MODI_DATE' => '',
                'FLAG' => '1',
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'COPI08',
                'MODI_TIME' => '',
                'MODI_AP' => '',
                'MODI_PRID' => '',
                'EF_ERPMA001' => '',
                'EF_ERPMA002' => '',
                'TH001' => $erpItem->TH001, //單別
                'TH002' => $erpSellNo, //單號
                'TH010' => 0, //庫存數量
                'TH011' => '', //小單位
                'TH017' => '', //批號
                'TH019' => '', //客戶品號
                'TH020' => 'N', //確認碼
                'TH021' => 'N', //更新碼
                'TH022' => '', //保留欄位
                'TH023' => '', //保留欄位
                'TH024' => 0, //贈/備品量
                'TH026' => 'N', //結帳碼
                'TH027' => '', //結帳單別
                'TH028' => '', //結帳單號
                'TH029' => '', //結帳序號
                'TH030' => '', //專案代號
                'TH031' => 1, //類型
                'TH032' => '', //暫出單別
                'TH033' => '', //暫出單號
                'TH034' => '', //暫出序號
                'TH039' => '', //預留欄位
                'TH040' => '', //預留欄位
                'TH041' => '', //預留欄位
                // 'TH042' => '', //包裝數量
                // 'TH043' => '', //贈/備品包裝量
                'TH044' => '', //包裝單位
                'TH045' => '', //發票號碼
                'TH046' => '', //生產加工包裝資訊
                // 'TH057' => '', //產品序號數量
                'TH074' => '', //CRM來源
                'TH075' => '', //CRM單別
                'TH076' => '', //CRM單號
                'TH077' => '', //CRM序號
                'TH099' => 1, //品號稅別
                'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                'TH004' => $erpItem->TH004, //品號
                'TH005' => $erpItem->TH005, //品名
                'TH006' => $erpItem->TH006, //規格
                'TH007' => $erpItem->TH007, //庫別
                'TH008' => $erpItem->TH008, //數量
                'TH009' => $erpItem->TH009, //單位
                'TH012' => $erpItem->TH012, //單價
                'TH013' => $erpItem->TH013, //金額
                'TH014' => $erpItem->TH014, //訂單單別
                'TH015' => $erpItem->TH015, //訂單單號
                'TH016' => $erpItem->TH016, //訂單序號
                'TH018' => $erpItem->TH018, //備註
                'TH025' => $erpItem->TH025, //折扣率
                'TH035' => $erpItem->TH035, //原幣未稅金額
                'TH036' => $erpItem->TH036, //原幣稅額
                'TH037' => $erpItem->TH037, //本幣未稅金額
                'TH038' => $erpItem->TH038, //本幣稅額
                'TH047' => $erpItem->TH047, //網購訂單編號
            ];
            // dd($erpCOPTH);
            ErpCOPTHDB::create($erpCOPTH);
            $item->update([
                'erp_sell_no' => $erpSellNo,
                'erp_sell_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                'sell_date' => $newSellDate,
            ]);
            $i++;
        }
        //建立鼎新銷貨單單頭
        $erpSell = [
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'MODIFIER' => '',
            'MODI_DATE' => '',
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI08',
            'MODI_TIME' => '',
            'MODI_AP' => '',
            'MODI_PRID' => '',
            'EF_ERPMA001' => '',
            'EF_ERPMA002' => '',
            'TG001' => $oldErpSell->TG001, //單別
            'TG002' => $erpSellNo, //單號
            'TG003' => $newSellDate8, //銷貨日期
            'TG004' => $oldErpSell->TG004, //客戶代號
            'TG005' => $oldErpSell->TG005, //部門
            'TG006' => $oldErpSell->TG006, //業務員
            'TG007' => $oldErpSell->TG007, //客戶全名
            'TG008' => $oldErpSell->TG008, //送貨地址一
            'TG009' => $oldErpSell->TG009, //送貨地址二
            'TG010' => '001', //出貨廠別
            'TG011' => 'NTD', //幣別
            'TG012' => 1, //匯率
            'TG013' => $oldErpSell->TG013, //原幣銷貨金額
            'TG014' => '', //發票號碼
            'TG015' => $oldErpSell->TG015, //統一編號
            'TG016' => $oldErpSell->TG016, //發票聯數
            'TG017' => $oldErpSell->TG017, //課稅別
            'TG018' => '', //發票地址一
            'TG019' => '', //發票地址二
            'TG020' => '', //備註
            'TG021' => '', //發票日期
            'TG022' => 0, //列印次數
            'TG023' => 'N', //確認碼
            'TG024' => 'N', //更新碼
            'TG025' => $oldErpSell->TG025, //原幣銷貨稅額
            'TG026' => '', //收款業務員
            'TG027' => '', //備註一
            'TG028' => '', //備註二
            'TG029' => '', //備註三
            'TG030' => 'N', //發票作廢
            'TG031' => 1, //通關方式
            'TG032' => 0, //件數
            'TG033' => $oldErpSell->TG033, //總數量
            'TG034' => 'N', //現銷
            'TG035' => '', //員工代號
            'TG036' => 'N', //產生分錄碼(收入)
            'TG037' => 'N', //產生分錄碼(成本)
            'TG038' => $createMonth, //申報年月
            'TG039' => '', //L/C_NO
            'TG040' => '', //INVOICE_NO
            'TG041' => 0, //發票列印次數
            'TG042' => $newSellDate8, //單據日期
            'TG043' => '', //確認者
            'TG044' => 0.05, //營業稅率
            'TG045' => $oldErpSell->TG045, //本幣銷貨金額
            'TG046' => $oldErpSell->TG046, //本幣銷貨稅額
            'TG047' => 'N', //簽核狀態碼
            'TG048' => '', //報單號碼
            'TG049' => $oldErpSell->TG049, //送貨客戶全名
            'TG050' => $oldErpSell->TG050, //連絡人
            'TG051' => $oldErpSell->TG051, //TEL_NO
            'TG052' => $oldErpSell->TG052, //FAX_NO
            'TG053' => '', //出貨通知單別
            'TG054' => '', //出貨通知單號
            // 'TG055' => '', //預留欄位
            'TG056' => 1, //交易條件
            'TG057' => 0, //總包裝數量
            'TG058' => 0, //傳送次數
            'TG059' => '', //訂單單別
            'TG060' => '', //訂單單號
            'TG061' => '', //預收待抵單別
            'TG062' => '', //預收待抵單號
            'TG063' => 0, //沖抵金額
            'TG064' => 0, //沖抵稅額
            'TG065' => $oldErpSell->TG065, //付款條件代號
            'TG066' => $oldErpSell->TG066, //收貨人
            'TG067' => '', //指定日期
            'TG068' => '', //配送時段
            'TG069' => '', //貨運別
            'TG070' => 0, //代收貨款
            'TG071' => 0, //運費
            'TG072' => 'N', //產生貨運文字檔
            'TG073' => 0, //客戶描述
            'TG074' => '', //作廢日期
            'TG075' => '', //作廢時間
            'TG076' => '', //專案作廢核准文號
            'TG077' => '', //作廢原因
            'TG078' => '', //發票開立時間
            'TG079' => '', //載具顯碼ID
            'TG080' => '', //載具類別號碼
            'TG081' => '', //載具隱碼ID
            'TG082' => '', //發票捐贈對象
            'TG083' => '', //發票防偽隨機碼
            'TG106' => '', //來源
            'TG129' => $oldErpSell->TG129, //行動電話
            'TG130' => '', //信用卡末四碼
            'TG131' => '', //連絡人EMAIL
            'TG132' => '', //買受人適用零稅率註記
            'TG200' => '', //載具行動電話
            'TG134' => '', //貨運單號
            'TG091' => 0, //原幣應稅銷售額
            'TG092' => 0, //原幣免稅銷售額
            'TG093' => 0, //本幣應稅銷售額
            'TG094' => 0, //本幣免稅銷售額
        ];
        ErpCOPTGDB::create($erpSell);
        $sell->update([
            'erp_sell_no' => $erpSellNo,
            'sell_date' => $newSellDate,
        ]);
        return true;
    }
}
