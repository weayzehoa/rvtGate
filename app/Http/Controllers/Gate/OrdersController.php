<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\iCarryUser as UserDB;
use App\Models\iCarryUserPoint as UserPointDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarryOrderLog as OrderLogDB;
use App\Models\iCarryOrderAsiamiles as AsiamilesDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryShippingMethod as ShippingMethodDB;
use App\Models\iCarryDigiwinPayment as DigiwinCustomerDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\OrderShipping as OrderShippingDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;
use App\Models\ErpProduct as ErpProductDB;
use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\SyncedOrder as SyncedOrderDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarrySiteSetup as SiteSetupDB;
use App\Models\iCarryReceiverBaseSet as ReceiverBaseSetDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;
use App\Models\AdminKeypassLog as AdminKeypassLogDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\VendorShipping as VendorShippingDB;
use App\Models\VendorShippingItem as VendorShippingItemDB;
use App\Models\iCarryPay2go as Pay2GoDB;
use App\Models\SellImport as SellImportDB;
use App\Models\SystemSetting as SystemSettingDB;
use DB;
use Carbon\Carbon;
use Session;
use Hash;
use Log;

use App\Jobs\SellImportJob;
use App\Jobs\AdminInvoiceJob;
use App\Jobs\SellReturnJob;
use App\Jobs\SellAllowanceJob;
use App\Jobs\OrderRefundMailJob;
use App\Jobs\OrderCancelJob;
use App\Jobs\OrderImportJob;
use App\Jobs\OrderFileImportJob;
use App\Jobs\OrderShippingFileImportJob;
use App\Jobs\AdminExportJob;
use App\Jobs\OrderExportDigiWinJob;
use App\Jobs\CheckOrderStatusJob;
use App\Jobs\OrderMemoFileImportJob;
use App\Jobs\OrderShippingMemoFileImportJob;
use App\Jobs\addNotPurchaseMarkJob;
use App\Jobs\OrderPickupShippingVendorJob as OrderPickupShippingVendor;
use App\Jobs\iCarryOrderSynchronizeToDigiwinJob as OrderSyncToDigiwin;
use App\Jobs\iCarryOrderToPurchaseOrderJob as OrderToPurchaseOrder;
use App\Jobs\RemoveSyncedOrderItemPurchaseDataJob as RemoveSyncedOrderItemPurchaseData;
use App\Jobs\AcOrderProcessJob;

use App\Traits\OrderFunctionTrait;
use App\Traits\VendorArrivalDateTrait;
use App\Traits\ThirdPartyFunctionTrait;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AcOrderSerialNoImport;
use App\Exports\Sheets\AcOrderInvoiceExport;

