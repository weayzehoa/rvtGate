<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryCountry as CountryDB;
use App\Models\IpAddress as IpAddressDB;
use App\Models\NidinOrder as NidinOrderDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\iCarryProductLog as ProductLogDB;
use App\Models\AutoStockinProduct as AutoStockinProductDB;
use App\Models\NidinTicket as NidinTicketDB;
use App\Models\NidinTicketLog as NidinTicketLogDB;
use App\Models\NidinPayment as NidinPaymentDB;
use App\Models\NidinSetBalance as NidinSetBalanceDB;
use App\Models\NidinInvoiceLog as NidinInvoiceLogDB;
use App\Models\PurchaseExcludeProduct as PurchaseExcludeProductDB;

use App\Jobs\AdminSendEmail;
use App\Jobs\AdminInvoiceJob;
use App\Jobs\NidinOrderProcessJob;
use App\Jobs\NidinTicketWriteOffJob;
use App\Jobs\iCarryOrderSynchronizeToDigiwinJob as OrderSyncToDigiwin;
use App\Jobs\NidinServiceFeeProcessJob as NidinServiceFeeProcessJob;

use App\Traits\OrderFunctionTrait;
use App\Traits\UniversalFunctionTrait;
use App\Traits\NidinTicketFunctionTrait;
use App\Traits\ApiResponser;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DB;

class NidinOrderController extends Controller
{
    use NidinTicketFunctionTrait,OrderFunctionTrait,UniversalFunctionTrait,ApiResponser;

    // 先經過 middleware 檢查
    public function __construct()
    {
        $this->middleware('auth:gate', ['except' => ['product','pay','order','query','openTicket','writeOff','return']]);
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
        $this->allowIps = IpAddressDB::where([['admin_id',0],['is_on',1]])->select('ip')->get()->pluck('ip')->all();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M27S10';
        $appends =  $compact = $orders = [];
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
            $status = '1,2,3,4,5';
            $compact = array_merge($compact, ['status']);
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }
        //計算及item資料分拆
        $orders = $this->orderItemSplit($this->getOrderData(request(),'index','nidinOrder'));
        foreach($orders as $order){
            !empty($order->syncDate) ? $order->syncDate = str_replace('-','/',substr($order->syncDate['created_at'],0,10)) : $order->syncDate = null;
            foreach($order->items as $item){
                if(count($item->sells) > 0){
                    foreach($item->sells as $sell){
                        $item->sell_quantity += $sell->sell_quantity;
                    }
                }
            }
        }
        $compact = array_merge($compact, ['menuCode','orders','appends']);
        return view('gate.nidinOrders.index', compact($compact));
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
        //
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
        //
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

    public function product(Request $request)
    {
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $data['vendor_product_no'] = $data['digiwin_no'] = $data['vendor_id'] = $data['product_id'] = $data['name'] = $data['price'] = $data['message'] = $message = null;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $data1 = $request->all();
        if(!empty($request->getContent())){
            $getJson = $request->getContent();
            $data1 = json_decode($getJson, true);
        }
        $data = array_merge($data,$data1);
        if(count($data1) > 0){
            $log = ProductLogDB::create([
                'get_json' => json_encode($data1,JSON_UNESCAPED_UNICODE),
                'ip' => $this->loginIp,
            ]);
            if(isset($data['merchant_no']) && isset($data['merchant_key'])){
                $vendor = VendorDB::where([['merchant_no',$data['merchant_no']],['merchant_key',$data['merchant_key']]])->first();
                if(!empty($vendor)){
                    $mail['from'] = 'icarry@icarry.me'; //寄件者
                    $mail['name'] = 'iCarry 中繼系統'; //寄件者名字
                    $mail['model'] = 'nidinProductNotice';
                    $vendor->email = str_replace(' ','',str_replace(['/',';','|',':','／','；','：','｜','　','，','、'],[',',',',',',',',',',',',',',',',',',',',','],$vendor->email));
                    $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                    $mails = explode(',',$vendor->email);
                    for($i=0;$i<count($mails);$i++){
                        $email = strtolower($mails[$i]);
                        if(preg_match($pattern,$email)){
                            $mail['to'][] = $email; //收件者, 需使用陣列
                        };
                    }
                    $data['vendor_id'] = $vendor->id;
                    if(isset($data['type']) && in_array($data['type'],['create','update','delete','query','multiQuery'])){
                        if(in_array($data['type'],['update','delete','query'])){
                            if(!empty($data['vendor_product_no'])){
                                if(is_string($data['vendor_product_no'])){
                                    $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->where($productTable.'.is_del',0)
                                    ->where($vendorTable.'.id',$vendor->id)
                                    ->where($productModelTable.'.vendor_product_model_id',$data['vendor_product_no'])
                                    ->select([
                                        $productModelTable.'.digiwin_no',
                                        $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                        $productTable.'.name',
                                        $productTable.'.price',
                                        $productTable.'.status',
                                        DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                    ])->first();
                                    if(!empty($productData)){
                                        $data['digiwin_no'] = $productData->digiwin_no;
                                        $productModel = ProductModelDB::where('digiwin_no',$productData->digiwin_no)->first();
                                        $product = ProductDB::find($productModel->product_id);
                                        $data['product_id'] = $product->id;
                                        if($data['type'] == 'query'){
                                            $status = 'Success';
                                            $data['message'] = "商家商品 ".$data['vendor_product_no']." 已存在。";
                                            $message = [
                                                'result' => $data['message'],
                                                'product' => $productData,
                                            ];
                                        }elseif($data['type'] == 'delete'){
                                            //將商品資料移出自動入庫資料表
                                            $autoStockin = AutoStockinProductDB::where('digiwin_no',$productModel->digiwin_no)->first();
                                            !empty($autoStockin) ? $autoStockin->delete() : '';
                                            $product->update(['is_del' => 1]);
                                            $productModel->update(['is_del' => 1]);
                                            $status = 'Success';
                                            $mail['message'] = $data['message'] = $message = "商家商品 ".$data['vendor_product_no']." 已刪除。";
                                            //通知ACPay已被刪除
                                            $digiwinNo = $productData->digiwin_no;
                                            $mail['subject'] = "你訂案-{$digiwinNo}商品被刪除通知!!";
                                            $mail['product'] = $productData;
                                            env('APP_ENV') == 'local' ? AdminSendEmail::dispatchNow($mail) : AdminSendEmail::dispatch($mail);
                                        }elseif($data['type'] == 'update'){
                                            if(empty($data['name']) && empty($data['price'])){
                                                $data['message'] = $message = "修改商品，name 或 price 其中一個必須存在。";
                                                $code = 81;
                                            }else{
                                                $oldPrice = $product->price;
                                                $oldName = $product->name;
                                                if(!empty($data['price'])){
                                                    if($data['price'] > 0){
                                                        if(!empty($data['name'])){
                                                            if(mb_strlen($data['name'], "utf-8") > 30){
                                                                $data['message'] = $message = "name 商品名稱字不能超過30個字。";
                                                                $code = 91;
                                                            }else{
                                                                //修改金額需要重新審核
                                                                $newPrice = $data['price'];
                                                                $newName = $data['name'];
                                                                if($oldName != $data['name']){
                                                                    $data['message'] .= "舊商品名稱 $oldName 變更為 {$newName}。";
                                                                    if($oldPrice != $data['price']){
                                                                        $mail['message'] = $data['message'] .= "舊金額 $oldPrice 變更為 $newPrice 。";
                                                                        $product->update(['price' => $newPrice, 'name' => $newName, 'status' => 0]);
                                                                        $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                                        ->where($productTable.'.is_del',0)
                                                                        ->where($vendorTable.'.id',$vendor->id)
                                                                        ->where($productModelTable.'.vendor_product_model_id',$data['vendor_product_no'])
                                                                        ->select([
                                                                            $productModelTable.'.digiwin_no',
                                                                            $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                                                            $productTable.'.name',
                                                                            $productTable.'.price',
                                                                            $productTable.'.status',
                                                                            DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                                                        ])->first();

                                                                        //金額變更, 通知ACPay
                                                                        $digiwinNo = $productData->digiwin_no;
                                                                        $mail['subject'] = "你訂案-{$digiwinNo}商品名稱及金額變更通知!!";
                                                                        $mail['product'] = $productData;
                                                                        //發送email通知給ACPay
                                                                        env('APP_ENV') == 'local' ? AdminSendEmail::dispatchNow($mail) : AdminSendEmail::dispatch($mail);

                                                                        $status = 'Success';
                                                                        $message = [
                                                                            'result' => $data['message'],
                                                                            'product' => $productData,
                                                                        ];
                                                                    }else{
                                                                        $product->update(['name' => $newName]);
                                                                        $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                                        ->where($productTable.'.is_del',0)
                                                                        ->where($vendorTable.'.id',$vendor->id)
                                                                        ->where($productModelTable.'.vendor_product_model_id',$data['vendor_product_no'])
                                                                        ->select([
                                                                            $productModelTable.'.digiwin_no',
                                                                            $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                                                            $productTable.'.name',
                                                                            $productTable.'.price',
                                                                            $productTable.'.status',
                                                                            DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                                                        ])->first();
                                                                        $status = 'Success';
                                                                        $message = [
                                                                            'result' => $data['message'],
                                                                            'product' => $productData,
                                                                        ];
                                                                    }
                                                                }else{
                                                                    $newName = $oldName;
                                                                    if($oldPrice != $data['price']){
                                                                        $mail['message'] = $data['message'] = "舊金額 $oldPrice 變更為 $newPrice 。";
                                                                        $product->update(['price' => $newPrice, 'name' => $newName, 'status' => 0]);
                                                                        $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                                        ->where($productTable.'.is_del',0)
                                                                        ->where($vendorTable.'.id',$vendor->id)
                                                                        ->where($productModelTable.'.vendor_product_model_id',$data['vendor_product_no'])
                                                                        ->select([
                                                                            $productModelTable.'.digiwin_no',
                                                                            $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                                                            $productTable.'.name',
                                                                            $productTable.'.price',
                                                                            $productTable.'.status',
                                                                            DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                                                        ])->first();

                                                                        //金額變更, 通知ACPay
                                                                        $digiwinNo = $productData->digiwin_no;
                                                                        $mail['subject'] = "你訂案-{$digiwinNo}商品金額變更通知!!";
                                                                        $mail['product'] = $productData;
                                                                        //發送email通知給ACPay
                                                                        env('APP_ENV') == 'local' ? AdminSendEmail::dispatchNow($mail) : AdminSendEmail::dispatch($mail);

                                                                        $status = 'Success';
                                                                        $message = [
                                                                            'result' => $data['message'],
                                                                            'product' => $productData,
                                                                        ];
                                                                    }else{
                                                                        $data['message'] = $message = "name & price 新舊商品名稱及金額相同不變更。";
                                                                        $code = 83;
                                                                    }
                                                                }
                                                            }
                                                        }else{
                                                            //修改金額需要重新審核
                                                            $newPrice = $data['price'];
                                                            if($oldPrice != $data['price']){
                                                                $mail['message'] = $data['message'] .= "舊金額 $oldPrice 變更為 $newPrice 。";
                                                                $product->update(['price' => $newPrice, 'status' => 0]);
                                                                $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                                ->where($productTable.'.is_del',0)
                                                                ->where($vendorTable.'.id',$vendor->id)
                                                                ->where($productModelTable.'.vendor_product_model_id',$data['vendor_product_no'])
                                                                ->select([
                                                                    $productModelTable.'.digiwin_no',
                                                                    $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                                                    $productTable.'.name',
                                                                    $productTable.'.price',
                                                                    $productTable.'.status',
                                                                    DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                                                ])->first();

                                                                //金額變更, 通知ACPay
                                                                $digiwinNo = $productData->digiwin_no;
                                                                $mail['subject'] = "你訂案-{$digiwinNo}商品金額變更通知!!";
                                                                $mail['product'] = $productData;
                                                                //發送email通知給ACPay
                                                                env('APP_ENV') == 'local' ? AdminSendEmail::dispatchNow($mail) : AdminSendEmail::dispatch($mail);

                                                                $status = 'Success';
                                                                $message = [
                                                                    'result' => $data['message'],
                                                                    'product' => $productData,
                                                                ];
                                                            }else{
                                                                $data['message'] = $message = "price 修改的金額與上次相同。";
                                                                $code = 82;
                                                            }
                                                        }
                                                    }else{
                                                        $data['message'] = $message = "price 金額不可小於等於0。";
                                                        $code = 92;
                                                    }
                                                }else{
                                                    $newName = $data['name'];
                                                    if($newName != $oldName){
                                                        $data['message'] .= "舊商品名稱 $oldName 變更為 $newName 。";
                                                        $product->update(['name'=> $newName]);
                                                        $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                        ->where($productTable.'.is_del',0)
                                                        ->where($vendorTable.'.id',$vendor->id)
                                                        ->where($productModelTable.'.vendor_product_model_id',$data['vendor_product_no'])
                                                        ->select([
                                                            $productModelTable.'.digiwin_no',
                                                            $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                                            $productTable.'.name',
                                                            $productTable.'.price',
                                                            $productTable.'.status',
                                                            DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                                        ])->first();
                                                        $status = 'Success';
                                                        $message = [
                                                            'result' => $data['message'],
                                                            'product' => $productData,
                                                        ];
                                                    }else{
                                                        $data['message'] = $message = "name 新舊商品名稱相同不變更。";
                                                        $code = 82;
                                                    }

                                                }
                                            }
                                        }
                                    }else{
                                        $vendorProductNo = $data['vendor_product_no'];
                                        $data['message'] = $message = "vendor_product_no 商家商品 $vendorProductNo 不存在。";
                                        $code = 8;
                                    }
                                }else{
                                    $data['message'] = $message = "vendor_product_no 商家料號不存在。";
                                    $code = 9;
                                }
                            }else{
                                $data['message'] = $message = "vendor_product_no 商家料號不存在。";
                                $code = 9;
                            }
                        }elseif($data['type'] == 'create'){
                            if(!empty($data['vendor_product_no'])){
                                if(strlen($data['vendor_product_no']) <= 32){
                                    if(isset($data['price']) && isset($data['name'])){
                                        if(mb_strlen($data['name'], "utf-8") > 30){
                                            $data['message'] = $message = "name 商品名稱字不能超過30個字。";
                                            $code = 91;
                                        }else{
                                            $productModel = ProductModelDB::where([['is_del',0],['vendor_product_model_id',$data['vendor_product_no']]])->first();
                                            if(!empty($productModel)){
                                                $data['digiwin_no'] = $productModel->digiwin_no;
                                                $digiwinNo = $productModel->digiwin_no;
                                                $vendorProductNo = mb_substr($data['vendor_product_no'],0,20);
                                                $data['message'] = $message = "商家料號 $vendorProductNo 已經建立過商品。";
                                                $code = 93;
                                            }else{
                                                if($data['price'] > 0){
                                                    $productData['vendor_id'] = $vendor->id;
                                                    $productData['brand'] = $vendor->name;
                                                    $productData['name'] = mb_substr($data['name'], 0, 30);
                                                    $productData['category_id'] = 19;
                                                    $productData['unit_name'] = '張';
                                                    $productData['unit_name_id'] = 5;
                                                    $productData['model_type'] = 1;
                                                    $productData['product_sold_country'] = '台灣';
                                                    $productData['status'] = $productData['is_hot'] = $productData['hotel_days'] = $productData['airplane_days'] = $productData['is_del'] = $productData['direct_shipment'] = 0;
                                                    $productData['gross_weight'] = $productData['net_weight'] = $productData['type'] = $productData['model_type'] = $productData['from_country_id'] = 1;
                                                    $productData['digiwin_product_category'] = 207;
                                                    $productData['price'] = $productData['vendor_price'] = $data['price'];
                                                    $product = ProductDB::create($productData);
                                                    $data['product_id'] = $product->id;
                                                    $productModelData['vendor_product_model_id'] = $data['vendor_product_no'];
                                                    $productModelData['quantity'] = 10;
                                                    $productModelData['safe_quantity'] = 1;
                                                    $productModelData['name'] = '單一規格';
                                                    $productModelData['is_del'] = 0;
                                                    $productModelData['product_id'] = $product->id;
                                                    $productModel = ProductModelDB::create($productModelData);
                                                    //產生SKU及鼎新代碼
                                                    $productData['product_model_id'] = $productModel->id;
                                                    $output = $this->makeSku($productData);
                                                    $productModel->update($output);
                                                    $status = 'Success';
                                                    $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                    ->where($vendorTable.'.id',$vendor->id)
                                                    ->where($productModelTable.'.digiwin_no',$output['digiwin_no'])
                                                    ->select([
                                                        $productModelTable.'.id as product_model_id',
                                                        $productModelTable.'.digiwin_no',
                                                        $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                                        $productTable.'.name',
                                                        $productTable.'.price',
                                                        $productTable.'.status',
                                                        DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                                    ])->first();

                                                    //將商品資料放入自動入庫資料表用
                                                    AutoStockinProductDB::create(['digiwin_no' => $product->digiwin_no]);
                                                    //將商品放入不採購
                                                    PurchaseExcludeProductDB::create(['product_model_id' => $product->product_model_id]);
                                                    $mail['message'] = $data['message'] = '商品建立成功。';
                                                    $message = [
                                                        'result' => $data['message'],
                                                        'product' => $product,
                                                    ];

                                                    $data['digiwin_no'] = $digiwinNo = $product->digiwin_no;
                                                    $mail['subject'] = "你訂案-{$digiwinNo}商品建立通知!!";
                                                    $mail['product'] = $product;
                                                    //發送email通知給ACPay
                                                    env('APP_ENV') == 'local' ? AdminSendEmail::dispatchNow($mail) : AdminSendEmail::dispatch($mail);
                                                }else{
                                                    $data['message'] = $message = "price 金額不可小於等於0。";
                                                    $code = 92;
                                                }
                                            }
                                        }
                                    }else{
                                        $data['message'] = $message = "建立商品時，name 與 price 參數必須同時存在。";
                                        $code = 90;
                                    }
                                }else{
                                    $data['message'] = $message = "vendor_product_no 不能超過32個字元。";
                                    $code = 94;
                                }
                            }else{
                                $data['message'] = $message = "vendor_product_no 商家料號不存在。";
                                $code = 9;
                            }
                        }elseif($data['type'] == 'multiQuery'){
                            if(isset($data['vendor_product_nos'])){
                                if(is_array($data['vendor_product_nos']) && count($data['vendor_product_nos']) > 0){
                                    $data['vendor_product_no'] = join(',',$data['vendor_product_nos']);
                                    $vendorProductNos = $data['vendor_product_nos'];
                                    $productData = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->where($productTable.'.is_del',0)
                                    ->where($productModelTable.'.is_del',0)
                                    ->where($vendorTable.'.id',$vendor->id)
                                    ->whereIn($productModelTable.'.vendor_product_model_id',$vendorProductNos)
                                    ->select([
                                        $productModelTable.'.digiwin_no',
                                        $productModelTable.'.vendor_product_model_id as vendor_product_no',
                                        $productTable.'.name',
                                        $productTable.'.price',
                                        $productTable.'.status',
                                        DB::raw("(CASE WHEN status = -9 THEN '2' WHEN status = 0 THEN '1' WHEN status = -2 THEN '3' END) as status"),
                                    ])->get();
                                    $results = [];
                                    $count = count($vendorProductNos);
                                    $empty = 0;
                                    for($i=0;$i<count($vendorProductNos);$i++){
                                        foreach($productData as $product){
                                            if($product->vendor_product_no == $vendorProductNos[$i]){
                                                $results[$vendorProductNos[$i]] = $product;
                                                break;
                                            }
                                        }
                                        !isset($results[$vendorProductNos[$i]]) ? $results[$vendorProductNos[$i]] = null : '';
                                        !isset($results[$vendorProductNos[$i]]) ? $empty++ : '';
                                    }
                                    $status = 'Success';
                                    $data['message'] = "共查詢 $count 筆，$empty 筆不存在。";
                                    $message = [
                                        'result' => $data['message'],
                                        'products' => $results
                                    ];
                                }else{
                                    $data['message'] = $message = "vendor_product_nos 不存在，多筆查詢至少需要一筆資料。";
                                    $code = 71;
                                }
                            }else{
                                $data['message'] = $message = "vendor_product_nos 不存在，type 類別為 multiQuery 時，vendor_product_nos 必須存在。";
                                $code = 7;
                            }
                        }
                    }else{
                        $data['type'] = null;
                        $data['message'] = $message = "type 類別參數錯誤或不存在。";
                        $code = 990;
                    }
                }else{
                    $data['type'] = null;
                    $data['message'] = $message = "商家不存在，請檢查特店代號及特店密鑰。";
                    $code = 9999;
                }
                $log->update([
                    'post_json' => json_encode(['status'=> $status, 'code' => $code, 'message' => $message],JSON_UNESCAPED_UNICODE),
                    'type' => $data['type'],
                    'vendor_product_no' => $data['vendor_product_no'],
                    'digiwin_no' => $data['digiwin_no'],
                    'vendor_id' => $data['vendor_id'],
                    'product_id' => $data['product_id'],
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'message' => $data['message'],
                ]);
            }else{
                $data['message'] = $message = "商家代號及驗證碼不存在。";
                $code = 9999;
            }
        }else{
            $httpCode = 400;
            $message = 'No Data Input。';
        }
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }

    protected function makeSku($input){
        if(isset($input['sku'])){
            $output['sku'] = $input['sku'];
        }else{
            //sku的編碼方式 EC 0001 000001
            $output['gtin13'] = $output['sku'] = "EC" . str_pad($input['vendor_id'],5,'0',STR_PAD_LEFT) . str_pad($input['product_model_id'],6,'0',STR_PAD_LEFT);
        }

        //digiwin_no的編碼方式
        $digiwinNo="5";
        $countryId = $input['from_country_id'];
        $country = CountryDB::findOrFail($countryId);
        $digiwinNo .= $country->lang; //語言代碼 1:tw, 5:jp
        $digiwinNo .= "A".str_pad($input['vendor_id'],5,"0",STR_PAD_LEFT);

        // 找出product_models與product_id跟vendor_id關聯的總數
        $vendorProductModelCounts = ProductModelDB::where('id','<=',$input['product_model_id'])
            ->whereIn('product_id', ProductDB::where('vendor_id',$input['vendor_id'])->select('id'))
            ->count();
        $vendorProductModelCounts == 0 ? $vendorProductModelCounts++ : '';
        //鼎新編碼原則（包括單品與組合商品）
        if(substr($output['sku'],0,3)=="BOM"){
            $digiwinNo .= "B".str_pad(base_convert($vendorProductModelCounts, 10, 36),3,"0",STR_PAD_LEFT);
        }else{
            $digiwinNo .= str_pad(base_convert($vendorProductModelCounts, 10, 36),4,"0",STR_PAD_LEFT);
        }

        $output['digiwin_no'] = strtoupper($digiwinNo);
        return $output;
    }