class OrdersController extends Controller
{
    use OrderFunctionTrait,VendorArrivalDateTrait,ThirdPartyFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ctime = microtime(true); //紀錄開始時間
        $menuCode = 'M27S1';
        $appends =  $compact = $orders = [];
        //可開發票的訂單
        $allowDigiwinPaymentIds = DigiwinPaymentDB::where('is_invoice',1)->where('customer_no','not like','%AC%')->get()->pluck('customer_no')->all();
        $add = []; //填入特定代號可以暫時性開啟功能
        $allowDigiwinPaymentIds = array_merge($allowDigiwinPaymentIds,$add);
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                //轉換參數
                if(isset($synced_date_not_fill)) {
                    strtolower($synced_date_not_fill) == 'on' ? $synced_date_not_fill == 1 : '';
                }
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
            !empty($value) ? $con[$key] = $value : '';
        }

        if (!isset($status)) {
            $status = '1,2,3,4';
            $compact = array_merge($compact, ['status']);
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        //計算及item資料分拆
        $orders = $this->orderItemSplit($this->getOrderData(request(),'index'));
        // dd($orders);
        $NGBookShippingDate = $NGProduct = $NGOrder = [];
        foreach($orders as $order){
            $order->is_ticket_order = $chkTickets = 0;
            //php 7.4 Trying to access array offset on value of type null 改用下方方式
            !empty($order->syncDate) ? $order->syncDate = str_replace('-','/',substr($order->syncDate['created_at'],0,10)) : $order->syncDate = null;
            if(strtotime($order->book_shipping_date) < strtotime($order->vendor_arrival_date)){
                $NGBookShippingDate[] = $order->order_number;
            }
            if(count($order->syncedErrors) > 0){
                $NGOrder[] = $order->order_number;
            }
            foreach($order->items as $item){
                if($item->product_category_id == 17){
                    $chkTickets++;
                }
                $total = 0;
                if(!empty($item->split) && count($item->split) > 0){
                    foreach($item->split as $split){
                        $total += $split['quantity'];
                    }
                }
                $item->splitCount = $total;
                if(strstr($item->sku,'BOM') && $item->is_del == 0){
                    if(!empty($item->package_data)){
                        $packageData = json_decode(str_replace('	','',$item->package_data));
                        if(!empty($packageData) && count($packageData) > 0){
                            $chk = 0;
                            foreach($packageData as $package){
                                if($item->sku == $package->bom){
                                    $chk++;
                                    break;
                                }
                            }
                            if($chk == 0){
                                $NGProduct[] = $item->digiwin_no;
                            }
                        }else{
                            $NGProduct[] = $item->digiwin_no;
                        }
                    }else{
                        $NGProduct[] = $item->digiwin_no;
                    }
                }else{
                    if(count($item->sells) > 0){
                        foreach($item->sells as $sell){
                            $item->sell_quantity += $sell->sell_quantity;
                        }
                    }
                }
                $item->vendor_shipping_no = null;
                if(!empty($item->syncedOrderItem) && !empty($item->syncedOrderItem->purchase_no) && $item->syncedOrderItem->is_del == 0){
                    $vendorShippingTable = env('DB_DATABASE').'.'.(new VendorShippingDB)->getTable();
                    $vendorShippingItemTable = env('DB_DATABASE').'.'.(new VendorShippingItemDB)->getTable();
                    //找商家出貨單號
                    $productModelId = $item->product_model_id;
                    if(!empty($item->origin_digiwin_no)){ //轉換貨號
                        $productModel = ProductModelDB::where('digiwin_no',$item->origin_digiwin_no)->where('is_del',0)->first();
                        !empty($productModel) ? $productModelId = $productModel->id : '';
                    }
                    $tmp = VendorShippingItemDB::join($vendorShippingTable,$vendorShippingTable.'.shipping_no',$vendorShippingItemTable.'.shipping_no')
                    ->where($vendorShippingItemTable.'.product_model_id',$productModelId)
                    ->where($vendorShippingItemTable.'.order_numbers','like',"%$order->order_number%")
                    ->where($vendorShippingTable.'.status','!=',-1)
                    ->where($vendorShippingItemTable.'.is_del',0)
                    ->where($vendorShippingItemTable.'.direct_shipment',$item->direct_shipment);
                    $tmp = $tmp->where($vendorShippingItemTable.'.vendor_arrival_date',$item->syncedOrderItem->vendor_arrival_date)
                    ->where($vendorShippingItemTable.'.purchase_no',$item->syncedOrderItem->purchase_no);
                    $tmp = $tmp->orderBy($vendorShippingItemTable.'.id','desc')->first();
                    !empty($tmp) ? $item->vendor_shipping_no = $tmp->shipping_no : '';
                }
            }
            if($chkTickets == count($order->items)){
                $order->is_ticket_order = 1;
            }
            //檢查acOrder是否有全部處理
            $order->acOrderChk = 0;
            if(!empty($order->acOrder)){
                $order->acOrder->is_sync == 0 ? $order->acOrderChk++ : '';
                $order->acOrder->is_invoice == 0 ? $order->acOrderChk++ : '';
                $order->acOrder->purchase_sync == 0 ? $order->acOrderChk++ : '';
                $order->acOrder->is_sell == 0 ? $order->acOrderChk++ : '';
                empty($order->acOrder->purchase_id) ? $order->acOrderChk++ : '';
                $order->acOrder->is_stockin == 0 ? $order->acOrderChk++ : '';
            }
        }
        //其他資料
        $imports = ['宜睿匯入','MOMO匯入','鼎新訂單匯入','批次修改管理員備註','物流單號匯入','訂單在途存貨'];
        $shippingVendors = ShippingVendorDB::orderBy('sort', 'asc')->get();
        $digiwinCustomers = DigiwinCustomerDB::where(function($query){
            $query->where('customer_no','<=','999')
            ->orWhereIn('customer_no',['065001','065002','065003','065004','065005','065006','065007','065008','065009','065010','065011'])
            ->orWhere('customer_no','like','AC%');
        })->select(['customer_no','customer_name'])->get();
        $bookShippingDates = OrderDB::whereIn('status',[1,2])
            ->whereNotNull('book_shipping_date')
            // ->where('book_shipping_date','!=','0000-00-00') //mysql8 date format不能放0000-00-00比對
            ->where('shipping_method','>',0)
            // ->whereBetween('book_shipping_date',[Carbon::now(),Carbon::now()->addDays(45)])
            ->select([
                DB::raw("DATE_FORMAT(book_shipping_date,'%Y-%m-%d') as book_shipping_date"),
                DB::raw("SUM(CASE WHEN book_shipping_date is not null THEN 1 ELSE 0 END) as count"),
            ])->distinct()->groupBy('book_shipping_date')->orderBy('book_shipping_date','asc')->get();
        $pickupDates = OrderDB::whereIn('status',[1,2])
            ->whereNotNull('receiver_key_time')
            // ->where('receiver_key_time','!=','0000-00-00')  //mysql8 date format不能放0000-00-00比對
            ->where('shipping_method','>',0)
            // ->whereBetween('book_shipping_date',[Carbon::now(),Carbon::now()->addDays(45)])
            ->select([
                DB::raw("DATE_FORMAT(receiver_key_time,'%Y-%m-%d') as receiver_key_time"),
                DB::raw("DATE_FORMAT(receiver_key_time,'%Y-%m-%d') as receiver_time"),
                DB::raw("SUM(CASE WHEN receiver_key_time is not null THEN 1 ELSE 0 END) as count"),
            ])->distinct()->groupBy('receiver_time')->orderBy('receiver_time','asc')->get();
        $sources = DigiwinPaymentDB::where(function($query){
            $query->where('customer_no','<=','999')
            ->orWhereIn('customer_no',['065001','065002','065003','065004','065005','065006','065007','065008','065009','065010','065011'])
            ->orWhere('customer_no','like',"AC%");
        })->select([
            'customer_no as source',
            'customer_name as name'
        ])->orderBy('source','asc')->get();
        $compact = array_merge($compact, ['menuCode','orders','appends','digiwinCustomers','shippingVendors','bookShippingDates','pickupDates','NGProduct','NGOrder','imports','NGBookShippingDate','allowDigiwinPaymentIds','sources']);
        return view('gate.orders.index', compact($compact));
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
        $menuCode = 'M27S1';
        $compact = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $grossWeightRate = SystemSettingDB::first()->gross_weight_rate;
        $myId['id'] = $id;
        $order = $this->orderItemSplit($this->getOrderData($myId,'show'),'single');
        $order->chkDirectShipment = 0;
        if(count($order->items) > 0){
            foreach($order->items as $item){
                $item->sell_quantity = 0;
                //找商家出貨單號
                $productModelId = $item->product_model_id;
                if(!empty($item->origin_digiwin_no)){ //轉換貨號
                    $productModel = ProductModelDB::where('digiwin_no',$item->origin_digiwin_no)->where('is_del',0)->first();
                    !empty($productModel) ? $productModelId = $productModel->id : '';
                }
                $tmp = VendorShippingItemDB::where('product_model_id',$productModelId)->where('order_numbers','like',"%$order->order_number%")->where('is_del',0)->orderBy('id','desc')->first();
                !empty($tmp) ? $item->vendor_shipping_no = $tmp->shipping_no : $item->vendor_shipping_no = null;
                //組合品資料錯誤時, 修正後自動補資料
                if(strstr($item->sku,'BOM') && $item->is_del == 0){
                    if(count($item->package) == 0){
                        $packageData = json_decode(str_replace('	','',$item->package_data));
                        foreach($packageData as $package){
                            if($package->bom == $item->sku){
                                foreach($package->lists as $list){
                                    $useQty = $list->quantity;
                                    $pm = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->where($productModelTable.'.sku',$list->sku)
                                    ->where($productModelTable.'.is_del',0)
                                    ->select([
                                        $productModelTable.'.id as product_model_id',
                                        $productModelTable.'.sku',
                                        $productModelTable.'.digiwin_no',
                                        $productTable.'.id as product_id',
                                        DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                        $productTable.'.unit_name',
                                        $productTable.'.price',
                                        $productTable.'.gross_weight',
                                        $productTable.'.net_weight',
                                        $productTable.'.direct_shipment',
                                        $productTable.'.is_tax_free',
                                        $productTable.'.vendor_price',
                                        $productTable.'.service_fee_percent as product_service_fee_percent',
                                        $productTable.'.package_data',
                                        $vendorTable.'.id as vendor_id',
                                        $vendorTable.'.name as vendor_name',
                                        $vendorTable.'.service_fee as vendor_service_fee',
                                        $vendorTable.'.shipping_verdor_percent',
                                    ])->first();
                                    if(!empty($pm)){
                                        OrderItemPackageDB::create([
                                            'order_id' => $order->id,
                                            'order_item_id' => $item->id,
                                            'product_model_id' => $pm->product_model_id,
                                            'sku' => $pm->sku,
                                            'digiwin_no' => $pm->digiwin_no,
                                            'digiwin_payment_id' => $order->digiwin_payment_id,
                                            'gross_weight' => $grossWeightRate * $pm->gross_weight,
                                            'net_weight' => $pm->net_weight,
                                            'quantity' => $useQty * $item->quantity,
                                            'is_del' => 0,
                                            'create_time' => $order->pay_time,
                                            'product_name' => $pm->product_name,
                                            'purchase_price' => 0,
                                            'direct_shipment' => $pm->direct_shipment,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                    foreach ($item->package as $package) {
                        $package->sell_quantity = 0;
                        if(count($package->sells) > 0){
                            foreach($package->sells as $sell){
                                $package->sell_quantity += $sell->sell_quantity;
                            }
                        }
                    }
                }else{
                    if(count($item->sells) > 0){
                        foreach($item->sells as $sell){
                            $item->sell_quantity += $sell->sell_quantity;
                        }
                    }
                }
                if($item->is_del == 0 && $item->direct_shipment == 0){
                    if(strstr($item->sku,'BOM')){
                        foreach($item->package as $package){
                            $package->quantity - $package->sell_quantity > 0 ? $order->chkDirectShipment++ : '';
                        }
                    }else{
                        $item->quantity - $item->sell_quantity > 0 ? $order->chkDirectShipment++ : '';
                    }
                }
            }
        }
        if(count($order->returns) > 0){
            //找出運費及其他資料
            foreach($order->returns as $return){
                if(count($return->items) > 0){
                    foreach($return->items as $item){
                        if($item->origin_digiwin_no == '901001'){
                            $item->product_name = '運費';
                            $item->unit_name = '個';
                            $item->vendor_name = 'iCarry';
                        }elseif($item->origin_digiwin_no == '901002'){
                            $item->product_name = '跨境稅';
                            $item->unit_name = '個';
                            $item->vendor_name = 'iCarry';
                        }else{
                            $tmp = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                            ->select([
                                $productModelTable.'.*',
                                $vendorTable.'.name as vendor_name',
                                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                $productTable.'.unit_name',
                            ])->where($productModelTable.'.digiwin_no',$item->origin_digiwin_no)->first();
                            $item->vendor_name = $tmp->vendor_name;
                            $item->product_name = $tmp->product_name;
                            $item->unit_name = $tmp->unit_name;
                        }
                    }
                }
            }
        }
        $shippingMethods = ShippingMethodDB::all();
        $shippingVendors = ShippingVendorDB::orderBy('sort','asc')->get();
        $vendors = VendorDB::select(['id','name'])->get();
        // dd($order);
        $compact = array_merge($compact, ['menuCode','order','shippingMethods','shippingVendors','vendors']);
        return view('gate.orders.show', compact($compact));
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
    public function update(Request $request, $id)
    {
        $data = $request->all();
        if(isset($request->invoice_sub_type)){
            if($data['invoice_sub_type'] == 1) { //捐贈
                $data['carrier_type'] = $data['carrier_num'] = null;
            }elseif($data['invoice_sub_type'] == 3) { //公司戶
                $data['carrier_type'] = $data['carrier_num'] = null;
            }else{
                $data['carrier_type'] == null ? $data['carrier_num'] = null : '';
            }
        }
        if(!empty($data['receiver_tel'])){
            $key = env('APP_AESENCRYPT_KEY');
            $receiverTel = $data['receiver_tel'];
            $data['receiver_tel'] = DB::raw("AES_ENCRYPT('$receiverTel', '$key')");
        }
        $data['admin_id'] = auth('gate')->user()->id;
        $myId['id'] = $id;
        $order = $this->getOrderData($myId,'show','update');
        $oldStatus = $order->status; //更新前取得舊狀態
        $oldShipping = $order->shippings; //更新前取得物流資料
        // 組合商品
        // https://gate.localhost/orders/155903
        // https://gate.localhost/orders/155900
        // https://gate.localhost/orders/155888

        // 單一+組合
        // https://gate.localhost/orders/155886

        // 單一
        // https://gate.localhost/orders/155860
        // https://gate.localhost/orders/155957
        if(isset($data['status']) && $oldStatus != 1 && $data['status'] == 1){
            if(empty($data['pay_time'])){
                Session::put('error', '付款時間不可為空白。');
                return redirect()->back();
            }
        }
        //修改直寄功能
        if(isset($data['directShip']) && $data['directShip'] == 1){
            if(count($data['items']) > 0){
                $c = $i = 0;
                $nonDirectShip = $directShip = [];
                foreach ($data['items'] as $it) {
                    $item = OrderItemDB::with('package')
                    ->join('product_model','product_model.id','order_item.product_model_id')
                    ->join('product','product.id','product_model.product_id')
                    ->join('vendor','vendor.id','product.vendor_id')
                    ->select([
                        'order_item.*',
                        'vendor.id as vendor_id',
                        'vendor.name as vendor_name',
                        // 'product.name as product_name',
                        DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                        'product.unit_name',
                        'product.direct_shipment as directShip',
                        'product_model.origin_digiwin_no',
                        'product_model.digiwin_no',
                        'product_model.sku',
                        'product_model.gtin13',
                        'product.serving_size',
                        'product.unit_name',
                        'product.id as product_id',
                        'product.package_data',
                    ])->find($it['id']);
                    $originQty = $item->quantity;

                    if($item->quantity != 0){
                        if($item->direct_shipment == 1 && $it['qty'] != $originQty){ //這個商品為直寄
                            $nonDirectShip[$c]['input_qty'] = $it['qty'];
                            $nonDirectShip[$c]['item'] = $item;
                            if($it['qty'] == 0){ //取消直寄
                                $nonDirectShip[$c]['qty'] = $item->quantity;
                            }elseif($it['qty'] > 0){ //修改
                                if($it['qty'] < $originQty){
                                    $nonDirectShip[$c]['qty'] = $originQty - $it['qty'];
                                }
                            }
                            $c++;
                        }elseif($item->direct_shipment == 0 || $item->direct_shipment == null){ //商品為非直寄
                            if($it['qty'] > 0){
                                if($it['qty'] >= $originQty){
                                    $it['qty'] = $originQty;
                                }
                                $directShip[$i]['item'] = $item;
                                $directShip[$i]['input_qty'] = $it['qty'];
                                $directShip[$i]['qty'] = $item->quantity - $it['qty'];
                            }
                            $i++;
                        }
                    }
                }
                sort($nonDirectShip);
                if(count($nonDirectShip) > 0){
                    for($x=0;$x<count($nonDirectShip);$x++){
                        $originItem = $nonDirectShip[$x]['item'];
                        $oldQty = $originItem->quantity;
                        if($nonDirectShip[$x]['input_qty'] == 0){
                            $nonDirectShipItem = OrderItemDB::with('package')
                            ->join('product_model','product_model.id','order_item.product_model_id')
                            ->where([['order_id', $id],['product_model_id', $originItem->product_model_id]])
                            ->where(function($query){
                                $query->where('direct_shipment',0)
                                ->orWhereNull('direct_shipment');
                            })->select([
                                'order_item.*',
                                'product_model.sku',
                            ])->first();
                            if(!empty($nonDirectShipItem)){
                                $nonDirectShipItem->update([
                                    'quantity' => $nonDirectShipItem->quantity + $oldQty,
                                    'direct_shipment' => 0,
                                ]);
                                if(strstr($nonDirectShipItem->sku,'BOM')){
                                    foreach($nonDirectShipItem->package as $package){
                                        $packageData = json_decode($originItem->package_data);
                                        foreach($packageData as $product){
                                            if($product->bom == $originItem->sku){
                                                foreach($product->lists as $list){
                                                    if($list->sku == $package->sku){
                                                        $p = ProductModelDB::join('product','product.id','product_model.product_id')
                                                            ->where('sku',$list->sku)
                                                            ->select([
                                                                'product.gross_weight',
                                                                'product.net_weight',
                                                            ])->first();
                                                        $useQty = $list->quantity;
                                                        break;
                                                    }
                                                }
                                            }
                                            $package->update([
                                                'quantity' => $package->quantity + ($useQty * $nonDirectShip[$x]['qty']),
                                                'direct_shipment' => 0,
                                            ]);
                                        }
                                    }
                                }
                                $originItem->update([
                                    'quantity' => 0,
                                ]);
                                if (strstr($originItem->sku, 'BOM')) {
                                    foreach($originItem->package as $package){
                                        $package->update([
                                            'quantity' => 0,
                                        ]);
                                    }
                                }
                            }else{ //直接轉成非直寄
                                $originItem->update(['direct_shipment' => 0]);
                                if(strstr($originItem->sku,'BOM')){
                                    foreach($originItem->package as $package){
                                        $package->update(['direct_shipment' => 0]);
                                    }
                                }
                            }
                            $originItem->shipping_memo == '廠商發貨' ? $originItem->update(['shipping_memo' => null]) : '';
                        }else{
                            //直寄的更新為input qty
                            $oldItemQty = $originItem->quantity;
                            $originItem->update(['quantity' => $nonDirectShip[$x]['input_qty']]);
                            $newItemQty = $originItem->quantity;
                            if (strstr($originItem->sku, 'BOM')) {
                                foreach ($originItem->package as $package) {
                                    $useQty = $package->quantity / $oldItemQty;
                                    $newQty = $useQty * $newItemQty;
                                    $package->update(['quantity' => $newQty]);
                                }
                            }
                            $nonDirectShipItem = OrderItemDB::with('package')
                            ->join('product_model','product_model.id','order_item.product_model_id')
                            ->where([['order_id', $id],['product_model_id', $originItem->product_model_id]])
                            ->where(function($query){
                                $query->where('direct_shipment',0)
                                ->orWhereNull('direct_shipment');
                            })->select([
                                'order_item.*',
                                'product_model.sku',
                            ])->first();
                            if(!empty($nonDirectShipItem)){
                                $nonDirectShipItem->update(['quantity' => $nonDirectShipItem->quantity + $nonDirectShip[$x]['qty']]);
                                if(strstr($nonDirectShipItem->sku,'BOM')){
                                    foreach($nonDirectShipItem->package as $package){
                                        $packageData = json_decode($originItem->package_data);
                                        foreach($packageData as $product){
                                            if($product->bom == $originItem->sku){
                                                foreach($product->lists as $list){
                                                    if($list->sku == $package->sku){
                                                        $p = ProductModelDB::join('product','product.id','product_model.product_id')
                                                            ->where('sku',$list->sku)
                                                            ->select([
                                                                'product.gross_weight',
                                                                'product.net_weight',
                                                            ])->first();
                                                        $useQty = $list->quantity;
                                                        break;
                                                    }
                                                }
                                            }
                                            $package->update([
                                                'quantity' => $package->quantity + ($useQty * $nonDirectShip[$x]['qty']),
                                            ]);
                                        }
                                    }
                                }
                            }else{
                                $inputQty = $nonDirectShip[$x]['input_qty'];
                                $orderItem = OrderItemDB::create([
                                    'order_id' => $id,
                                    'product_model_id' => $originItem->product_model_id,
                                    'digiwin_no' => $originItem->digiwin_no,
                                    'digiwin_payment_id' => $order->digiwin_payment_id,
                                    'price' => $originItem->price,
                                    'purchase_price' => $originItem->purchase_price,
                                    'gross_weight' => $originItem->gross_weight,
                                    'net_weight' => $originItem->net_weight,
                                    'is_tax_free' => $originItem->is_tax_free,
                                    'parcel_tax_code' => $originItem->parcel_tax_code,
                                    'parcel_tax' => $originItem->parcel_tax,
                                    'vendor_service_fee_percent' => $originItem->vendor_service_fee_percent,
                                    'shipping_verdor_percent' => $originItem->shipping_verdor_percent,
                                    'product_service_fee_percent' => $originItem->product_service_fee_percent,
                                    'quantity' => $inputQty,
                                    'is_del' => $originItem->is_del,
                                    'admin_memo' => $originItem->admin_memo,
                                    'create_time' => $originItem->create_time,
                                    'promotion_ids' => $originItem->promotion_ids,
                                    'product_name' => $originItem->product_name,
                                    'is_call' => $originItem->is_call,
                                    'direct_shipment' => 0,
                                ]);
                                if(strstr($originItem->sku,'BOM')){
                                    foreach($originItem->package as $package){
                                        $useQty = $package->quantity / $oldQty;
                                        $newQty = $inputQty * $useQty;
                                        $orderItemPackage = OrderItemPackageDB::create([
                                            'order_id' => $orderItem->order_id,
                                            'order_item_id' => $orderItem->id,
                                            'product_model_id' => $package->product_model_id,
                                            'sku' => $package->sku,
                                            'gross_weight' => $package->gross_weight,
                                            'net_weight' => $package->net_weight,
                                            'quantity' => $newQty,
                                            'purchase_price' => $package->purchase_price,
                                            'is_del' => $package->is_del,
                                            'admin_memo' => $package->admin_memo,
                                            'create_time' => $package->create_time,
                                            'product_name' => $package->product_name,
                                            'is_call' => $package->is_call,
                                            'direct_shipment' => 0,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
                sort($directShip);
                if(count($directShip) > 0){
                    for($x=0;$x<count($directShip);$x++){
                        $originItem = $directShip[$x]['item'];
                        //非直寄商品更新
                        if($directShip[$x]['qty'] == 0){
                            $directShipItem = OrderItemDB::with('package')
                            ->join('product_model','product_model.id','order_item.product_model_id')
                            ->where([['order_id', $id],['product_model_id', $originItem->product_model_id],['direct_shipment',1]])
                            ->select([
                                'order_item.*',
                                'product_model.sku',
                            ])->first();
                            if(!empty($directShipItem)){
                                $directShipItem->update([
                                    'quantity' => $directShipItem->quantity + $directShip[$x]['input_qty'],
                                ]);
                                if (strstr($originItem->sku, 'BOM')) {
                                    foreach ($directShipItem->package as $package) {
                                        $packageData = json_decode($originItem->package_data);
                                        foreach($packageData as $product){
                                            if($product->bom == $originItem->sku){
                                                foreach($product->lists as $list){
                                                    if($list->sku == $package->sku){
                                                        $useQty = $list->quantity;
                                                        break;
                                                    }
                                                }
                                            }
                                            $package->update([
                                                'quantity' => $package->quantity + ($useQty * $directShip[$x]['input_qty']),
                                            ]);
                                        }
                                    }
                                }
                                //原本的數量全部變為0
                                $originItem->update([
                                    'quantity' => 0,
                                ]);
                                if(strstr($originItem->sku,'BOM')){
                                    foreach($originItem->package as $package){
                                        $package->update([
                                            'quantity' => 0,
                                        ]);
                                    }
                                }
                            }else{
                                //直接全部轉為直寄
                                $originItem->update(['direct_shipment' => 1, 'shipping_memo' => '廠商發貨']);
                                if(strstr($originItem->sku,'BOM')){
                                    foreach($originItem->package as $package){
                                        $package->update(['direct_shipment' => 1]);
                                    }
                                }
                            }
                        }else{
                            $oldQty = $originItem->quantity;
                            $originItem->update([
                                'quantity' => $directShip[$x]['qty'],
                            ]);
                            if (strstr($originItem->sku, 'BOM')) {
                                $packages = OrderItemPackageDB::where([['order_item_id',$originItem->id],['is_del',0]])->get();
                                foreach ($packages as $p) {
                                    $useQty = $p->quantity / $oldQty;
                                    $newQty = $directShip[$x]['qty'] * $useQty;
                                    $p->update([
                                        'quantity' => $newQty,
                                    ]);
                                }
                            }
                            //如果存在直寄商品則加入
                            $directShipItem = OrderItemDB::with('package')
                            ->join('product_model','product_model.id','order_item.product_model_id')
                            ->where([['order_id', $id],['product_model_id', $originItem->product_model_id],['direct_shipment',1]])
                            ->select([
                                'order_item.*',
                                'product_model.sku',
                            ])->first();
                            if(!empty($directShipItem)){
                                $oldItemQty = $directShipItem->quantity;
                                $directShipItem->update(['quantity' => $directShipItem->quantity + $directShip[$x]['input_qty']]);
                                $newItemQty = $directShipItem->quantity;
                                if(strstr($directShipItem->sku,'BOM')){
                                    foreach($directShipItem->package as $pp){
                                        $oldItemQty == 0 ? $useQty = $pp->quantity : $useQty = $pp->quantity / $oldItemQty;
                                        $newQty = $newItemQty * $useQty;
                                        $pp->update([
                                            'quantity' => $newQty,
                                        ]);
                                    }
                                }
                            }else{
                                $inputQty = $directShip[$x]['input_qty'];
                                $orderItem = OrderItemDB::create([
                                    'order_id' => $id,
                                    'product_model_id' => $originItem->product_model_id,
                                    'digiwin_no' => $originItem->digiwin_no,
                                    'digiwin_payment_id' => $order->digiwin_payment_id,
                                    'price' => $originItem->price,
                                    'purchase_price' => $originItem->purchase_price,
                                    'gross_weight' => $originItem->gross_weight,
                                    'net_weight' => $originItem->net_weight,
                                    'is_tax_free' => $originItem->is_tax_free,
                                    'parcel_tax_code' => $originItem->parcel_tax_code,
                                    'parcel_tax' => $originItem->parcel_tax,
                                    'vendor_service_fee_percent' => $originItem->vendor_service_fee_percent,
                                    'shipping_verdor_percent' => $originItem->shipping_verdor_percent,
                                    'product_service_fee_percent' => $originItem->product_service_fee_percent,
                                    'quantity' => $inputQty,
                                    'is_del' => $originItem->is_del,
                                    'admin_memo' => $originItem->admin_memo,
                                    'create_time' => $originItem->create_time,
                                    'promotion_ids' => $originItem->promotion_ids,
                                    'product_name' => $originItem->product_name,
                                    'is_call' => $originItem->is_call,
                                    'direct_shipment' => 1,
                                    'shipping_memo' => '廠商發貨',
                                ]);
                                if(strstr($originItem->sku,'BOM')){
                                    foreach($originItem->package as $package){
                                        $useQty = $package->quantity / $oldQty;
                                        $newQty = $inputQty * $useQty;
                                        $orderItemPackage = OrderItemPackageDB::create([
                                            'order_id' => $orderItem->order_id,
                                            'order_item_id' => $orderItem->id,
                                            'product_model_id' => $package->product_model_id,
                                            'sku' => $package->sku,
                                            'gross_weight' => $package->gross_weight,
                                            'net_weight' => $package->net_weight,
                                            'quantity' => $newQty,
                                            'purchase_price' => $package->purchase_price,
                                            'is_del' => $package->is_del,
                                            'admin_memo' => $package->admin_memo,
                                            'create_time' => $package->create_time,
                                            'product_name' => $package->product_name,
                                            'is_call' => $package->is_call,
                                            'direct_shipment' => 1,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }elseif(isset($data['itemQty']) && $data['itemQty'] == 1){ //修改數量
            //檢查是否有出貨且鼎新出貨單需未確認才可以修改
            $chkSellItem = $this->chkSellItem($data,'modify');
            if($chkSellItem == 0) {
                $result = OrderCancelJob::dispatchNow($order,$data);
                if(!empty($result)){
                    Session::put('error',"請注意!! $result 取消數量不可大於 (訂單數量 - 已出貨數量)。");
                }
            }else{
                Session::put('error',"請注意!! 鼎新銷貨單已確認無法部分取消，請先至鼎新將相關的銷貨單取消確認。");
            }
        }elseif(isset($data['returnQty']) && $data['returnQty'] == 1){ //退貨處理
            if(!empty($order->sell)){
                $chkSellItem = $this->chkSellItem($data,'return');
                //檢查銷貨單是否已確認
                if($chkSellItem == 0){
                    SellReturnJob::dispatchNow($myId,$data);
                }else{
                    Session::put('error',"請注意!! 鼎新銷貨單尚未全部確認，請先至鼎新將銷貨單做確認。");
                }
            }else{
                Session::put('error',"本訂單沒有銷貨單，無法做退貨處理。");
            }
        }elseif(isset($data['allowance']) && $data['allowance'] == 1){ //折讓處理
            $result = SellAllowanceJob::dispatchNow($order,$data);
            if(!empty($result)){
                $total = $result->price + $result->tax;
                Session::put('info', "折讓處理完成，共折讓 $total 元");
            }
        }elseif(isset($data['purchasePrice']) && $data['purchasePrice'] == 1){ //修改採購價
            foreach ($data['items'] as $it) {
                $item = OrderItemDB::find($it['id']);
                $originPrice = $item->purchase_price;
                if($originPrice != $it['price']){
                    $item->update(['purchase_price' => $it['price']]);
                }
            }
        }else{ //其他功能
            if(isset($data['order_shipping'])){
                $shippingNumber = '';
                $shippingMemo = [];
                for($i=0;$i<count($data['order_shipping']);$i++){
                    $data['order_shipping'][$i]['id'] ? $orderShipping = OrderShippingDB::findOrFail($data['order_shipping'][$i]['id'])->update($data['order_shipping'][$i]) : $orderShipping = OrderShippingDB::create($data['order_shipping'][$i]);
                    $shippingMemo[$i] = [
                        'create_time' => date('Y-m-d H:i:s'),
                        'express_way' => $data['order_shipping'][$i]['express_way'],
                        'express_no' => $data['order_shipping'][$i]['express_no']
                    ];
                    $shippingNumber .= ','.$data['order_shipping'][$i]['express_no'];
                }
                $data['shipping_memo'] = json_encode($shippingMemo,JSON_UNESCAPED_UNICODE); //回寫json格式到shipping_memo欄位以利查詢用
                // $data['shipping_number'] = ltrim($shippingNumber,','); //自動將物流單號填入
            }
            //廠商出貨資訊
            if(isset($data['vendor_shipping'])){
                for($i=0;$i<count($data['vendor_shipping']);$i++) {
                    $data['vendor_shipping'][$i]['id'] ? OrderVendorShippingDB::findOrFail($data['vendor_shipping'][$i]['id'])->update($data['vendor_shipping'][$i]) : OrderVendorShippingDB::create($data['vendor_shipping'][$i]);
                }
            }
            if($order->status != -1 && isset($data['status']) && $data['status'] == -1){
                //檢查是否有出貨單
                if(empty($order->sell)){
                    if($order->spend_point > 0){
                        //花費購物金 + 使用者剩餘購物金
                        $balance = $order->spend_point + $order->user->point;
                        $order->user->update(['points' => $balance]);
                        $userPoint = UserPointDB::create([
                            'user_id' => $order->user->id,
                            'point_type' => "取消訂單 {$order->order_number} 退回購物金 {$order->spend_point} 點",
                            'points' => $order->spend_point,
                            'balance' => $balance,
                        ]);
                    }

                    //取消shopcom訂單
                    if(!empty($order->shopcom)){
                        $this->cancelSendToShopcom($order->order_number,$order->create_time,$order->amount+$order->parcel_tax,$order->shopcom->RID,$order->shopcom->Click_ID);
                    }

                    //取消Tradevan訂單
                    if(!empty($order->tradevan)){
                        $this->cancelSendToTradevan($order->order_number,$order->create_time,$order->amount+$order->parcel_tax,$order->tradevan->RID,$order->tradevan->Click_ID);
                    }

                    $order->update(['status' => -1]);
                    OrderCancelJob::dispatchNow($order,$data);
                }else{
                    Session::put('error',"此訂單已有出貨，無法取消訂單，請改用銷退方式。");
                    return redirect()->back();
                }
            }
            //更新訂單資料
            if(isset($data['admin_memo']) && ($order->admin_memo != $data['admin_memo'])){ //管理者備註修改需做紀錄
                $orderLog = OrderLogDB::create([
                    'order_id' => $order->id,
                    'column_name' => 'admin_memo',
                    'log' => $data['admin_memo'],
                    'editor' => auth('gate')->user()->id,
                ]);
            }
            if(isset($data['invoice_sub_type']) && $data['invoice_sub_type'] != 1){
                $data['love_code'] = null;
            }
            //更新亞萬訂單資料
            if(!empty($data['asiamiles_account']) && $data['asiamiles_account'] != ''){
                $asiamiles = AsiamilesDB::where('order_id',$order->id)->first();
                if(!empty($asiamiles)){
                    $asiamiles->update([
                        'asiamiles_account' => substr($data['asiamiles_account'],0,10),
                        'asiamiles_name' => mb_substr($data['asiamiles_name'],0,100),
                        'asiamiles_last_name' => mb_substr($data['asiamiles_lastname'],0,100),
                    ]);
                }else{
                    AsiamilesDB::create([
                        'order_id' => $order->id,
                        'asiamiles_account' => substr($data['asiamiles_account'],0,10),
                        'asiamiles_name' => mb_substr($data['asiamiles_name'],0,100),
                        'asiamiles_last_name' => mb_substr($data['asiamiles_lastname'],0,100),
                    ]);
                }
            }
            $order->update($data);
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

    public function getShippingVendors(Request $request)
    {
        return response()->json(ShippingVendorDB::select(['id','name','sort'])->orderBy('sort', 'asc')->get());
    }

    public function getVendors(Request $request)
    {
        return response()->json(VendorDB::select(['id','name'])->get());
    }


    public function getExpressDataNew(){
        $request = request();
        if(!empty($request['getExpress'])){
            $SVs = ShippingVendorDB::select('name')->orderBy('sort', 'asc')->get();
            foreach($SVs as $sv){
                $sv->count = OrderItemDB::where('shipping_memo',$sv->name)->groupBy('order_id')->count();
            }
            $SVarray = $SVs->toArray();
            $s[0]['name'] = '未分類';
            $s[0]['count'] =  OrderItemDB::whereNull('shipping_memo')->orWhere('shipping_memo','')->groupBy('order_id')->count();
            $s[1]['name'] = '含多筆運單之訂單';
            $s[1]['count'] = 3000;
            $data = array_merge($SVarray,$s);
            return response()->json($data);
        }
    }

    public function getExpressData(){
        $request = request();
        if(!empty($request['getExpress'])){
            $data = $orders = [];
            $SVs = ShippingVendorDB::select('name')->orderBy('sort', 'asc')->get();
            $getOrders = $this->getOrderData($request);
            if(count($getOrders) > 0){
                DB::statement($this->dropView());

                $orderIds = join(',',$getOrders);
                DB::statement($this->createView($orderIds));

                $orders = DB::table('shipping_data')
                ->selectRaw('count(order_id) as `count`, shipping_memo')
                ->groupBy('shipping_memo')
                ->get();

                DB::statement($this->dropView());
            }

            if(count($orders) > 0){
                foreach($SVs as $sv){
                    foreach($orders as $order){
                        if($order->shipping_memo == $sv->name){
                            $sv->count = $order->count;
                            break;
                        }
                    }
                }
                foreach($orders as $order){
                    if($order->shipping_memo == ''){
                        $s[0]['name'] = '未分類';
                        $s[0]['count'] = $order->count;
                        break;
                    }
                }
                $SVarray = $SVs->toArray();
                $s[1]['name'] = '含多筆運單之訂單';

                $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
                $tmp = DB::select( DB::raw("(select count(order_id) as `count` from (SELECT order_id FROM (SELECT order_id, shipping_memo FROM $orderItemTable WHERE order_id IN($orderIds) and shipping_memo is not null GROUP by shipping_memo, order_id ORDER BY `order_item`.`shipping_memo` DESC) as tmp WHERE 1 GROUP by order_id HAVING (count(order_id) >=2)) tmp2 where 1)") );

                $s[1]['count'] = $tmp[0]->count;
                $data = array_merge($SVarray,$s);
            }

            return response()->json($data);
        }
    }

    public function multiProcess(Request $request)
    {
        //將進來的資料作參數轉換及附加到$param中
        foreach ($request->all() as $key => $value) {
            $param[$key] = $value;
        }
        $method = null;
        $url = 'https://'.env('GATE_DOMAIN').'/exportCenter';
        $param['admin_id'] = auth()->user()->id;
        $param['admin_name'] = auth()->user()->name;
        if(!empty($param['method'])){
            $param['method'] == 'OneOrder' ? $method = '單一訂單' : '';
            $param['method'] == 'selected' ? $method = '自行勾選' : '';
            $param['method'] == 'allOnPage' ? $method = '目前頁面全選' : '';
            $param['method'] == 'byQuery' ? $method = '依查詢條件' : '';
            $param['method'] == 'allData' ? $method = '全部資料' : '';
        }
        if(!empty($param['cate'])){
            $param['cate'] == 'pickupShipping' ? $param['filename'] = null : '';
        }
        if(!empty($method)){
            !empty($param['filename']) ? $param['name'] = $param['filename'].'_'.$method : $param['name'] = $param['filename'];
        }
        $param['export_no'] = time();
        $param['start_time'] = date('Y-m-d H:i:s');
        empty($param['type']) ? $param['type'] = null : '';
        $param['cate'] == 'pdf' || $param['type'] == 'pdf' ? $param['filename'] = $param['name'].'_'.time().'.pdf' : ($param['type'] == 'DHL' ? $param['filename'] = $param['name'].'_'.time().'.xls' : $param['filename'] = $param['name'].'_'.time().'.xlsx');
        $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>';
        if($param['cate'] == 'Synchronize'){ //同步鼎新
            if($param['method'] == 'byQuery'){
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，依條件查詢資料量較大，改由於背端執行，請稍後一段時間，再重新查詢或按F5重新整理。';
            }else{
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，選擇的訂單已同步至頂新。';
            }
            if($param['method'] == 'byQuery'){
                OrderSyncToDigiwin::dispatch($param);
            }else{
                OrderSyncToDigiwin::dispatchNow($param);
            }
        }elseif($param['cate'] == 'invoice'){ //發票處理
            if($param['method'] == 'byQuery'){
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，依條件查詢資料量較大，改由於背端執行，請稍後一段時間，再重新查詢或按F5重新整理。';
            }else{
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，選擇的訂單已開立發票。';
            }
            if($param['method'] == 'byQuery'){
                AdminInvoiceJob::dispatch($param);
            }else{
                AdminInvoiceJob::dispatchNow($param);
            }
        }elseif($param['cate'] == 'Purchase'){ //商品採購
            if($param['method'] == 'byQuery'){
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，依條件查詢資料量較大，改由於背端執行，請稍後一段時間，再重新查詢或按F5重新整理。';
            }else{
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，選擇的商品已建立採購單。';
            }
            // OrderToPurchaseOrder::dispatchNow($param);
            if($param['method'] == 'byQuery'){
                OrderToPurchaseOrder::dispatch($param);
            }else{
                OrderToPurchaseOrder::dispatchNow($param);
            }
        }elseif($param['cate'] == 'pickupShipping'){
            if(!empty($param['type'])){
                if($param['type'] == '自行挑選'){
                    if(empty($param['shippingMemo'])){
                        $message = '選擇自行挑選類別時，物流商不可空白，請挑選物流商。';
                        Session::put('error', $message);
                        return redirect()->back();
                    }
                }
                OrderPickupShippingVendor::dispatchNow($param);
                if(!empty($param['order_id'])){
                    $message = '該商品已完成挑選物流商。';
                }else{
                    $message = '該訂單已完成挑選物流商。';
                }
            }else{
                $message = '請選擇物流類型。';
                Session::put('error', $message);
                return redirect()->back();
            }
        }elseif($param['cate'] == 'CheckOrder'){
            CheckOrderStatusJob::dispatchNow($param);
            $message = '已檢查處理被選擇的訂單。';
        }elseif($param['cate'] == 'sell'){
            $sellImport = [];
            $order = OrderDB::find($param['order_id']);
            $items = $param['items'];
            sort($items);
            for($i=0;$i<count($items);$i++){
                if($items[$i]['quantity'] > 0){
                    $sellImport[] = [
                        'import_no' => $param['export_no'],
                        'type' => 'warehouse',
                        'order_number' => $order->order_number,
                        'shipping_number' => $items[$i]['shippingNumber'],
                        'gtin13' => $items[$i]['gtin13'],
                        'purchase_no' => null,
                        'digiwin_no' => null,
                        'product_name' => $items[$i]['productName'],
                        'quantity' => $items[$i]['quantity'],
                        'sell_date' => $items[$i]['sellDate'],
                        'status' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
            if(count($sellImport) > 0){
                SellImportDB::insert($sellImport);
                $sellParam['type'] = 'warehouse';
                $sellParam['order_number'] = $order->order_number;
                $result = SellImportJob::dispatchNow($sellParam);
                $message = '手動銷貨已處理完成。';
            }
        }elseif($param['cate'] == 'acOrder'){
            // Ac訂單處理
            if(!empty($param['orderNumber'])){
                $orderNumber = $param['orderNumber'];
                $order = OrderDB::with('acOrder','sellImport','itemData')->where('order_number',$orderNumber)->first();
                if(!empty($order)){
                    if(!empty($order->acOrder)){
                        //檢查是否有建立sellImport資料, 如果沒有則建立
                        if(empty($order->sellImport)){
                            if(count($order->itemData) > 0){
                                foreach($order->itemData as $item){
                                    $sellImport = SellImportDB::create([
                                        'import_no' => time(),
                                        'type' => 'warehouse',
                                        'order_number' => $order->order_number,
                                        'shipping_number' => $order->partner_order_number,
                                        'gtin13' => $item->sku,
                                        'purchase_no' => null,
                                        'digiwin_no' => null,
                                        'product_name' => $item->product_name,
                                        'quantity' => $item->quantity,
                                        'sell_date' => $order->book_shipping_date,
                                        'stockin_time' => $order->vendor_arrival_date,
                                        'status' => 0,
                                    ]);
                                }
                            }
                        }
                        $acOrderParam['acOrderId'] = $order->acOrder->id;
                        $acOrderParam['orderId'] = $order->id;
                        $acOrderParam['orderNumber'] = $order->order_number;
                        AcOrderProcessJob::dispatchNow($acOrderParam);
                        $message = '訂單已處理完成。';
                        //補開發票
                        if($order->acOrder->is_invoice == 0){
                            $invoiceParam['id'] = $order->id;
                            $invoiceParam['type'] = 'create';
                            $invoiceParam['model'] = 'acOrderOpenInvoice';
                            AdminInvoiceJob::dispatchNow($invoiceParam);
                            $message .= '發票已補開。';
                        }
                    }else{
                        $message = '找不到串接訂單。';
                    }
                }else{
                    $message = '訂單不存在。';
                }
            }
        }elseif($param['cate'] == 'Refund'){
            $message = OrderRefundMailJob::dispatchNow($param);
        }elseif($param['cate'] == 'RemovePurchase'){
            RemoveSyncedOrderItemPurchaseData::dispatchNow($param);
            $message = '已移除被選擇的商品採購註記。';
        }elseif($param['cate'] == 'addNotPurchase'){
            addNotPurchaseMarkJob::dispatchNow($param);
            $message = '已新增被選擇的商品不採購註記。';
        }elseif($param['cate'] == 'getOrderDate'){
            $message = '判斷到貨日已於背端執行，請過一段時間重新整理頁面。';
            env('APP_ENV') == 'local' ? OrderExportDigiWinJob::dispatchNow($param) : OrderExportDigiWinJob::dispatch($param);
        }else{
            $param['store'] = true;
            $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間重新整理頁面或至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>';
            //本機測試用
            //訂單更新在laravel excel套件中無法用背端處理
            if(env('APP_ENV') == 'local'){
            $param['store'] = false;
            return AdminExportJob::dispatchNow($param); //直接馬上下載則必須使用 return
            }else{
                AdminExportJob::dispatch($param);
            }
        }
        if(is_array($message)){
            Session::put($message['status'], $message['message']);
        }else{
            Session::put('info', $message);
        }
        return redirect()->back();
    }

    public function modify(Request $request)
    {
        if($request->column_data == null && $request->column_name == 'merge_order'){
            $orders = $this->getOrderData($request,'index');
            foreach($orders as $order){
                OrderDB::whereIn('order_number',explode(',',$order->merge_order))->update(['merged_order' => null]);
            }
        }
        $orders = $this->getOrderData($request,'modify');
        if(!empty($orders)){
            foreach($orders as $order){
                $request->column_data == null || $request->column_data == '' ? $request->column_data = '清除註記' : '';
                if($request->column_data == '清除註記'){
                    $order->update([ $request->column_name => null]);
                }else{
                    if($request->column_name == 'merge_order'){
                        //清除之前的訂單註記
                        OrderDB::whereIn('order_number',explode(',',$order->merge_order))->update(['merged_order' => null]);
                        $mainOrderNumber = $order->order_number;
                        $merges = explode(',',str_replace([' ','，'],['',','],$request->column_data));
                        $chkMerge = 0;
                        for($i=0;$i<count($merges);$i++){
                            if(!is_numeric($merges[$i])){
                                $chkMerge++;
                            }
                            if($merges[$i] == $mainOrderNumber){
                                unset($merges[$i]);
                            }
                        }
                        sort($merges);
                        $request->column_data = join(',',$merges);
                        if($chkMerge == 0){
                            $order->update([ $request->column_name => $request->column_data]);
                            OrderDB::whereIn('order_number',explode(',',$order->merge_order))->update(['merged_order' => $order->order_number]);
                        }else{
                            $request->column_data = $request->column_data.'，非純數字，請重新輸入。';
                        }
                    }else{
                        $order->update([ $request->column_name => $request->column_data]);
                    }
                }
                $orderLog = OrderLogDB::create([
                    'order_id' => $order->id,
                    'column_name' => $request->column_name,
                    'log' => $request->column_data,
                    'editor' => auth('gate')->user()->id,
                ]);
            }
        }

        if(!empty($request->id) && is_array($request->id)){
            return response()->json($orders);
        }
        if(!empty($request->con) && is_array($request->con)){
            return redirect()->back();
        }
        return null;
    }

    public function getLog(Request $request)
    {
        $adminTable = env('DB_DATABASE').'.'.(new AdminDB)->getTable();
        $orderLogTable = env('DB_ICARRY').'.'.(new OrderLogDB)->getTable();
        if($request->column_name == 'sync_date'){
            $orderLogs = SyncedOrderDB::join('admins', 'admins.id', 'synced_orders.admin_id')
            ->where('synced_orders.order_id',$request->order_id)
            ->select([
                'synced_orders.*',
                'admins.name',
                DB::raw("(CASE WHEN synced_orders.status = -1 THEN '已取消' WHEN synced_orders.status = 1 THEN '待出貨' WHEN synced_orders.status = 2 THEN '集貨中' WHEN synced_orders.status = 3 THEN '已出貨' WHEN synced_orders.status = 4 THEN '已完成' END) as status"),
                DB::raw("DATE_FORMAT(synced_orders.created_at,'%Y/%m/%d %H:%i:%s') as create_time"),
            ])->orderBy('synced_orders.created_at','desc')->get();
        }else{
            $orderLogs = OrderLogDB::join($adminTable, $adminTable.'.id', $orderLogTable.'.editor');
            !empty($request->order_item_id) ? $orderLogs = $orderLogs->where($orderLogTable.'.order_item_id', $request->order_item_id) : '';
            !empty($request->order_id) ? $orderLogs = $orderLogs->where($orderLogTable.'.order_id', $request->order_id) : '';
            !empty($request->column_name) ? $orderLogs = $orderLogs->where($orderLogTable.'.column_name',$request->column_name) :'';
            $orderLogs = $orderLogs->select([
                $adminTable.'.name',
                $orderLogTable.'.column_name',
                $orderLogTable.'.log',
                DB::raw("DATE_FORMAT($orderLogTable.create_time,'%Y-%m-%d %H:%i:%s') as created_at"),
            ])->orderBy($orderLogTable.'.create_time', 'desc')->get();
        }
        return response()->json($orderLogs);
    }

    function getUnPurchase(Request $request)
    {
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        if(!empty($request->condition)){
            $condition = $request->condition;
            for($i=0;$i<count($condition);$i++){
                $con[$condition[$i]['name']] = $condition[$i]['value'];
            }
            $request->request->add(['con' => $con]);
        }
        //找出同步過且未採買的訂單資料及ID
        $orderIds = $this->getOrderData($request,'getUnPurchaseOrders');
        //抓商品資料
        if(count($orderIds) > 0){
            $items = $this->getUnPurchaseSyncedOrderItemData($orderIds);
            //檢查票券是否有使用, 有使用才能建立採購單
            foreach($items as $item){
                if($item->product_category_id == 17){
                    //找出syncedOrderItem裡面的orderIds 並且排除渠道訂單,才能找出真正需要的數量
                    $ticketOrderIds = OrderDB::whereIn('id',explode(',',$item->orderIds))->where('create_type','web')->pluck('id')->all();
                    $ticketCount = 0;
                    if(count($ticketOrderIds) > 0){
                        $ticketCount = TicketDB::where([['digiwin_no',$item->digiwin_no],['status',2]])
                        ->whereNull('purchase_no')
                        ->whereIn('order_id',$ticketOrderIds)->count();
                    }
                    $item->quantity = $ticketCount;
                }
            }
            $data['items'] = $items;
            $data['orderIds'] = $orderIds;
            return response()->json($data);
        }
        return null;
    }

    function getPurchasedItems(Request $request)
    {
        if(!empty($request->condition)){
            $condition = $request->condition;
            for($i=0;$i<count($condition);$i++){
                $con[$condition[$i]['name']] = $condition[$i]['value'];
            }
            $request->request->add(['con' => $con]);
        }
        //找出同步過且未採買的訂單資料及ID
        $orderIds = $this->getOrderData($request,'getPurchasedItems');
        //抓已採購商品資料
        if(count($orderIds) > 0){
            $items = $this->getPurchaseSyncedOrderItemData($orderIds);
            return response()->json($items);
        }
        return null;
    }

    function purchaseCancel(Request $request)
    {
        if(!empty($request->id)){
            $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
            $synceOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
            $item = SyncedOrderItemDB::join($productModelTable,$productModelTable.'.id',$synceOrderItemTable.'.product_model_id')
                ->select([
                    $synceOrderItemTable.'.*',
                    $productModelTable.'.sku',
                ])->find($request->id);
            if(!empty($item)){
                if(strstr($item->sku,'BOM')){
                    $itemPackages = SyncedOrderItemPackageDB::where('order_item_id',$item->order_item_id)->update([ 'purchase_no' => null, 'purchase_date' => null ]);
                }
                $item->update([ 'purchase_no' => null, 'purchase_date' => null ]);
                return true;
            }else{
                return false;
            }
        }
    }

    public function import(Request $request)
    {
        // $request->request->add(['test' => true]); //測試用, 測試完關閉
        $imports = ['宜睿匯入','MOMO匯入','鼎新訂單匯入','物流單號匯入','批次修改管理員備註','訂單在途存貨','錢街發票資訊'];
        $request->cate == 'orders' ? $request->request->add(['imports' => $imports]) : ''; //加入request
        $request->request->add(['admin_id' => auth('gate')->user()->id]); //加入request
        $request->request->add(['import_no' => time()]); //加入request
        if($request->cate == 'orders' && in_array($request->type, $imports)){
            if ($request->hasFile('filename')) {
                $file = $request->file('filename');
                $uploadedFileMimeType = $file->getMimeType();
                $excelMimes = ['application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                $textMimes = ['text/plain','application/octet-stream'];
                //宜睿的檔案沒有副檔名的文字檔
                $message = null;
                if($request->type == '宜睿匯入' && !in_array($uploadedFileMimeType, $textMimes)){
                    $message = "檔案格式錯誤， $request->type 只接受文字檔或無副檔名檔案。";
                }elseif($request->type != '宜睿匯入' && !in_array($uploadedFileMimeType, $excelMimes)){
                    $message = "檔案格式錯誤，$request->type 只接受 Excel 檔案格式。";
                }
                if(!empty($message)){
                    Session::put('error', $message);
                    return redirect()->back();
                }else{
                    if($request->type == '物流單號匯入'){
                        $results = OrderShippingMemoFileImportJob::dispatchNow($request); //直接馬上處理
                    }elseif($request->type == '批次修改管理員備註'){
                        $results = OrderMemoFileImportJob::dispatchNow($request); //直接馬上處理
                    }elseif($request->type == '訂單在途存貨'){
                        $param['start_time'] = date('Y-m-d H:i:s');
                        $results = OrderShippingFileImportJob::dispatchNow($request); //直接馬上處理
                        $param['admin_id'] = auth('gate')->user()->id;
                        $param['method'] = 'allData';
                        $param['cate'] = 'excel';
                        $param['model'] = 'orders';
                        $param['type'] = 'orderShipping';
                        $param['name'] = 'iCarry訂單在途存貨';
                        $param['export_no'] = $request->import_no;
                        $param['filename'] = $param['name'].'_'.$param['export_no'].'.xlsx';
                        if(!empty($results['error']) ){
                            $message = $results['error'];
                            Session::put('error', $message);
                        }else{
                            $param['data'] = $results;
                            if(env('APP_ENV') == 'local') {
                                $param['store'] = false;
                                return AdminExportJob::dispatchNow($param); //直接馬上下載則必須使用 return
                            } else {
                                $param['store'] = true;
                                AdminExportJob::dispatch($param);
                            }
                            $url = 'https://'.env('GATE_DOMAIN').'/exportCenter';
                            $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>';
                            Session::put('success', $message);
                        }
                    }else{
                        $results = OrderFileImportJob::dispatchNow($request); //直接馬上處理
                        if(!empty($results['error']) ){
                            $message = $results['error'];
                            Session::put('error', $message);
                        }else{
                            $fail = $results['fail'];
                            $success = $results['success'];
                            $total = $fail + $success;
                            $result = OrderImportJob::dispatchNow($results);
                            $orders = $result['orders'];
                            if($fail > 0){
                                $message = $request->type." 共匯入 $total 筆，成功匯入 $success 筆，產生 $orders 筆訂單，$fail 筆資料異常，請至訂單匯入異常查看。";
                                Session::put('error', $message);
                            }else{
                                $message = $request->type." 共匯入 $total 筆，成功匯入 $success 筆，產生 $orders 筆訂單。";
                                Session::put('success', $message);
                            }
                        }
                    }
                    if($request->type == '物流單號匯入' || $request->type == '批次修改管理員備註'){
                        if(!empty($results)){
                            $message = '下列 iCarry訂單號碼 / 合作廠商訂單編號 找不到資料，請確認資料是否正確。';
                            Session::put('error', $message);
                            Session::put('importErrors', $results);
                        }else{
                            $message = "$request->type 資料已全部處理完成。";
                            Session::put('success', $message);
                        }
                    }
                }
            }
        }elseif($request->cate == 'acorders' && in_array($request->type, $imports)){
            if ($request->hasFile('filename')) {
                $file = $request->file('filename');
                $uploadedFileMimeType = $file->getMimeType();
                $excelMimes = ['application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                $textMimes = ['text/plain','application/octet-stream'];
                $message = null;
                if($request->type != '錢街發票資訊' && !in_array($uploadedFileMimeType, $excelMimes)){
                    $message = "檔案格式錯誤，$request->type 只接受 Excel 檔案格式。";
                }

                if(!empty($message)){
                    Session::put('error', $message);
                    return redirect()->back();
                }else{
                    $result = Excel::toArray(new AcOrderSerialNoImport(), $file);
                    if(count($result[0]) > 0){
                        $importData = $result[0];
                        $x=0; $snos = [];
                        for($i=0;$i<count($importData);$i++){
                            if(!empty($importData[$i][0])){
                                $snos[] = $importData[$i][0];
                            }
                        }
                        $param['snos'] = $snos;
                        $param['filename'] = "錢街發票資訊匯出_".time().".xlsx";
                        return Excel::download(new AcOrderInvoiceExport($param), $param['filename']);
                    }else{
                        Session::put('error',"檔案內沒有資料，請檢查檔案是否正確。");
                    }
                }
            }
        }
        return redirect()->back();
    }

    public function markNotPurchase(Request $request)
    {
        if(!empty($request->order_item_id)){
            $orderItem = OrderItemDB::find($request->order_item_id);
            if(!empty($orderItem)){
                $syncedItem = SyncedOrderItemDB::where([['order_id',$orderItem->order_id],['order_item_id',$orderItem->id],['product_model_id',$orderItem->product_model_id]])->first();
                // return response()->json($syncedItem);
                if($orderItem->not_purchase == 1){
                    $orderItem->update(['not_purchase' => 0]);
                    !empty($syncedItem) ? $syncedItem->update(['not_purchase' => 0]) : '';
                }else{
                    $orderItem->update(['not_purchase' => 1]);
                    !empty($syncedItem) ? $syncedItem->update(['not_purchase' => 1]) : '';
                }
                return response()->json($orderItem->not_purchase);
            }
        }
        return null;
    }

    public function getInfo(Request $request)
    {
        $adminId = auth('gate')->user()->id;
        $adminUser = AdminDB::find($adminId);
        $data = [];
        $isPass = $logMemo = $KeypassMemo = $data['message'] = $data['order'] = $data['count'] = null;
        if(!empty($request->pwd)){
            if(Hash::check($request->pwd, env('GET_INFO_PWD'))){
                $key = env('APP_AESENCRYPT_KEY');
                $adminUser->update(['lock_on' => 0]);
                $order = $this->getOrderData($request,'getInfo');
                $isPass = 1;
                if(!empty($order)){
                    !empty($order->asiamiles) ? $order->asiamiles_account = $order->asiamiles->asiamiles_account : $order->asiamiles_account = null;
                    !empty($order->asiamiles) ? $order->asiamiles_name = $order->asiamiles->asiamiles_name : $order->asiamiles_name = null;
                    !empty($order->asiamiles) ? $order->asiamiles_lastname = $order->asiamiles->asiamiles_last_name : $order->asiamiles_lastname = null;
                    $user = UserDB::select([
                        '*',
                        DB::raw("IF(mobile IS NULL,'',AES_DECRYPT(mobile,'$key')) as mobile"),
                    ])->find($order->user_id);
                    !empty($user) ? $order->user_name = null : $order->user_name = null;
                    !empty($user) ? $order->user_email = null : $order->user_email = null;
                    !empty($user) ? $order->user_tel = null: $order->user_tel = null;
                    $data['order'] = $order;
                    $KeypassMemo = "查詢訂單資訊 ($order->order_number) 成功。";
                }else{
                    $KeypassMemo = $data['message'] = '查無訂單資料';
                }
            }else{
                $isPass = 0;
                $adminUser->increment('lock_on');
                $data['count'] = $adminUser->lock_on;
                $KeypassMemo = $data['message'] = '訂單資訊密碼輸入錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $KeypassMemo = $data['message'] = '密碼輸入錯誤三次，帳號已被鎖定！請聯繫管理員。' : '';
                if($adminUser->lock_on >= 3){
                    $logMemo = '訂單資訊密碼輸入錯誤 3 次，帳號鎖定。';
                    auth('gate')->logout();
                }else{
                    $logMemo = "訂單資訊密碼輸入錯誤 $adminUser->lock_on 次。";
                }
            }
        }
        AdminKeypassLogDB::create([
            'type' => '訂單查詢',
            'is_pass' => $isPass,
            'memo' => $KeypassMemo,
            'admin_id' => $adminUser->id,
            'admin_name' => $adminUser->name,
        ]);
        if(!empty($logMemo)){
            AdminLoginLogDB::create([
                'admin_id' => $adminUser->id,
                'result' => $logMemo,
                'ip' => $this->loginIp,
            ]);
        }
        return response()->json($data);
    }

    private function createView($orderIds): string
    {
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();

        return <<<SQL
            CREATE VIEW shipping_data AS
            SELECT order_id,shipping_memo FROM $orderItemTable WHERE order_id IN ($orderIds) GROUP by order_id , shipping_memo ORDER BY `order_item`.`shipping_memo` DESC
            SQL;
    }

    private function dropView(): string
    {
        return <<<SQL

            DROP VIEW IF EXISTS `shipping_data`;
            SQL;
    }

    protected function chkSellItem($data,$model = null)
    {
        $chkSellItem = 0;
        if(isset($data['items']) && count($data['items']) > 0){
            for($i=0;$i<count($data['items']);$i++){
                $orderItemIds[] = $data['items'][$i]['id'];
            }
            $items = OrderItemDB::join('product_model','product_model.id','order_item.product_model_id')
            ->with('sells','package','package.sells')
            ->whereIn('order_item.id',$orderItemIds)
            ->where('order_item.is_del',0)
            ->select([
                'order_item.*',
                'product_model.sku',
            ])->get();
            if(count($items) > 0){
                foreach($items as $item){
                    if(strstr($item,'BOM')){
                        foreach($item->package as $package){
                            if(count($package->sells) > 0){
                                foreach($package->sells as $sell){
                                    $tmp = ErpCOPTHDB::where([['TH002',$sell->erp_sell_no],['TH003',$sell->erp_sell_sno]])->first();
                                    // $model == 'return' && !empty($tmp) && $tmp->TH020 == 'Y' ? '' : $chkSellItem++;
                                    // $model == 'modify' && !empty($tmp) && $tmp->TH020 == 'Y' ? $chkSellItem++ : '';
                                    if(!empty($tmp)){
                                        if($model=='modify'){
                                            $tmp->TH020 == 'Y' ? $chkSellItem++ : '';
                                        }elseif($model=='return'){
                                            $tmp->TH020 == 'Y' ? '' : $chkSellItem++;
                                        }
                                    }
                                }
                            }else{
                                $model == 'return' ? $chkSellItem++ : '';
                            }
                        }
                    }else{
                        if(count($item->sells)>0){
                            foreach($item->sells as $sell){
                                $tmp = ErpCOPTHDB::where([['TH002',$sell->erp_sell_no],['TH003',$sell->erp_sell_sno]])->first();
                                // $model == 'return' && !empty($tmp) && $tmp->TH020 == 'Y' ? '' : $chkSellItem++;
                                // $model == 'modify' && !empty($tmp) && $tmp->TH020 == 'Y' ? $chkSellItem++ : '';
                                if(!empty($tmp)){
                                    if($model=='modify'){
                                        $tmp->TH020 == 'Y' ? $chkSellItem++ : '';
                                    }elseif($model=='return'){
                                        $tmp->TH020 == 'Y' ? '' : $chkSellItem++;
                                    }
                                }
                            }
                        }else{
                            $model == 'return' ? $chkSellItem++ : '';
                        }
                    }
                }
                return $chkSellItem;
            }
        }
        return null;
    }

    public function getInvoiceLogs(Request $request)
    {
        return response()->json(Pay2GoDB::where('order_number',$request->orderNumber)
        ->select([
            '*',
            DB::raw("(CASE WHEN type = 'allowance' THEN CONCAT(get_json,' (折讓單號:',allowance_no,' 金額：',allowance_amt,')') ELSE get_json END) as get_json"),
            DB::raw("DATE_FORMAT(create_time,'%Y-%m-%d %H:%i:%s') as createTime")
        ])->orderBy('create_time','desc')->get());
    }

    public function getAllowanceItem(Request $request)
    {
        $itmes = [];
        if(!empty($request->order_id)){
            $order = OrderDB::with('itemData','invoiceAllowance')->select([
                'id',
                'origin_country',
                'ship_to',
                'invoice_type',
                'is_invoice_no',
                'love_code',
                'amount',
                'shipping_fee',
                'parcel_tax',
                'spend_point',
                'discount',
                DB::raw("SUM(amount+shipping_fee+parcel_tax-spend_point-discount) as total"),
            ])->find($request->order_id);
            $data['taxType'] = 1;
            if($order->invoice_type == 3){
                $data['invoiceType'] = '三聯式';
            }else{
                $data['invoiceType'] = '二聯式';
                if ($order->origin_country == '台灣' && $order->ship_to != '台灣') {
                    if ($order->love_code != '') {
                        $data['invoiceType'] = '二聯式(零稅)';
                        $data['taxType'] = 2;
                    }
                }
            }
            $i=0;
            foreach($order->itemData as $item){
                if($item->is_del == 0){
                    $items[$i]['unit_name'] = $item->unit_name;
                    $items[$i]['name'] = $item->product_name;
                    $items[$i]['quantity'] = $item->quantity;
                    $items[$i]['price'] = $item->price;
                    if($data['invoiceType'] == '二聯式(零稅)'){
                        $items[$i]['amount'] = round(($item->quantity * $item->price) ,0);
                        $items[$i]['tax'] = 0;
                    }else{
                        $items[$i]['amount'] = round(($item->quantity * $item->price / 1.05) ,0);
                        $items[$i]['tax'] = round(($item->quantity * $item->price) - round(($item->quantity * $item->price / 1.05) ,0), 0);
                    }
                    $i++;
                }
            }
            if($order->shipping_fee > 0){
                $items[$i]['unit_name'] = '式';
                $items[$i]['name'] = '運費';
                $items[$i]['quantity'] = 1;
                $items[$i]['price'] = $order->shipping_fee;
                if($data['invoiceType'] == '二聯式(零稅)'){
                    $items[$i]['amount'] = round($order->shipping_fee ,0);
                    $items[$i]['tax'] = 0;
                }else{
                    $items[$i]['amount'] = round(($order->shipping_fee / 1.05) ,0);
                    $items[$i]['tax'] = round(($order->shipping_fee) - round(($order->shipping_fee / 1.05) ,0), 0);
                }
                $i++;
            }
            if($order->parcel_tax > 0){
                $items[$i]['unit_name'] = '式';
                $items[$i]['name'] = '行郵稅';
                $items[$i]['quantity'] = 1;
                $items[$i]['price'] = $order->parcel_tax;
                if($data['invoiceType'] == '二聯式(零稅)'){
                    $items[$i]['amount'] = round($order->parcel_tax ,0);
                    $items[$i]['tax'] = 0;
                }else{
                    $items[$i]['amount'] = round(($order->parcel_tax / 1.05) ,0);
                    $items[$i]['tax'] = round(($order->parcel_tax) - round(($order->parcel_tax / 1.05) ,0), 0);
                }
                $i++;
            }
            if($order->spend_point > 0){
                $items[$i]['unit_name'] = '式';
                $items[$i]['name'] = '購物金';
                $items[$i]['quantity'] = 1;
                $items[$i]['price'] = $order->spend_point;
                if($data['invoiceType'] == '二聯式(零稅)'){
                    $items[$i]['amount'] = round($order->spend_point ,0);
                    $items[$i]['tax'] = 0;
                }else{
                    $items[$i]['amount'] = round(($order->spend_point / 1.05) ,0);
                    $items[$i]['tax'] = round(($order->spend_point) - round(($order->spend_point / 1.05) ,0), 0);
                }
                $i++;
            }
            if($order->discount > 0){
                $items[$i]['unit_name'] = '式';
                $items[$i]['name'] = '折扣';
                $items[$i]['quantity'] = 1;
                $items[$i]['price'] = $order->discount;
                if($data['invoiceType'] == '二聯式(零稅)'){
                    $items[$i]['amount'] = round($order->discount ,0);
                    $items[$i]['tax'] = 0;
                }else{
                    $items[$i]['amount'] = round(($order->discount / 1.05) ,0);
                    $items[$i]['tax'] = round(($order->discount) - round(($order->discount / 1.05) ,0), 0);
                }
                $i++;
            }
            $data['id'] = $order->id;
            $data['invoiceNumber'] = $order->is_invoice_no;
            $data['orderNumber'] = $order->order_number;
            !empty($order->invoiceAllowance) ? $data['remainAmt'] = $order->invoiceAllowance->remain_amt : $data['remainAmt'] = $order->total;
            $data['items'] = $items;
            return response()->json($data);
        }
        return null;
    }

    public function allowance(Request $request)
    {
        $param = $request->all();
        if(count($param['items']) > 0){
            rsort($param['items']);
            $param['type'] = 'allowance';
            $result = AdminInvoiceJob::dispatchNow($param);
            if(strstr($result['msg'],'SUCCESS')){
                Session::put('success',$result['msg']);
            }else{
                Session::put('error',$result['msg']);
            }
        }else{
            Session::put('error',"請選擇要折讓的商品資料。");
        }
        return redirect()->back();
    }
}