    public function order(Request $request)
    {
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $message = null;
        $number = time();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $invoiceData = $originItems = $getData = [];
        if(!empty($request->getContent())){
            $getJson = $request->getContent();
            $getData = json_decode($getJson, true);
        }
        if(!empty($getData) && is_array($getData) && count($getData) > 0){
            $nidinOrder = NidinOrderDB::create([
                'get_json' => $getJson,
                'ip' => $this->loginIp,
            ]);
            $nidinOrderId = $nidinOrder->id;
            if(isset($getData['merchant_no'])){
                $nidinOrder->update(['merchant_no' => $getData['merchant_no']]);
                $vendor = VendorDB::where('merchant_no',$getData['merchant_no'])->first();
                if(!empty($vendor)){
                    $purchasePriceRate = $this->getNidinServiceFee($vendor->merchant_no,$vendor->merchant_key,'writeoff');
                    if(isset($getData['nidin_order_no'])){
                        strstr(env('APP_URL'),'localhost') || $this->loginIp == '60.248.153.35' ? $getData['transaction_id'] = $getData['nidin_order_no'] = $number : '';
                        strstr(env('APP_URL'),'localhost') || $this->loginIp == '60.248.153.35' ? NidinPaymentDB::create(['is_success'=>1, 'nidin_order_no' => $number, 'transaction_id' => $number, 'pay_time' => date('Y-m-d H:i:s'), 'message' => '付款成功。']) : '';
                        $nidinOrder->update(['nidin_order_no' => $getData['nidin_order_no']]);
                        if(isset($getData['transaction_id'])){
                            $nidinOrder->update(['transaction_id' => $getData['transaction_id']]);
                            //2024.07.22 開會討論, 關閉金流與訂單號碼鎖定, 只鎖定金流序號與iCarry訂單.
                            $chkPayment = NidinPaymentDB::where([['transaction_id',$getData['transaction_id']],['is_success',1]])->first();
                            if(!empty($chkPayment)){
                                $chkOrder = nidinOrderDB::where('transaction_id',$getData['transaction_id'])->whereNotNull('order_number')->first();
                                if(empty($chkOrder)){
                                    $data = $getData;
                                    $digiwinPayment = DigiwinPaymentDB::where('customer_no',$vendor->digiwin_vendor_no)->first();
                                    $data['digiwin_payment_id'] = $digiwinPayment->customer_no;
                                    $data['create_type'] = $data['pay_method'] = $digiwinPayment->create_type;
                                    $data['create_time'] = $data['pay_time'] = date('Y-m-d H:i:s');
                                    $ctime = strtotime($data['pay_time']); //付款時間
                                    $orderNumber = date("ymdHis",$ctime);
                                    for($i=1;$i<=100;$i++){ //避免重複訂單號
                                        $orderNumber = date("ymd",$ctime).substr($orderNumber,-5).rand(100,999);//12碼+2
                                        $chkOrder = OrderDB::where('order_number',$orderNumber)->first();
                                        if(!empty($chkOrder)){
                                            continue;
                                        }else{
                                            break;
                                        }
                                    }
                                    $data['vendor_id'] = $vendor->id; //同一張訂單內只能有一種商家的商品, 檢查商品時需要帶入商家id
                                    $data['order_number'] = $orderNumber;
                                    $data['origin_country'] = $data['ship_to'] = '台灣';
                                    $data['user_id'] = 68081;
                                    $data['book_shipping_date'] = $data['vendor_arrival_date'] = date('Y-m-d');
                                    $data['shipping_method'] = 6;
                                    $data['spend_point'] = $data['shipping_fee'] = $data['parcel_tax'] = $data['shipping_kg_price'] = $data['shipping_base_price'] = 0;
                                    $data['partner_order_number'] = $data['transaction_id'];
                                    $data['status'] = 5;
                                    //採購價費率以後用API直接取
                                    $data['purchasePriceRate'] = $purchasePriceRate;
                                    $data = $this->chkBuyerInvoiceData($data); //檢查訂單資訊及發票資料
                                    if(empty($data['message']) && $data['code'] == 0){
                                        $data = $this->chkItemsData($data); //檢查商品資料
                                        if(empty($data['message']) && $data['code'] == 0){
                                            $order = OrderDB::create($data);
                                            $param['nidinOrderId'] = $nidinOrder->id;
                                            $param['orderId'] = $order->id;
                                            $param['orderNumber'] = $order->order_number;
                                            $nidinOrder->update(['order_id' => $order->id, 'order_number' => $order->order_number, 'amount' => $order->amount, 'discount' => $order->discount]);
                                            $importNo = time().rand(100,999);
                                            $items = $data['items'];
                                            for($i=0;$i<count($items);$i++){
                                                $items[$i]['order_id'] = $order->id;
                                                $items[$i]['direct_shipment'] = 0;
                                                $orderItems[$i] = OrderItemDB::create($items[$i]);
                                            }
                                            //訂單開立成功後, 開票券
                                            if(!empty($order) && count($orderItems) > 0){
                                                $message = '訂單建立完成。';
                                                $ticketLog = NidinTicketLogDB::create([
                                                    'type' => '訂單開票',
                                                    'from_nidin' => $getJson,
                                                    'nidin_order_no' => $data['nidin_order_no'],
                                                    'transaction_id' => $data['transaction_id'],
                                                    'platform_no' => $data['merchant_no'],
                                                    'key' => $vendor->merchant_key,
                                                    'ip' => $this->loginIp,
                                                ]);
                                                $originItems = $getData['items'];
                                                for($c=0;$c<count($originItems);$c++){
                                                    $originItems[$c]['ticket_no'] = null; //先設定票號為空值
                                                }
                                                $openTicketData = $this->openTicketItems($nidinOrder, $data['tickets']);
                                                if(!isset($data['no_ticket'])){
                                                    $result = $this->nidinOpenTicket($nidinOrder, $openTicketData['ticketItems'], $ticketLog);
                                                    if($result['rtnCode'] == 0){
                                                        if(count($result['items']) > 0){
                                                            $resultItems = $result['items'];
                                                            for($i=0;$i<count($resultItems);$i++){
                                                                isset($resultItems[$i]['ticketNos']) && count($resultItems[$i]['ticketNos']) > 0 ? $ticketNo = $resultItems[$i]['ticketNos'][0] : $ticketNo = null;
                                                                if(!empty($ticketNo)){
                                                                    $digiwinNo = $resultItems[$i]['itemNo'];
                                                                    $setNo = $resultItems[$i]['setNo'];
                                                                    $productModel = ProductModelDB::where('digiwin_no',$digiwinNo)->first();
                                                                    $orderItem = OrderItemDB::where([['order_id',$order->id],['set_no',$setNo],['product_model_id',$productModel->id],['is_del',0]])->whereNull('ticket_no')->first();
                                                                    !empty($orderItem) ? $orderItem->update(['ticket_no' => $ticketNo]) : '';
                                                                    for($c=0;$c<count($originItems);$c++){
                                                                        //因為套票號碼與商品號碼皆相同, 須將空值填入票券號碼 否則會造成全部都是同一張票券號碼
                                                                        if($originItems[$c]['ticket_no'] == null && $originItems[$c]['set_no'] == $resultItems[$i]['setNo'] && $productModel->vendor_product_model_id == $originItems[$c]['product_num']){
                                                                            $originItems[$c]['ticket_no'] = $ticketNo;
                                                                            $originItems[$c]['ticket_start_date'] = $orderItem->ticket_start_date;
                                                                            $originItems[$c]['ticket_end_date'] = $orderItem->ticket_end_date;
                                                                            break;
                                                                        }
                                                                    }
                                                                }else{
                                                                    unset($resultItems[$i]);
                                                                }
                                                            }
                                                            if(count($resultItems) == count($originItems)){
                                                                $nidinOrder->update(['is_ticket' => 1]);
                                                                $order->update(['status' => 1]);
                                                                $message .= '開立票券完成。';
                                                            }else{
                                                                $code = 93;
                                                                $message .= '開立票券API返回票數與實際需求數不符。';
                                                            }
                                                        }else{
                                                            $code = 92;
                                                            $message .= '開立票券API返回無票券資料。';
                                                        }
                                                    }else{
                                                        $code = 91;
                                                        $message .= "開立票券API失敗。";
                                                    }
                                                }else{
                                                    $code = 91;
                                                    $message .= "開立票券API失敗。";
                                                }
                                                if($code == 0){
                                                    if(!isset($data['no_invoice'])){
                                                        $amount = round($order->amount - $order->discount,0);
                                                        $taxAmount = round($amount - round($amount/1.05,0),0);
                                                        $saleAmount = round($amount/1.05,0);
                                                        $invoiceParam['id'] = $order->id;
                                                        $invoiceParam['type'] = 'create';
                                                        $invoiceParam['model'] = 'nidinOrderOpenInvoice';
                                                        $invoiceLog = NidinInvoiceLogDB::create([
                                                            'type' => 'open',
                                                            'nidin_order_no' => $nidinOrder->nidin_order_no,
                                                            'param' => json_encode($invoiceParam,true)
                                                        ]);
                                                        $result = AdminInvoiceJob::dispatchNow($invoiceParam);
                                                        if(!empty($result) && !empty($result['info'])){
                                                            $pay2goInfo = json_decode($result['info'],true);
                                                            if(!empty($pay2goInfo['Result']) && strtoupper($pay2goInfo['Status']) == 'SUCCESS'){
                                                                $pay2goResult = json_decode($pay2goInfo['Result'],true);
                                                                if(!empty($pay2goResult['InvoiceNumber'])){
                                                                    $invoiceLog->update(['is_success' => 1]);
                                                                    $invoiceData['invoice_number'] = $pay2goResult['InvoiceNumber'];
                                                                    $invoiceData['rand'] = $pay2goResult['RandomNum'];
                                                                    $invoiceData['invoice_time'] = str_replace([' ','-',':'],['','',''],$pay2goResult['CreateTime']);
                                                                    $invoiceData['tax_amount'] = "$taxAmount";
                                                                    $invoiceData['sales_amount'] = "$saleAmount";
                                                                    $invoiceData['total_amount'] = "$amount";
                                                                    $message .= "發票開立完成。";
                                                                    $status = 'Success';
                                                                }else{
                                                                    $code = 94;
                                                                    $message .= "發票API開立發票失敗。";
                                                                }
                                                            }else{
                                                                $code = 94;
                                                                $message .= "發票API開立發票失敗。";
                                                            }
                                                        }else{
                                                            $code = 94;
                                                            $message .= "發票API開立發票失敗。";
                                                        }
                                                    }else{
                                                        $code = 94;
                                                        $message .= "發票API開立發票失敗。";
                                                    }
                                                }
                                                $data['message'] = $message;
                                                $message = [
                                                    'result' => $data['message'],
                                                    'nidin_order_no' => "$nidinOrder->nidin_order_no",
                                                    'transaction_id' => "$nidinOrder->transaction_id",
                                                    'invoice' => $invoiceData,
                                                    'items' => $originItems,
                                                ];
                                                if($code == 0 || $code == 94){
                                                    if(strstr(env('APP_URL'),'localhost')){
                                                        NidinOrderProcessJob::dispatchNow($param);
                                                    }else{
                                                        //背端處理檢查
                                                        $chkQueue = 0;
                                                        $delay = null;
                                                        $minutes = 1;
                                                        $jobName = 'NidinOrderProcessJob';
                                                        $countQueue = Redis::llen('queues:default');
                                                        $allQueues = Redis::lrange('queues:default', 0, -1);
                                                        $allDelayQueues = Redis::zrange('queues:default:delayed', 0, -1);
                                                        if(count($allQueues) > 0){
                                                            if(count($allDelayQueues) > 0){
                                                                $allDelayQueues = array_reverse($allDelayQueues);
                                                                for($i=0;$i<count($allDelayQueues);$i++){
                                                                    $job = json_decode($allDelayQueues[$i],true);
                                                                    if(strstr($job['displayName'],$jobName)){
                                                                        $commandStr = $job['data']['command'];
                                                                        if(strstr($commandStr,'s:26')){
                                                                            $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                                            $command = explode('____',$commandStr);
                                                                            $time = $command[1];
                                                                            $delay = Carbon::parse($time)->addminutes($minutes);
                                                                        }else{
                                                                            $delay = Carbon::now()->addminutes($minutes);
                                                                        }
                                                                        $chkQueue++;
                                                                        break;
                                                                    }
                                                                }
                                                            }else{
                                                                foreach($allQueues as $queue){
                                                                    $job = json_decode($queue,true);
                                                                    if(strstr($job['displayName'],$jobName)){
                                                                        $delay = Carbon::now()->addminutes($minutes);
                                                                        $chkQueue++;
                                                                    }
                                                                }
                                                            }
                                                        }else{
                                                            $queue = DB::table('jobs')->where('payload','like',"%$jobName%")->orderBy('id','desc')->first();
                                                            if(!empty($queue)){
                                                                $payload = $queue->payload;
                                                                $job = json_decode($payload,true);
                                                                $commandStr = $job['data']['command'];
                                                                if(strstr($commandStr,'s:26')){
                                                                    $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                                    $command = explode('____',$commandStr);
                                                                    $time = $command[1];
                                                                    $delay = Carbon::parse($time)->addminutes($minutes);
                                                                }else{
                                                                    $delay = Carbon::now()->addminutes($minutes);
                                                                }
                                                                $chkQueue++;
                                                            }
                                                        }
                                                        if($chkQueue > 0){
                                                            !empty($delay) ? NidinOrderProcessJob::dispatch($param)->delay($delay) : NidinOrderProcessJob::dispatch($param);
                                                        }else{
                                                            NidinOrderProcessJob::dispatch($param);
                                                        }
                                                    }
                                                }
                                            }else{
                                                $code = 9;
                                                $data['message'] = $message = "訂單建立失敗。";
                                            }
                                        }else{
                                            $message = $data['message'];
                                            is_array($data['message']) ? $data['message'] = $data['message']['result'] : '';
                                            $code = $data['code'];
                                        }
                                    }else{
                                        $message = $data['message'];
                                        is_array($data['message']) ? $data['message'] = $data['message']['result'] : '';
                                        $code = $data['code'];
                                    }
                                }else{
                                    $data['message'] = $message = "nidin_order_no 已存在訂單，無法再建立訂單。";
                                    $code = 13;
                                }
                            }else{
                                $data['message'] = $message = "transaction_id 金流交易未成功，無法建立訂單。";
                                $code = 12;
                            }
                        }else{
                            $data['message'] = $message = "transaction_id 金流交易序號不存在。";
                            $code = 11;
                        }
                    }else{
                        $data['message'] = $message = "商家訂單編號不存在。";
                        $code = 1;
                    }
                }else{
                    $data['message'] = $message = "商家不存在，請檢查特店代號。";
                    $code = 9999;
                }
            }else{
                $data['message'] = $message = "商家代號不存在，請檢查特店代號。";
                $code = 9999;
            }
            NidinOrderDB::find($nidinOrderId)->update(['message' => $data['message']]);
        }else{
            $code = $httpCode = 400;
            $data['message'] = $message = 'No Data Input。';
        }
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }

    private function chkBuyerInvoiceData($data)
    {
        $data['code'] = 0;
        if(!empty($data['buyer_name']) && !empty($data['buyer_email'])){
            $data['buyer_name'] = $this->removeEmoji($data['buyer_name']);
            if(mb_strlen($data['buyer_name'], "utf-8") <= 30){
                if($this->chkEmail($data['buyer_email']) == false || $this->chkMobile($data['buyer_mobile']) == false){
                    $data['message'] = "buyer_email 或 buyer_mobile 格式錯誤。";
                    $data['code'] = 22;
                }else{
                    if(!empty($data['amount']) && (!empty($data['discount']) || $data['discount'] == 0) && is_numeric($data['amount']) && is_numeric($data['discount'])){
                        $data['receiver_name'] = $data['buyer_name'];
                        $data['receiver_email'] = $data['buyer_email'];
                        $receiverTel = $data['buyer_mobile'];
                        $key = env('APP_AESENCRYPT_KEY');
                        $data['receiver_tel'] = DB::raw("AES_ENCRYPT('$receiverTel', '$key')");
                        if(isset($data['invoice_type']) && in_array($data['invoice_type'],[1,2,3])){
                            if($data['invoice_type'] == 3){
                                $data['print_mark'] = 'Y';
                                if(isset($data['invoice_number']) || isset($data['invoice_title'])){
                                    if(!empty($data['invoice_number']) && !empty($data['invoice_title'])){
                                        $data['invoice_title'] = $this->removeEmoji($data['invoice_title']);
                                        $data['carrier_type'] = null;
                                        $data['invoice_type'] = 3;
                                        $data['invoice_sub_type'] = 3;
                                        $preg = '/^[0-9]{8}$/i';
                                        if(!preg_match($preg,$data['invoice_number'])){
                                            $data['message'] = "invoice_number 三聯式發票統編只能8個數字。";
                                            $data['code'] = 332;
                                        }
                                    }else{
                                        $data['message'] = "invoice_number && invoice_title 三聯式發票統編/抬頭不可為空值。";
                                        $data['code'] = 331;
                                    }
                                }else{
                                    $data['message'] = "invoice_number 三聯式發票統編/抬頭不存在。";
                                    $data['code'] = 33;
                                }
                            }elseif($data['invoice_type'] == 2){
                                $data['print_mark'] = 'N';
                                $data['invoice_type'] = 2;
                                $data['invoice_sub_type'] = 2;
                                if(isset($data['carrier_type']) && in_array($data['carrier_type'],[0,1,2])){
                                    if(in_array($data['carrier_type'],[1,2])){
                                        if(isset($data['carrier_num'])){
                                            if(!empty($data['carrier_num'])){
                                                if($data['carrier_type'] == 1){ //手機條碼
                                                    $data['carrier_type'] = 0;
                                                    $preg = '/^[0-9A-Z\/\.\-\_]+$/';
                                                    $data['carrier_num'] = str_replace(['\\','+',' '],['','_','_'],$data['carrier_num']);
                                                    if(strlen($data['carrier_num']) != 8 || substr($data['carrier_num'],0,1) != '/' || !preg_match($preg,$data['carrier_num'])){
                                                        $data['message'] = "carrier_num 手機條碼必須為8碼，且第一碼必須為/符號且由0-9A-Z與.-+符號組成。";
                                                        $data['code'] = 325;
                                                    }
                                                    $data['carrier_num'] = $data['carrier_num'] = str_replace('_','+',$data['carrier_num']);
                                                }elseif($data['carrier_type'] == 2){ //自然人憑證
                                                    $data['carrier_type'] = 1;
                                                    $preg1 = '/^[A-Z]+$/';
                                                    $preg2 = '/^[0-9]{14}$/i';
                                                    if(strlen($data['carrier_num']) != 16 || !preg_match($preg1,substr($data['carrier_num'],0,2)) || !preg_match($preg2,substr($data['carrier_num'],2))){
                                                        $data['message'] = "carrier_num 自然人憑證號碼必須為16碼，且前兩碼為大寫英文字後14碼為數字。";
                                                        $data['code'] = 324;
                                                    }
                                                }
                                            }else{
                                                if($data['carrier_type'] == 1){
                                                    $data['message'] = "carrier_num 手機載具資料不可為空值。";
                                                    $data['code'] = 323;
                                                }elseif($data['carrier_type'] == 2){
                                                    $data['message'] = "carrier_num 自然人憑證資料不可為空值。";
                                                    $data['code'] = 322;
                                                }
                                            }
                                        }else{
                                            $data['message'] = "carrier_num 載具資料不存在。";
                                            $data['code'] = 321;
                                        }
                                    }else{
                                        $data['carrier_type'] = null;
                                    }
                                }else{
                                    $data['message'] = "carrier_type 載具類別不存在/錯誤，0:不使用載具 2:手機條碼 3:自然人憑證。";
                                    $data['code'] = 32;
                                }
                            }elseif($data['invoice_type'] == 1){
                                $data['print_mark'] = 'N';
                                $data['invoice_type'] = 2;
                                $data['invoice_sub_type'] = 2;
                                $data['carrier_type'] = null;
                                $preg = '/^[0-9]{3,7}$/i';
                                if(isset($data['love_code'])){
                                    if(!empty($data['love_code'])){
                                        if(!preg_match($preg,$data['love_code'])){
                                            $data['message'] = "love_code 捐贈碼只能3-7個數字。";
                                            $data['code'] = 31;
                                        }
                                    }else{
                                        $data['love_code'] = 86888;
                                    }
                                }else{
                                    $data['love_code'] = 86888;
                                }
                            }
                        }else{
                            $data['message'] = "invoice_type 發票類別不存在/錯誤，1:捐贈 2:二聯式 3:三聯式。";
                            $data['code'] = 30;
                        }
                    }else{
                        $data['message'] = "amount 總金額與 discount 折扣必填且須為數字。";
                        $data['code'] = 23;
                    }
                }
            }else{
                $data['message'] = "購買人資料超過30個字。";
                $data['code'] = 21;
            }
        }else{
            $data['message'] = "購買人/eMail資料未填寫。";
            $data['code'] = 20;
        }
        return $data;
    }

    private function chkItemsData($data)
    {
        $result = [];
        $data['code'] = 0;
        $key = env('APP_AESENCRYPT_KEY');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $ticketStartDate = Carbon::today()->toDateString();
        $data['vendor_id'] == 729 ? $ticketEndDate = Carbon::today()->addDays(183)->toDateString() : $ticketEndDate = Carbon::today()->addDays(365)->toDateString();
        if(isset($data['items'])){
            $items = $data['items'];
            $total = $amount = $discount = $chkItem = 0;
            //檢查套票號碼及數量是否皆為1
            for($i=0;$i<count($items);$i++){
                $data['tickets'][$i]['product_num'] = $items[$i]['product_num'];
                $data['tickets'][$i]['set_no'] = $items[$i]['set_no'];
                $data['tickets'][$i]['qty'] = $items[$i]['qty'];
                $data['tickets'][$i]['memo'] = substr($items[$i]['memo'],0,600);
                $data['tickets'][$i]['expStartDate'] = str_replace('-','',$ticketStartDate);
                $data['tickets'][$i]['expEndDate'] = str_replace('-','',$ticketEndDate);
                $amount += $items[$i]['price'] * $items[$i]['qty'];
                $discount += $items[$i]['discount'] * $items[$i]['qty'];
                //先檢查商品
                $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->where($productModelTable.'.vendor_product_model_id',$items[$i]['product_num'])
                ->where($productTable.'.status',-9)
                ->where($productTable.'.is_del',0)
                ->where($productModelTable.'.is_del',0)
                ->where($vendorTable.'.id',$data['vendor_id'])
                ->select([
                    $productModelTable.'.gtin13',
                    $productModelTable.'.sku',
                    $productModelTable.'.digiwin_no',
                    $productModelTable.'.id as product_model_id',
                    $productModelTable.'.vendor_product_model_id',
                    $productTable.'.name as product_name',
                    $productTable.'.unit_name',
                    $productTable.'.price',
                    $productTable.'.gross_weight',
                    $productTable.'.net_weight',
                    $productTable.'.direct_shipment',
                    $vendorTable.'.id as vendor_id',
                    $vendorTable.'.name as vendor_name',
                    $vendorTable.'.digiwin_vendor_no as digiwin_payment_id',
                ])->first();
                if($items[$i]['qty'] != 1){
                    $chkItem++;
                    $result[]['message'] = "第".($i+1)."個，商品 ".$items[$i]['product_num'].'，數量不等於1。';
                }
                if(empty($items[$i]['memo'])){
                    $chkItem++;
                    $result[]['message'] = "第".($i+1)."個，商品備註必填。";
                }
                if(!empty($items[$i]['set_no'])){
                    $chkSetNumber = OrderItemDB::where('set_no',$items[$i]['set_no'])->first();
                    if(!empty($chkSetNumber)){
                        $chkItem++;
                        $result[]['message'] = "第".($i+1)."個，商品 ".$items[$i]['product_num']." 套票號碼".$items[$i]['set_no']." 已存在。";
                    }
                }else{
                    $chkItem++;
                    $result[]['message'] = "第".($i+1)."個，商品 ".$items[$i]['product_num'].'，套票號碼不存在。';
                }
                if(!empty($product)){
                    if($product->price == $items[$i]['price']){
                        foreach($product->toArray() as $key => $value){
                            $data['items'][$i][$key] = $value;
                        }
                        $data['items'][$i]['quantity'] = $items[$i]['qty'];
                        //( 原價 - discount ) * ( 1 - 費率)
                        $data['items'][$i]['purchase_price'] = round(($items[$i]['price'] - $items[$i]['discount']) * (1 - $data['purchasePriceRate']),4);
                        $data['items'][$i]['discount'] = $items[$i]['discount'];
                        $data['items'][$i]['shipping_memo'] = '電子郵件';
                        $data['items'][$i]['ticket_start_date'] = $ticketStartDate;
                        $data['items'][$i]['ticket_end_date'] = $ticketEndDate;
                    }else{
                        $chkItem++;
                        $result[]['message'] = '第'.($i+1).'個，商品金額 '.$items[$i]['price'].' 與商品審核金額 '.$product->price.' 不同。';
                    }
                }else{
                    $chkItem++;
                    $result[]['message'] = '第'.($i+1).'個，'.$items[$i]['product_num'].' 商品尚未建立、未審核通過或被刪除。';
                }
            }
            // 2024.07.22 會議決定關閉檢查套票折扣.
            //檢查套票折扣是否過大
            // $tmp = collect($data['items'])->groupBy('set_no')->all();
            // $x = 0;
            // foreach($tmp as $setNo => $value){
            //     $setDiscount = 0;
            //     $setprice = 0;
            //     foreach($value as $v){
            //         $setDiscount += $v['discount'];
            //         $setprice += $v['price'];
            //     }
            //     $sets[$x]['set_no'] = $setNo;
            //     $sets[$x]['set_discount'] = (INT)$setDiscount;
            //     $sets[$x]['set_price'] = (INT)$setprice;
            //     $x++;
            // }
            // for($i=0;$i<count($sets);$i++){
            //     $setNo = $sets[$i]['set_no'];
            //     if(($sets[$i]['set_discount'] / $sets[$i]['set_price']) > 0.5){
            //         $chkItem++;
            //         $percent = ($sets[$i]['set_discount'] / $sets[$i]['set_price']) * 100;
            //         $result[]['message'] = "$setNo 套票折扣大於50%";
            //     }
            // }
            $total = $amount - $discount;
            if($amount != $data['amount'] || $discount != $data['discount']){
                $data['message'] = "商品總金額 $amount 及商品折扣金額 $discount 與訂單總金額 ".$data['amount']." 及訂單折扣金額 ".$data['discount']."不符。";
                $data['code'] = 41;
            }elseif($chkItem > 0){
                $data['message']['result'] = "items 商品資料錯誤。";
                $data['message']['items'] = $result;
                $data['code'] = 40;
            }
        }else{
            $data['message'] = "items 商品資料不存在。";
            $data['code'] = 4;
        }
        return $data;
    }

    public function query(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $message = null;
        $now = date('Y-m-d H:i:s');
        $writeOffDate = date('Y-m-d');
        $importNo = time().rand(100,999);
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $nidinOrderTable = env('DB_DATABASE').'.'.(new NidinOrderDB)->getTable();
        $items = $getData = [];
        if(!empty($request->getContent())){
            $getJson = $request->getContent();
            $getData = json_decode($getJson, true);
        }
        if(!empty($getData) && is_array($getData) && count($getData) > 0){
            if(isset($getData['type']) && in_array($getData['type'],['order','return','ticket','invalid'])){
                if($getData['type'] == 'ticket'){
                    if(isset($getData['items']) && count($getData['items']) > 0){
                        if(count($getData['items']) <= 200){
                            $itemData = $getData['items'];
                            $orderItems = OrderItemDB::join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                            ->whereIn('ticket_no',$itemData)
                            ->select([
                                $orderItemTable.'.*',
                                $productModelTable.'.vendor_product_model_id',
                                $vendorTable.'.merchant_no',
                            ])->get();
                            for($i=0;$i<count($itemData);$i++){
                                $items[$i]['ticket_no'] = $itemData[$i];
                                $items[$i]['status'] = $items[$i]['return_date'] = $items[$i]['writeoff_date'] = $items[$i]['end_date'] = $items[$i]['start_date'] = $items[$i]['open_date'] = $items[$i]['discount'] = $items[$i]['price'] = $items[$i]['product_name'] = $items[$i]['vendor_product_no'] = $items[$i]['merchant_no'] = null;
                                foreach($orderItems as $orderItem){
                                    if($orderItem->ticket_no == $itemData[$i]){
                                        $status = '未核銷';
                                        !empty($orderItem->writeoff_date) ? $status = '已核銷' : '';
                                        !empty($orderItem->return_date) ? $status = '已退貨' : '';
                                        $items[$i]['status'] = $status;
                                        $items[$i]['return_date'] = $orderItem->return_date;
                                        $items[$i]['writeoff_date'] = $orderItem->writeoff_date;
                                        $items[$i]['end_date'] = $orderItem->ticket_end_date;
                                        $items[$i]['start_date'] = $orderItem->ticket_start_date;
                                        $items[$i]['open_date'] = $orderItem->ticket_start_date;
                                        $items[$i]['discount'] = (INT)$orderItem->discount;
                                        $items[$i]['price'] = (INT)$orderItem->price;
                                        $items[$i]['product_name'] = $orderItem->product_name;
                                        $items[$i]['vendor_product_no'] = $orderItem->vendor_product_model_id;
                                        $items[$i]['merchant_no'] = $orderItem->merchant_no;
                                        break;
                                    }
                                }
                                empty($items[$i]['status']) ? $items[$i]['status'] = '查無該票券資料' : '';
                            }
                            $data['message'] = "票券資料查詢完成。";
                            $message = [
                                'result' => $data['message'],
                                'items' => $items,
                            ];
                            $status = 'Success';
                        }else{
                            $data['message'] = $message = '查詢票券資料超過200張。';
                            $code = 3;
                        }
                    }else{
                        $data['message'] = $message = 'items 票券資料不存在。';
                        $code = 2;
                    }
                }else{
                    if(isset($getData['nidin_order_no'])){
                        $nidinOrder = NidinOrderDB::with('order','order.items')
                        ->where('nidin_order_no',$getData['nidin_order_no'])->first();
                        if(!empty($nidinOrder)){
                            if(!empty($nidinOrder->order) && !empty($nidinOrder->transaction_id)){
                                if($getData['type'] == 'order'){
                                    $order = $nidinOrder->order;
                                    $items = $order->items;
                                    $i=0;
                                    if(count($items) > 0){
                                        foreach($items as $item){
                                            // $itemData[$i] = $item->toArray();
                                            $itemData[$i]['product_num'] = $item->vendor_product_model_id;
                                            $itemData[$i]['set_no'] = $item->vendor_product_model_id;
                                            $itemData[$i]['description'] = $item->product_name;
                                            $itemData[$i]['qty'] = 1;
                                            $itemData[$i]['price'] = $item->price;
                                            $itemData[$i]['discount'] = $item->discount;
                                            $itemData[$i]['amount'] = $item->price;
                                            $itemData[$i]['ticket_no'] = $item->ticket_no;
                                            $itemData[$i]['ticket_start_date'] = $item->ticket_start_date;
                                            $itemData[$i]['ticket_end_date'] = $item->ticket_end_date;
                                            $itemData[$i]['writeoff_date'] = $item->writeoff_date;
                                            $itemData[$i]['return_date'] = $item->return_date;
                                            $i++;
                                        }
                                    }else{
                                        $itemData = [];
                                    }
                                    if(!empty($order->is_invoice_no)){
                                        $invoiceData['invoice_number'] = $order->is_invoice_no;
                                        $invoiceData['rand'] = $order->invoice_rand;
                                        $invoiceData['invoice_time'] = $order->invoice_time;
                                        $invoiceData['tax_amount'] = round(round(($order->amount - $order->discount) / 1.05,0) * 0.05,0);
                                        $invoiceData['sales_amount'] = round(($order->amount - $order->discount) / 1.05,0);
                                        $invoiceData['total_amount'] = $order->amount - $order->discount;
                                    }else{
                                        $invoiceData = [];
                                    }
                                    $data['message'] = "訂單查詢完成。";
                                    $message = [
                                        'result' => $data['message'],
                                        'nidin_order_no' => "$nidinOrder->nidin_order_no",
                                        'transaction_id' => "$nidinOrder->transaction_id",
                                        'invoice' => $invoiceData,
                                        'items' => $itemData,
                                    ];
                                    $status = 'Success';
                                }elseif($getData['type'] == 'return' || $getData['type'] == 'invalid'){
                                    if(isset($getData['items']) && count($getData['items']) > 0){
                                        if(isset($getData['return_service_fee']) && in_array($getData['return_service_fee'],[0,1])){
                                            if($getData['return_service_fee'] == 1){
                                                if(empty($getData['return_service_rate'])){
                                                    $data['message'] = $message = "return_service_fee = 1 時，消費者付退貨手續費率必須提供，且必須大於0。";
                                                    $code = 61;
                                                }
                                            }else{
                                                // $getData['return_service_rate'] = 0;
                                                $getData['return_service_rate'] = $this->getNidinServiceFee($vendor->merchant_no,$vendor->merchant_key,'invalid');
                                            }
                                            if($code == 0){
                                                $data = $this->chkReturnTicketItems($nidinOrder,$getData['items'],$getData['return_service_fee'],$getData['return_service_rate']);
                                                if(empty($data['message']) && $data['code'] == 0){
                                                    $data['message'] = ($getData['type'] == 'invalid' ? "作廢查詢完成。" : "退貨查詢完成。");
                                                    $message = [
                                                        'result' => $data['message'],
                                                        'nidin_order_no' => "$nidinOrder->nidin_order_no",
                                                        'transaction_id' => "$nidinOrder->transaction_id",
                                                        'return_price' => $data['return_price'],
                                                        'return_discount' => $data['return_discount'],
                                                        'return_amount' => $data['return_amount'],
                                                        'return_service_fee' => $data['return_service_fee'],
                                                        'return_service_rate' => $data['return_service_rate'],
                                                        'return_service_fee_total' => $data['return_service_fee_total'],
                                                        'items' => $data['return_items'],
                                                    ];
                                                    $status = 'Success';
                                                }else{
                                                    $message = $data['message'];
                                                    is_array($data['message']) ? $data['message'] = $data['message']['result'] : '';
                                                    $code = $data['code'];
                                                }
                                            }
                                        }else{
                                            $data['message'] = $message = 'return_service_fee 退貨服務費設定錯誤，1:消費者 0:你訂。';
                                            $code = 6;
                                        }
                                    }else{
                                        $data['message'] = $message = 'items 退貨資料不存在。';
                                        $code = 5;
                                    }
                                }
                            }else{
                                $data['message'] = $message = 'transaction_id 金流交易失敗/nidin_order_no 訂單未建立成功。';
                                $code = 4;
                            }
                        }else{
                            $data['message'] = $message = 'nidin_order_no 訂單不存在。';
                            $code = 3;
                        }
                    }else{
                        $data['message'] = $message = 'nidin_order_no 商家訂單號碼必須存在。';
                        $code = 2;
                    }
                }
            }else{
                $data['message'] = $message = 'type 類別不存在/錯誤，訂單查詢 = order, 退貨查詢 = return, 票券查詢 = ticket';
                $code = 1;
            }
        }else{
            $code = $httpCode = 400;
            $data['message'] = $message = 'No Data Input。';
        }
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }

    public function writeOff(Request $request)
    {
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $message = null;
        $now = date('Y-m-d H:i:s');
        $writeOffDate = date('Y-m-d');
        $importNo = time().rand(100,999);
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $fromAcpay = $getData = [];
        if(!empty($request->getContent())){
            $getJson = $request->getContent();
            $getData = json_decode($getJson, true);
        }
        if(!empty($getData) && is_array($getData) && count($getData) > 0){
            $log = NidinTicketLogDB::create([
                'type' => '核銷',
                'from_nidin' => $getJson,
                'ip' => $this->loginIp,
            ]);
            if(isset($getData['type']) && $getData['type'] == 'writeOff'){
                if(isset($getData['items']) && count($getData['items']) > 0){
                    $data = $this->writeOffTicketItems($getData['items'], $importNo);
                    if(empty($data['message']) && $data['code'] == 0){
                        $ticketItems = $data['ticketItems'];
                        if(count($ticketItems) > 0){
                            if(count($ticketItems) >= 100){
                                $nidinTickets = array_chunk($ticketItems,100);
                                for($i=0;$i<count($nidinTickets);$i++){
                                    NidinTicketDB::insert($nidinTickets[$i]);
                                }
                            }else{
                                NidinTicketDB::insert($ticketItems);
                            }
                            $nidinTickets = NidinTicketDB::where('import_no',$importNo)->get();
                            $i=0;
                            $results = $setNos = [];
                            foreach($nidinTickets as $ticket){
                                $tickItem = [
                                    'ticketNo' => $ticket->ticket_no,
                                    'merchantNo' => $ticket->merchant_no,
                                    'handler' => 'Nidin',
                                    'timestamp' =>date('YmdHis'),
                                ];
                                ksort($tickItem);
                                $vendor = VendorDB::where('merchant_no',$ticket->merchant_no)->first();
                                $result = $this->nidinWriteOffTicket($tickItem, $vendor->merchant_key, $log);
                                $fromAcpay[] = json_encode($result);
                                $results[$i]['ticket_no'] = $ticket->ticket_no;
                                if(!empty($result) && $result['rtnCode'] == 0){
                                    $ticket->update(['writeoff_time' => $now, 'memo' => '核銷成功。']);
                                    $orderItem = OrderItemDB::where('ticket_no',$ticket->ticket_no)->first();
                                    $setNos[] = $orderItem->set_no;
                                    $orderItem->update(['writeoff_date' => $writeOffDate]);
                                    $results[$i]['message'] = '核銷成功。';
                                }else{
                                    $results[$i]['message'] = '核銷失敗。';
                                }
                                $i++;
                            }
                            //檢查套票號碼是否close
                            if(count($setNos) > 0){
                                $setNos = array_unique($setNos);
                                sort($setNos);
                                for($i=0;$i<count($setNos);$i++){
                                    $this->chkSetNoStatus($setNos[$i]);
                                }
                            }
                            $status = 'success';
                            $data['message']['result'] = '核銷處理完成。';
                            $data['message']['items'] = $results;
                            $message = $data['message'];
                            $data['message'] = '核銷處理完成。';

                            if(strstr(env('APP_URL'),'localhost')){
                                NidinTicketWriteOffJob::dispatchNow($importNo);
                            }else{
                                //背端處理檢查
                                $chkQueue = 0;
                                $delay = null;
                                $minutes = 1;
                                $jobName = 'NidinTicketWriteOffJob';
                                $countQueue = Redis::llen('queues:default');
                                $allQueues = Redis::lrange('queues:default', 0, -1);
                                $allDelayQueues = Redis::zrange('queues:default:delayed', 0, -1);
                                if(count($allQueues) > 0){
                                    if(count($allDelayQueues) > 0){
                                        $allDelayQueues = array_reverse($allDelayQueues);
                                        for($i=0;$i<count($allDelayQueues);$i++){
                                            $job = json_decode($allDelayQueues[$i],true);
                                            if(strstr($job['displayName'],$jobName)){
                                                $commandStr = $job['data']['command'];
                                                if(strstr($commandStr,'s:26')){
                                                    $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                    $command = explode('____',$commandStr);
                                                    $time = $command[1];
                                                    $delay = Carbon::parse($time)->addminutes($minutes);
                                                }else{
                                                    $delay = Carbon::now()->addminutes($minutes);
                                                }
                                                $chkQueue++;
                                                break;
                                            }
                                        }
                                    }else{
                                        foreach($allQueues as $queue){
                                            $job = json_decode($queue,true);
                                            if(strstr($job['displayName'],$jobName)){
                                                $delay = Carbon::now()->addminutes($minutes);
                                                $chkQueue++;
                                            }
                                        }
                                    }
                                }else{
                                    $queue = DB::table('jobs')->where('payload','like',"%$jobName%")->orderBy('id','desc')->first();
                                    if(!empty($queue)){
                                        $payload = $queue->payload;
                                        $job = json_decode($payload,true);
                                        $commandStr = $job['data']['command'];
                                        if(strstr($commandStr,'s:26')){
                                            $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                            $command = explode('____',$commandStr);
                                            $time = $command[1];
                                            $delay = Carbon::parse($time)->addminutes($minutes);
                                        }else{
                                            $delay = Carbon::now()->addminutes($minutes);
                                        }
                                        $chkQueue++;
                                    }
                                }
                                if($chkQueue > 0){
                                    !empty($delay) ? NidinTicketWriteOffJob::dispatch($importNo)->delay($delay) : NidinTicketWriteOffJob::dispatch($importNo);
                                }else{
                                    NidinTicketWriteOffJob::dispatch($importNo);
                                }
                            }
                        }
                    }else{
                        $message = $data['message'];
                        is_array($data['message']) ? $data['message'] = $data['message']['result'] : '';
                        $code = $data['code'];
                    }
                }else{
                    $data['message'] = $message = 'items 核銷資料不存在。';
                    $code = 2;
                }
            }else{
                $data['message'] = $message = 'type 類別不存在/錯誤，核銷 = writeOff';
                $code = 1;
            }
            $log->update(['from_acpay' => join(',',$fromAcpay),'to_nidin' => json_encode($message,true),'message' => $data['message']]);
        }else{
            $code = $httpCode = 400;
            $data['message'] = $message = 'No Data Input。';
        }
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }

    private function writeOffTicketItems($items, $importNo)
    {
        $ticketItems = $result = [];
        $chkItem = $data['code'] = 0;
        $repeat = null;
        $key = env('APP_AESENCRYPT_KEY');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $nidinOrderTable = env('DB_DATABASE').'.'.(new NidinOrderDB)->getTable();
        if(isset($items)){
            $tmp = [];
            for($i=0;$i<count($items);$i++){
                $tmp[] = $items[$i];
            }
            $arrayUnique = array_unique($tmp);
            $repeatArray = array_diff_assoc($tmp,$arrayUnique);
            $repeat = join(',',$repeatArray);
            if(empty($repeat)){
                for($i=0;$i<count($items);$i++){
                    if(isset($items[$i]) && !empty($items[$i])){
                        $ticketNo = $items[$i];
                        $orderItem = OrderItemDB::join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                        ->join($nidinOrderTable,$nidinOrderTable.'.order_id',$orderItemTable.'.order_id')
                        ->where('ticket_no',$items[$i])
                        ->select([
                            $orderItemTable.'.*',
                            $nidinOrderTable.'.transaction_id',
                            $vendorTable.'.merchant_no',
                            $productModelTable.'.vendor_product_model_id as product_num',
                        ])->first();
                        if(!empty($orderItem)){
                            if(!empty($orderItem->writeoff_date)){
                                $chkItem++;
                                $result[] = '第'.($i+1)."筆 票券號碼 $ticketNo 已被核銷。";
                            }elseif(!empty($orderItem->return_date)){
                                $chkItem++;
                                $result[] = '第'.($i+1)."筆 票券號碼 $ticketNo 已被退貨。";
                            }else{
                                $ticketItems[] = [
                                    'import_no' => $importNo,
                                    'type' => 'writeOff',
                                    'merchant_no' => $orderItem->merchant_no,
                                    'transaction_id' => $orderItem->transaction_id,
                                    'product_num' => $orderItem->product_num,
                                    'set_no' => $orderItem->set_no,
                                    'description' => $orderItem->product_name,
                                    'ticket_no' => $orderItem->ticket_no,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
                            }
                        }else{
                            $chkItem++;
                            $result[] = '第'.($i+1)."筆 票券號碼 $ticketNo 無開票紀錄。";
                        }
                    }else{
                        $chkItem++;
                        $result[] = '第'.($i+1).'筆 票券號碼不可為空值。';
                    }
                }
                if($chkItem > 0){
                    $data['message']['result'] = "items 資料錯誤。";
                    $data['message']['items'] = $result;
                    $data['code'] = 4;
                }else{
                    $data['ticketItems'] = $ticketItems;
                }
            }else{
                $data['message'] = "items 票券號碼重複。重複號碼：$repeat";
                $data['code'] = 3;
            }
        }else{
            $data['message'] = "items 核銷資料不存在。";
            $data['code'] = 2;
        }
        return $data;
    }

    private function openTicketItems($nidinOrder,$items)
    {
        $result = [];
        $chkItem = $data['code'] = 0;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        if(isset($items)){
            !empty($nidinOrder->order) ? $orderId = $nidinOrder->order->id : $orderId = null;
            if(!empty($orderId)){
                for($i=0;$i<count($items);$i++){
                    $chkData = 0; $msg = null;
                    foreach($items[$i] as $key => $value){
                        if($key != 'set_no' && empty($value)){
                            $chkData++;
                            $chkItem++;
                            $msg .= $key.' ';
                        }
                    }
                    if($chkData > 0 && !empty($msg)){
                        $result[] = '第'.($i+1).'筆商品 '.$msg.' 欄位不可為空值。';
                    }else{
                        if((is_numeric($items[$i]['expStartDate']) && strlen($items[$i]['expStartDate']) == 8) && (is_numeric($items[$i]['expEndDate']) && strlen($items[$i]['expEndDate']) == 8)){
                            $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->where($productTable.'.status',-9)
                            ->where($productTable.'.is_del',0)
                            ->where($productModelTable.'.is_del',0)
                            ->where($productModelTable.'.vendor_product_model_id',$items[$i]['product_num'])
                            ->select([
                                $productModelTable.'.id',
                            ])->first();
                            if(!empty($productModel)){
                                $orderItem = OrderItemDB::whereNull('ticket_no')
                                ->where([['order_id',$orderId],['product_model_id',$productModel->id],['set_no',$items[$i]['set_no']],['is_del',0]])
                                ->first();
                                if(!empty($orderItem)){
                                    $expStartDate = $items[$i]['expStartDate'];
                                    $expEndDate = $items[$i]['expEndDate'];
                                    strstr($items[$i]['memo'],'http') ? $items[$i]['memo'] = str_replace([':','/'],['%3A','%2F'],$items[$i]['memo']) : '';
                                    $itemMemo = substr($items[$i]['memo'],0,600);
                                    //非套票 多張
                                    if(empty($items[$i]['set_no']) && $items[$i]['qty'] > 1){
                                        for($c=0;$c<$items[$i]['qty'];$c++){
                                            $item = [
                                                'autoSettlement' => "Y",
                                                'count' => (INT)$orderItem->quantity,
                                                'expStartDate' => "$expStartDate",
                                                'expEndDate' => "$expEndDate",
                                                'issuedType' => "voucher",
                                                'issuerId' => "acpay",
                                                'itemAmount' => ($orderItem->price - $orderItem->discount) * $orderItem->quantity,
                                                'itemMemo' => !empty($itemMemo) ? $itemMemo : "$orderItem->id",
                                                'itemName' => mb_substr($orderItem->product_name,0,78),
                                                'itemNo' => $orderItem->digiwin_no,
                                                'merchantNo' => $nidinOrder->vendor->merchant_no,
                                                'setNo' => $orderItem->set_no,
                                            ];
                                            ksort($item);
                                            $ticketItems[] = $item;
                                        }
                                    }else{ //單張
                                        $item = [
                                            'merchantNo' => $nidinOrder->vendor->merchant_no,
                                            'issuedType' => "voucher",
                                            'issuerId' => "acpay",
                                            'autoSettlement' => "Y",
                                            'count' => (INT)$orderItem->quantity,
                                            'expStartDate' => "$expStartDate",
                                            'expEndDate' => "$expEndDate",
                                            'itemName' => mb_substr($orderItem->product_name,0,78),
                                            'itemNo' => $orderItem->digiwin_no,
                                            'itemAmount' => ($orderItem->price - $orderItem->discount) * $orderItem->quantity,
                                            'itemMemo' => !empty($itemMemo) ? $itemMemo : "$orderItem->id",
                                            'setNo' => $orderItem->set_no,
                                        ];
                                        ksort($item);
                                        $ticketItems[] = $item;
                                    }
                                }else{
                                    $chkItem++;
                                    $result[] = '第'.($i+1).'筆商品 查無交易資料或已開立票券號碼。';
                                }
                            }else{
                                $chkItem++;
                                $result[] = '第'.($i+1).'筆商品 expStartDate/expEndDate 須為8個數字。';
                            }
                        }else{
                            $chkItem++;
                            $result[] = '第'.($i+1).'筆商品 查無資料。';
                        }
                    }
                }
                if($chkItem > 0){
                    $data['message']['result'] = "items 商品資料錯誤。";
                    $data['message']['items'] = $result;
                    $data['code'] = 50;
                }else{
                    $data['ticketItems'] = $ticketItems;
                }
            }

        }else{
            $data['message'] = "商品資料不存在。";
            $data['code'] = 5;
        }
        return $data;
    }

    public function return(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $message = null;
        $now = date('Y-m-d H:i:s');
        $writeOffDate = date('Y-m-d');
        $importNo = time().rand(100,999);
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $getData = [];
        if(!empty($request->getContent())){
            $getJson = $request->getContent();
            $getData = json_decode($getJson, true);
        }
        if(!empty($getData) && is_array($getData) && count($getData) > 0){
            if(isset($getData['type']) && in_array($getData['type'],['confirm','invalid'])){
                $log = NidinTicketLogDB::create([
                    'type' => $getData['type'] == 'confirm' ? '消費者退貨' : '你訂作廢',
                    'from_nidin' => $getJson,
                    'ip' => $this->loginIp,
                ]);
                if(isset($getData['nidin_order_no'])){
                    $nidinOrder = NidinOrderDB::with('order','order.items')
                    ->where('nidin_order_no',$getData['nidin_order_no'])->first();
                    $nidinOrder = nidinOrderDB::with('vendor','order','order.items')->where('nidin_order_no',$getData['nidin_order_no'])->first();
                    if(!empty($nidinOrder)){
                        $vendor = $nidinOrder->vendor;
                        $log->update(['transaction_id' => $nidinOrder->transaction_id, 'platform_no' => $nidinOrder->merchant_no, 'key' => $nidinOrder->vendor->merchant_key]);
                        if(!empty($nidinOrder->order) && !empty($nidinOrder->transaction_id)){
                            $orderId = $nidinOrder->order->id;
                            $invoiceType = $nidinOrder->order->invoice_type;
                            if(isset($getData['items']) && count($getData['items']) > 0){
                                if(isset($getData['return_service_fee']) && in_array($getData['return_service_fee'],[0,1])){
                                    if($getData['return_service_fee'] == 1){
                                        if(empty($getData['return_service_rate'])){
                                            $data['message'] = $message = "return_service_fee = 1 時，消費者付退貨手續費率必須提供，且必須大於0。";
                                            $code = 61;
                                        }
                                    }else{
                                        // $getData['return_service_rate'] = 0;
                                        $getData['return_service_rate'] = $this->getNidinServiceFee($vendor->merchant_no,$vendor->merchant_key,'invalid');
                                    }
                                    if($code == 0){
                                        $data = $this->chkReturnTicketItems($nidinOrder,$getData['items'],$getData['return_service_fee'],$getData['return_service_rate']);
                                        if(empty($data['message']) && $data['code'] == 0){
                                            $returnItems = $data['return_items'];
                                            $returnSets = $data['return_sets'];
                                            //作廢票券
                                            for($i=0;$i<count($returnItems);$i++){
                                                $invalidTickets[$i]['ticketNo'] = $returnItems[$i]['ticket_no'];
                                                $returnItems[$i]['return_amount'] > 0 ? $invalidTickets[$i]['refundAmount'] = $returnItems[$i]['return_amount'] : $invalidTickets[$i]['refundAmount'] = 0;
                                            }
                                            $result = $this->nidinInvalidTicket($nidinOrder, $invalidTickets, $log);
                                            if($result['rtnCode'] == 0){
                                                if($data['return_amount'] > 0){
                                                    $y=0;
                                                    for($i=0;$i<count($returnItems);$i++){
                                                        $orderItem = OrderItemDB::where([['order_id',$orderId],['ticket_no',$returnItems[$i]['ticket_no']]])->first();
                                                        $ticketNos[] = $returnItems[$i]['ticket_no'];
                                                        if($returnItems[$i]['return_amount'] > 0){
                                                            $invoiceItems[$y]['unit_name'] = '張';
                                                            $invoiceItems[$y]['name'] = $orderItem->product_name;
                                                            $invoiceItems[$y]['quantity'] = 1;
                                                            if($invoiceType == 2){
                                                                $invoiceItems[$y]['price'] = round($returnItems[$i]['return_amount'],0);
                                                                $invoiceItems[$y]['tax'] = 0;
                                                            }else{
                                                                $invoiceItems[$y]['price'] = round($returnItems[$i]['return_amount']/1.05,0);
                                                                $invoiceItems[$y]['tax'] = round($returnItems[$i]['return_amount'] - $invoiceItems[$i]['price'],0);
                                                            }
                                                            $y++;
                                                        }
                                                    }
                                                    if(count($invoiceItems) > 0){
                                                        $invoiceParam['type'] = 'allowance';
                                                        $invoiceParam['id'] = [$orderId];
                                                        $invoiceParam['admin_name'] = '中繼系統';
                                                        $invoiceParam['admin_id'] = 1;
                                                        $invoiceParam['taxType'] = 1;
                                                        $invoiceParam['items'] = $invoiceItems;

                                                        $invoiceLog = NidinInvoiceLogDB::create([
                                                            'type' => 'allowance',
                                                            'nidin_order_no' => $nidinOrder->nidin_order_no,
                                                            'param' => json_encode($invoiceParam,true)
                                                        ]);

                                                        $result = AdminInvoiceJob::dispatchNow($invoiceParam);
                                                        if(!empty($result['allowanceNo'])){
                                                            $invoiceLog->update(['is_success' => 1]);
                                                            $data['message'] = "退貨完成。發票折讓完成。";
                                                            $message = [
                                                                'result' => $data['message'],
                                                                'nidin_order_no' => "$nidinOrder->nidin_order_no",
                                                                'transaction_id' => "$nidinOrder->transaction_id",
                                                                'invoice_allowance_no' => $result['allowanceNo'],
                                                                'return_price' => $data['return_price'],
                                                                'return_discount' => $data['return_discount'],
                                                                'return_service_fee' => $data['return_service_fee'],
                                                                'return_service_fee_total' => $data['return_service_fee_total'],
                                                                'return_amount' => $data['return_amount'],
                                                                'items' => $returnItems,
                                                            ];
                                                            $status = 'Success';
                                                        }else{
                                                            $data['message'] = "退貨完成。發票折讓失敗。";
                                                            $message = [
                                                                'result' => $data['message'],
                                                                'nidin_order_no' => "$nidinOrder->nidin_order_no",
                                                                'transaction_id' => "$nidinOrder->transaction_id",
                                                                'return_price' => $data['return_price'],
                                                                'return_discount' => $data['return_discount'],
                                                                'return_service_fee' => $data['return_service_fee'],
                                                                'return_service_fee_total' => $data['return_service_fee_total'],
                                                                'return_amount' => $data['return_amount'],
                                                                'invoice_allowance_no' => null,
                                                                'items' => $data['return_items'],
                                                            ];
                                                        }
                                                    }
                                                }else{
                                                    $data['message'] = "退貨完成。退款金額小於等於0不折讓發票。";
                                                    $message = [
                                                        'result' => $data['message'],
                                                        'nidin_order_no' => "$nidinOrder->nidin_order_no",
                                                        'transaction_id' => "$nidinOrder->transaction_id",
                                                        'invoice_allowance_no' => null,
                                                        'return_price' => $data['return_price'],
                                                        'return_discount' => $data['return_discount'],
                                                        'return_service_fee' => $data['return_service_fee'],
                                                        'return_service_fee_total' => $data['return_service_fee_total'],
                                                        'return_amount' => $data['return_amount'],
                                                        'items' => $returnItems,
                                                    ];
                                                    $status = 'Success';
                                                }

                                                //更新OrderItem資料
                                                for($i=0;$i<count($returnItems);$i++){
                                                    $orderItem = OrderItemDB::where([['order_id',$orderId],['ticket_no',$returnItems[$i]['ticket_no']]])->update(['return_date' => date('Y-m-d'), 'return_amount' => $returnItems[$i]['return_amount'], 'return_service_fee' => $returnItems[$i]['return_service_fee']]);
                                                }

                                                //檢查訂單內item是否全部退貨及重新計算訂單amount與discount
                                                $order = OrderDB::with('items')->find($orderId);
                                                $orderNumber = $order->order_number;
                                                $chkOrderCancel = $this->chkOrderItemStatus($order);
                                                if($chkOrderCancel == true){
                                                    $order->update(['status' => -1]);
                                                }else{
                                                    $order->update(['amount' => $order->amount - $data['return_price'], 'discount' => $order->discount - $data['return_discount']]);
                                                    //更新OrderItem資料
                                                    for($i=0;$i<count($returnItems);$i++){
                                                        $orderItem = OrderItemDB::where([['order_id',$orderId],['ticket_no',$returnItems[$i]['ticket_no']]])->update(['is_del' => 1]);
                                                    }
                                                }

                                                //紀錄票券Balance
                                                for($i=0;$i<count($returnSets);$i++){
                                                    $nidinSetBalance = NidinSetBalanceDB::where('set_no',$returnSets[$i]['set_no'])->first();
                                                    if(!empty($nidinSetBalance)){
                                                        $nidinSetBalance->update([
                                                            'total_balance' => $returnSets[$i]['total_balance'],
                                                            'arrears' => $returnSets[$i]['arrears'],
                                                            'balance' => $returnSets[$i]['balance'],
                                                            'remain' => $returnSets[$i]['remain'],
                                                            'is_close' => $returnSets[$i]['is_close'],
                                                            'close_date' => $returnSets[$i]['is_close'] == 1 ? date('Y-m-d') : null,
                                                        ]);
                                                    }else{
                                                        $nidinSetBalance = NidinSetBalanceDB::create([
                                                            'total_balance' => $returnSets[$i]['total_balance'],
                                                            'arrears' => $returnSets[$i]['arrears'],
                                                            'set_no' => $returnSets[$i]['set_no'],
                                                            'set_qty' => $returnSets[$i]['set_qty'],
                                                            'balance' => $returnSets[$i]['balance'],
                                                            'remain' => $returnSets[$i]['remain'],
                                                            'is_close' => $returnSets[$i]['is_close'],
                                                            'close_date' => $returnSets[$i]['is_close'] == 1 ? date('Y-m-d') : null,
                                                        ]);
                                                    }
                                                    if($returnSets[$i]['is_close'] == 0){
                                                        $this->chkSetNoStatus($returnSets[$i]['set_no']);
                                                    }
                                                }

                                                //同步鼎新
                                                $param['id'] = [$orderId];
                                                $param['admin_name'] = '中繼系統';
                                                $param['admin_id'] = 1;
                                                strstr(env('APP_URL'),'localhost') ? $result = OrderSyncToDigiwin::dispatchNow($param) : OrderSyncToDigiwin::dispatch($param);

                                                //開立退貨手續費的應收應付資料
                                                $returnServiceParam['returnServiceFeeTotal'] = $data['return_service_fee_total'];
                                                $returnServiceParam['returnServiceRate'] = $data['return_service_rate'];
                                                $returnServiceParam['returnServiceFee'] = $data['return_service_fee'];
                                                $returnServiceParam['returnTicketNos'] = $data['return_service_ticket_no'];
                                                $returnServiceParam['orderId'] = $orderId;
                                                $returnServiceParam['vendorId'] = $vendor->id;
                                                env('APP_ENV') == 'local' ? $result = NidinServiceFeeProcessJob::dispatchNow($returnServiceParam) : $result = NidinServiceFeeProcessJob::dispatch($returnServiceParam);

                                                //發送信件給icarryop 與 sales
                                                $mail['from'] = 'icarry@icarry.me'; //寄件者
                                                $mail['name'] = 'iCarry 中繼系統'; //寄件者名字
                                                $mail['model'] = 'nidinOrderReturn';
                                                strstr(env('APP_URL'),'localhost') ? $mail['to'] = [env('TEST_MAIL_ACCOUNT')] : $mail['to'] = ['icarryop@icarry.me','sales@icarry.me'];
                                                $mail['subject'] = "你訂商品退貨通知!! 訂單號碼: $orderNumber ";
                                                $mail['returnItems'] = $returnItems;
                                                env('APP_ENV') == 'local' ? AdminSendEmail::dispatchNow($mail) : AdminSendEmail::dispatch($mail);
                                            }else{
                                                $data['message'] = $message = '票券API批次作廢失敗。';
                                                $code = 8;
                                            }
                                        }else{
                                            $message = $data['message'];
                                            is_array($data['message']) ? $data['message'] = $data['message']['result'] : '';
                                            $code = $data['code'];
                                        }
                                    }
                                }else{
                                    $data['message'] = $message = 'return_service_fee 退貨服務費設定錯誤，1:消費者 0:你訂。';
                                    $code = 6;
                                }
                            }else{
                                $data['message'] = $message = 'items 退貨資料不存在。';
                                $code = 5;
                            }
                        }else{
                            $data['message'] = $message = 'transaction_id 金流交易失敗/nidin_order_no 訂單未建立成功。';
                            $code = 4;
                        }
                    }else{
                        $data['message'] = $message = 'nidin_order_no 訂單不存在。';
                        $code = 3;
                    }
                }else{
                    $data['message'] = $message = 'nidin_order_no 商家訂單號碼必須存在。';
                    $code = 2;
                }
                $log->update(['to_nidin' => json_encode($message,true),'message' => $data['message']]);
            }else{
                $data['message'] = $message = 'type 類別不存在/錯誤，消費者退貨:confirm, 你訂作廢票券:invalid';
                $code = 1;
            }
        }else{
            $code = $httpCode = 400;
            $data['message'] = $message = 'No Data Input。';
        }
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }

    public function chkReturnTicketItems($nidinOrder,$items,$serviceFee,$serviceFeeRate)
    {
        $returnCounts = $sets = $result = [];
        $returnTotal = $returnAmount = $chkItem = $data['code'] = 0;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $orderId = $nidinOrder->order->id;
        $vendor = $nidinOrder->vendor;
        $data['return_service_rate'] = $serviceFeeRate;
        $data['return_service_fee'] = ($serviceFee == 1 ? '消費者付退貨手續費' : '你訂付退貨手續費');
        if($data['code'] == 0){
            $data['return_service_fee_total'] = $data['return_amount'] = $data['return_price'] = $data['return_discount'] = 0;
            if(count($items) > 0){
                 for($i=0;$i<count($items);$i++){
                    $ticketNo = $items[$i];
                    $orderItem = OrderItemDB::where([['order_id',$orderId],['ticket_no',$ticketNo]])->first();
                    if(!empty($orderItem)){
                        if(!empty($orderItem->writeoff_date)){
                            $chkItem++;
                            $result[]['message'] = '第'.($i+1).'個，'.$ticketNo.' 已被核銷。';
                        }elseif(!empty($orderItem->return_date)){
                            $chkItem++;
                            $result[]['message'] = '第'.($i+1).'個，'.$ticketNo.' 已退貨。';
                        }
                    }else{
                        $chkItem++;
                        $result[]['message'] = '第'.($i+1).'個，'.$ticketNo.' 不存在。';
                    }
                }
                if($chkItem > 0){
                    $data['message']['result'] = "items 商品資料錯誤。";
                    $data['message']['items'] = $result;
                    $data['code'] = 50;
                }else{
                    for($i=0;$i<count($items);$i++){
                        $orderItem = OrderItemDB::where([['order_id',$orderId],['ticket_no',$items[$i]]])->first();
                        $ticketItems[$i]['ticket_no'] = $orderItem->ticket_no;
                        $sets[$i] = $ticketItems[$i]['set_no'] = $orderItem->set_no;
                        $ticketItems[$i]['price'] = (INT)$orderItem->price;
                        $ticketItems[$i]['qty'] = 1;
                        $ticketItems[$i]['discount'] = (INT)$orderItem->discount;
                        $serviceFee == 1 ? $ticketItems[$i]['return_service_fee'] = floor(($ticketItems[$i]['price'] - $ticketItems[$i]['discount']) * $serviceFeeRate): $ticketItems[$i]['return_service_fee'] = 0;
                    }
                    //計算套票號碼退了幾張
                    $tmp = collect($ticketItems)->groupBy('set_no')->all();
                    $x = 0;
                    foreach($tmp as $setNo => $value){
                        $count = 0;
                        $price = 0;
                        foreach($value as $v){
                            $count++;
                            $price += $v['price'];
                        }
                        $returnCounts[$x]['set_no'] = $setNo;
                        $returnCounts[$x]['return_count'] = (INT)$count;
                        $returnCounts[$x]['return_price'] = (INT)$price;
                        $x++;
                    }
                    //本次退貨套票號碼資料
                    $sets =  array_unique($sets);
                    sort($sets);
                    for($i=0;$i<count($sets);$i++){
                        $tmp = OrderItemDB::where('set_no',$sets[$i])
                        ->select([
                            DB::raw("SUM(price - discount) as set_amount"),
                            DB::raw("SUM(price) as set_price"),
                            DB::raw("SUM(discount) as set_discount"),
                            DB::raw("SUM(1) as count")
                        ])->groupBy('set_no')->first();
                        !empty($tmp) ? $setCount = (INT)$tmp->count : $setCount = 0;
                        !empty($tmp) ? $setDiscount = (INT)$tmp->set_discount : $setDiscount = 0;
                        !empty($tmp) ? $setAmount = (INT)$tmp->set_amount : $setAmount = 0;
                        !empty($tmp) ? $setPrice = (INT)$tmp->set_price : $setPrice = 0;
                        $tmp = NidinSetBalanceDB::where('set_no',$sets[$i])->first();
                        !empty($tmp) ? $balance = $tmp->balance : $balance = null;
                        !empty($tmp) ? $returnSets[$i]['total_balance'] = $tmp->total_balance : $returnSets[$i]['total_balance'] = $setAmount;
                        !empty($tmp) ? $returnSets[$i]['arrears'] = $tmp->arrears : $returnSets[$i]['arrears'] = null;
                        !empty($tmp) ? $remain = $tmp->remain :  $remain = null;
                        $returnSets[$i]['set_no'] = $sets[$i]; //套票號碼
                        $returnSets[$i]['set_qty'] = $setCount; //套票數量
                        $returnSets[$i]['return_discount'] = (INT)$setDiscount; //取消套票折扣
                        $returnSets[$i]['return_service_fee'] = $returnSets[$i]['is_close'] = $returnSets[$i]['remain'] = $returnSets[$i]['return_all'] = $returnSets[$i]['return_amount'] = $returnSets[$i]['return_price'] = $returnSets[$i]['balance'] = $returnSets[$i]['discount_avg'] = 0;
                        for($c=0;$c<count($returnCounts);$c++){
                            if($returnCounts[$c]['set_no'] == $sets[$i]){
                                if($returnCounts[$c]['return_count'] == $setCount){ //整套全退
                                    $returnSets[$i]['return_all'] = 1;
                                    $returnSets[$i]['is_close'] = 1;
                                }else{
                                    empty($balance) ? $returnSets[$i]['discount_avg'] = ((INT)$setDiscount / $returnCounts[$c]['return_count']) : $returnSets[$i]['discount_avg'] = 0;
                                    !empty($balance) ? $returnSets[$i]['balance'] = $balance : $returnSets[$i]['balance'] = $setPrice - $returnCounts[$c]['return_price'];
                                    !empty($balance) ? $returnSets[$i]['return_discount'] = 0 : '';
                                    !empty($remain) ? $returnSets[$i]['remain'] = $remain : '';
                                }
                            }
                        }
                    }
                    //找出item相關退款資料
                    for($i=0;$i<count($ticketItems);$i++){
                        for($j=0;$j<count($returnSets);$j++){
                            if($ticketItems[$i]['set_no'] == $returnSets[$j]['set_no']){
                                $ticketItems[$i]['return_all'] = $returnSets[$j]['return_all'];
                                if($ticketItems[$i]['return_all'] == 1){
                                    $ticketItems[$i]['return_discount'] = $ticketItems[$i]['discount'];
                                }else{
                                    $ticketItems[$i]['return_discount'] = round($returnSets[$j]['discount_avg'],0);
                                }
                            }
                        }
                        $ticketItems[$i]['return_price'] = $ticketItems[$i]['price'];
                        $ticketItems[$i]['return_amount'] = $ticketItems[$i]['price'] - $ticketItems[$i]['return_discount'];
                        unset($ticketItems[$i]['return_all']);
                    }
                    //校正item裡面的return_discount
                    for($i=0;$i<count($returnSets);$i++){
                        if($returnSets[$i]['return_all'] == 0){
                            $allDiscount = $returnSets[$i]['return_discount'];
                            $itemsAllDiscount = 0;
                            for($j=0;$j<count($ticketItems);$j++){
                                if($ticketItems[$j]['set_no'] == $returnSets[$i]['set_no']){
                                    $itemsAllDiscount += $ticketItems[$j]['return_discount'];
                                }
                            }
                            if($allDiscount > $itemsAllDiscount){ //逐筆加1
                                $diff = $allDiscount - $itemsAllDiscount;
                                for($j=0;$j<count($ticketItems);$j++){
                                    if($ticketItems[$j]['set_no'] == $returnSets[$i]['set_no']){
                                        $ticketItems[$j]['return_discount']++;
                                        $diff--;
                                        if($diff==0){
                                            break;
                                        }
                                    }
                                }
                            }elseif($allDiscount < $itemsAllDiscount){ //逐筆減1
                                $diff = $itemsAllDiscount - $allDiscount;
                                for($j=0;$j<count($ticketItems);$j++){
                                    if($ticketItems[$j]['set_no'] == $returnSets[$i]['set_no']){
                                        $ticketItems[$j]['return_discount']--;
                                        $diff--;
                                        if($diff==0){
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //重新計算並檢查當下可退款餘額
                    for($i=0;$i<count($returnSets);$i++){
                        $returnAmountDiff = $chkItem = 0;
                        for($j=0;$j<count($ticketItems);$j++){
                            if($ticketItems[$j]['set_no'] == $returnSets[$i]['set_no']){
                                $ticketItems[$j]['return_amount'] = $ticketItems[$j]['return_price'] - $ticketItems[$j]['return_discount'];
                                $returnSets[$i]['return_price'] += $ticketItems[$j]['return_price'];
                                !empty($balance) ? $returnSets[$i]['balance'] = $returnSets[$i]['balance'] - $ticketItems[$j]['return_amount'] : '';
                                $serviceFee == 1 ? $ticketItems[$j]['return_amount'] = $ticketItems[$j]['return_amount'] - $ticketItems[$j]['return_service_fee'] : '';
                                if(empty($returnSets[$i]['arrears']) || $returnSets[$i]['arrears'] != 0){
                                    $returnSets[$i]['arrears'] += $ticketItems[$j]['return_amount'];
                                    if($returnSets[$i]['arrears'] < 0){
                                        $returnSets[$i]['arrears'] < 0 ? $ticketItems[$j]['return_amount'] = 0 : '';
                                    }elseif($returnSets[$i]['arrears'] > 0){
                                        $ticketItems[$j]['return_amount'] = $returnSets[$i]['arrears'];
                                        $returnSets[$i]['arrears'] = 0;
                                    }elseif($returnSets[$i]['arrears'] == 0){
                                        $ticketItems[$j]['return_amount'] = 0;
                                    }
                                }
                                $ticketItems[$j]['return_amount'] > 0 ? $returnSets[$i]['total_balance'] -= $ticketItems[$j]['return_amount'] : '';
                                $ticketItems[$j]['return_amount'] < 0 ? $ticketItems[$j]['return_amount'] = 0 : ''; //小於0則歸0
                                $returnSets[$i]['remain'] += $ticketItems[$j]['price'] - $ticketItems[$j]['discount'] - $ticketItems[$j]['return_amount'];  //額外餘額
                                $returnSets[$i]['return_amount'] += $ticketItems[$j]['return_amount'];  //實際退款總和計算
                                $returnSets[$i]['return_service_fee'] += $ticketItems[$j]['return_service_fee'];  //退款手續費總和計算
                            }
                        }
                        //大於0時需要校正回來
                        $returnSets[$i]['arrears'] > 0 ? $returnSets[$i]['arrears'] = 0 : '';
                        $data['return_price'] += $returnSets[$i]['return_price'];
                        $data['return_discount'] += $returnSets[$i]['return_discount'];
                        $data['return_amount'] += $returnSets[$i]['return_amount'];
                        $data['return_service_fee_total'] += $returnSets[$i]['return_service_fee'];
                    }
                    //檢查退款金額為0時是否有手續費, 有則擋掉
                    $returnServiceFee = $chkServiceFee = 0; $setNos = [];
                    for($i=0;$i<count($returnSets);$i++){
                        $setCount = $chk = 0;
                        if($returnSets[$i]['return_amount'] <= 0){
                            for($j=0;$j<count($ticketItems);$j++){
                                if($ticketItems[$j]['set_no'] == $returnSets[$i]['set_no']){
                                    $setCount++;
                                    if($ticketItems[$j]['return_service_fee'] > 0 && $ticketItems[$j]['return_amount'] <= 0){
                                        $chk++;
                                    }
                                }
                            }
                            if($chk == $setCount){
                                $chkServiceFee++;
                                $setNos[] = $returnSets[$i]['set_no'];
                                $returnServiceFee += $returnSets[$i]['return_service_fee'];
                            }
                        }
                    }
                    //計算你訂要付的作廢手續費
                    if($serviceFee == 0){
                        $returnServiceFee = 0;
                        for($j=0;$j<count($ticketItems);$j++){
                            $returnServiceFee += number_format($serviceFeeRate * ($ticketItems[$j]['price'] - $ticketItems[$j]['discount']),4);
                        }
                        $data['return_service_fee_total'] = $returnServiceFee;
                    }

                    //找出作廢的票號
                    $returnTicketNos = [];
                    for($j=0;$j<count($ticketItems);$j++){
                        $returnTicketNos[] = $ticketItems[$j]['ticket_no'];
                    }
                    $data['return_service_ticket_no'] = count($returnTicketNos) > 0 ? join(',',$returnTicketNos) : null;
                    if($chkServiceFee > 0){
                        $data['message'] = "套票號碼 ".join(',',$setNos)." 退款金額為 0，且消費者共需付退貨手續費 $returnServiceFee 元，無法退貨。";
                        $data['code'] = 7;
                    }else{
                        $data['return_items'] = $ticketItems;
                        $data['return_sets'] = $returnSets;
                    }
                }
            }else{
                $data['message'] = "items 退貨資料不存在。";
                $data['code'] = 5;
            }
        }
        return $data;
    }

    private function chkOrderItemStatus($order)
    {
        $items = $order->items;
        $chkReturn = $chkWriteOff = 0;
        $itemsCount = count($order->items);
        foreach($items as $item){
            !empty($item->writeoff_date) ? $chkWriteOff++ : '';
            !empty($item->return_date) ? $chkReturn++ : '';
        }
        if($chkReturn == $itemsCount){
            return true;
        }else{
            return false;
        }
    }

    private function chkSetNoStatus($setNo)
    {
        $tmp = OrderItemDB::where('set_no',$setNo)
        ->select([
            DB::raw("SUM(price - discount) as set_amount"),
            DB::raw("SUM(price) as set_price"),
            DB::raw("SUM(discount) as set_discount"),
            DB::raw("SUM(1) as count"),
            DB::raw("SUM(CASE WHEN writeoff_date is not null THEN 1 WHEN return_date is not null THEN 1 ELSE 0 END) as chkSetCount"),
            DB::raw("SUM(CASE WHEN writeoff_date is not null THEN 1 ELSE 0 END) as writeoffCount"),
        ])->groupBy('set_no')->first();
        if(!empty($tmp)){
            if($tmp->count == $tmp->chkSetCount || $tmp->count == $tmp->writeoffCount){
                $nidinSetBalance = NidinSetBalanceDB::where('set_no',$setNo)->first();
                if(!empty($nidinSetBalance)){
                    $nidinSetBalance->update(['is_close' => 1, 'close_date' => date('Y-m-d')]);
                }else{
                    $nidinSetBalance = NidinSetBalanceDB::create([
                        'set_no' => $setNo,
                        'set_qty' => $tmp->count,
                        'balance' => $tmp->set_price,
                        'total_balance' => $tmp->set_amount,
                        'remain' => 0,
                        'is_close' => 1,
                        'close_date' => date('Y-m-d')
                    ]);
                }
            }
        }
    }

    public function invalid(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $message = null;
        $message = '正在開發中';
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }

    public function openTicket(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $message = null;
        $getData = [];
        if(!empty($request->getContent())){
            $getJson = $request->getContent();
            $getData = json_decode($getJson, true);
        }
        if(!empty($getData) && is_array($getData) && count($getData) > 0){
            $log = NidinTicketLogDB::create([
                'type' => '開票',
                'from_nidin' => $getJson,
                'ip' => $this->loginIp,
            ]);
            if(isset($getData['nidin_order_no']) && isset($getData['transaction_id']) && isset($getData['platform_no']) && isset($getData['key'])){
                $log->update([
                    'nidin_order_no' => $getData['nidin_order_no'],
                    'transaction_id' => $getData['transaction_id'],
                    'platform_no' => $getData['platform_no'],
                    'key' => $getData['key'],
                ]);
                $chkPayment = NidinPaymentDB::where([['nidin_order_no',$getData['nidin_order_no']],['transaction_id',$getData['transaction_id']],['is_success',1]])->first();
                if(!empty($chkPayment)){
                    $transactionId = $getData['transaction_id'];
                    $nidinOrder = nidinOrderDB::with('payment','vendor','order','order.itemData')->where([['nidin_order_no',$getData['nidin_order_no']],['transaction_id',$getData['transaction_id']]])->whereNotNull('order_number')->first();
                    if(!empty($nidinOrder)){
                        $nidinOrderNo = $getData['nidin_order_no'];
                        $vendor = VendorDB::where([['merchant_no',$getData['platform_no'],['merchant_key',$getData['key']]]])->first();
                        if(!empty($vendor)){
                            $merchantNo = $platformNo = $getData['platform_no'];
                            $key = $getData['key'];
                            if(isset($getData['items'])){
                                //整理開票資料
                                $data = $this->openTicketItems($nidinOrder, $getData['items']);
                                if(empty($data['message']) && $data['code'] == 0){
                                    $result = $this->nidinOpenTicket($nidinOrder, $data['ticketItems'], $log);
                                    // $result = $this->testTicketData();
                                    if(!empty($result)){
                                        if($result['rtnCode'] == 0){
                                            if(count($result['items']) > 0){
                                                $resultItems = $result['items'];
                                                $originItems = $getData['items'];
                                                for($i=0;$i<count($resultItems);$i++){
                                                    $ticketNo = $resultItems[$i]['ticketNos'][0];
                                                    $digiwinNo = $resultItems[$i]['itemNo'];
                                                    $setNo = $resultItems[$i]['setNo'];
                                                    $productModel = ProductModelDB::where('digiwin_no',$digiwinNo)->first();
                                                    $orderItem = OrderItemDB::where([['order_id',$nidinOrder->order->id],['set_no',$setNo],['product_model_id',$productModel->id],['is_del',0]])->whereNull('ticket_no')->first();
                                                    !empty($orderItem) ? $orderItem->update(['ticket_no' => $ticketNo]) : '';
                                                    for($c=0;$c<count($originItems);$c++){
                                                        if($originItems[$c]['set_no'] == $resultItems[$i]['setNo'] && $productModel->vendor_product_model_id == $originItems[$c]['product_num']){
                                                            $originItems[$c]['ticket_no'] = $ticketNo;
                                                        }
                                                    }
                                                }
                                                $data['message']['result'] = '開票完成。';
                                                $data['message']['items'] = $originItems;
                                                $message = $data['message'];
                                                $data['message'] = $data['message']['result'];
                                                $status = 'Success';
                                            }else{
                                                $data['message'] = $message = '開票成功，開票API未返回票券資訊。';
                                                $code = 53;
                                            }
                                        }else{
                                            $data['message'] = $message = '開票失敗，開票API錯誤訊息: '.$result['rtnMsg'];
                                            $code = 52;
                                        }
                                    }else{
                                        $data['message'] = $message = '開票API失敗，請聯繫系統管理員。';
                                        $code = 51;
                                    }
                                }else{
                                    $message = $data['message'];
                                    is_array($data['message']) ? $data['message'] = $data['message']['result'] : '';
                                    $code = $data['code'];
                                }
                            }else{
                                $data['message'] = $message = 'items 商品資料不存在。';
                                $code = 5;
                            }
                        }else{
                            $data['message'] = $message = 'platform_no && key 錯誤。';
                            $code = 4;
                        }
                    }else{
                        $data['message'] = $message = 'nidin_order_no 商家訂單未建立成功，無法開票。';
                        $code = 3;
                    }
                }else{
                    $data['message'] = $message = 'transaction_id 金流交易序號付款未成功，無法開票。';
                    $code = 2;
                }
            }else{
                $data['message'] = $message = 'nidin_order_no/transaction_id/platform_no/key 必須存在。';
                $code = 1;
            }
            $log->update(['to_nidin' => json_encode($message,true),'message' => $data['message']]);
        }else{
            $code = $httpCode = 400;
            $data['message'] = $message = 'No Data Input。';
        }
        return response()->json([ 'status'=> $status, 'code' => $code, 'message' => $message], $httpCode);
    }
}
