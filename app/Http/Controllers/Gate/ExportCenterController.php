<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\ExportCenter as ExportCenterDB;
use App\Models\iCarryCategory as CategoryDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\Statement as StatementDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;
use App\Models\AdminKeypassLog as AdminKeypassLogDB;
use DB;
use Carbon\Carbon;
use Session;
use Hash;
use App\Export\ProductExport;
use App\Jobs\AdminExportJob;

class ExportCenterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:gate');
    }

    public function index()
    {
        $appends = [];
        $compact = [];
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
        }
        if (!isset($list)) {
            $list = 15;
            $compact = array_merge($compact, ['list']);
        }
        $exports = new ExportCenterDB();
        if(auth('gate')->user()->id != 40){
            $exports = $exports->join('admins','admins.id','export_centers.admin_id');
            $exports = $exports->where('export_centers.admin_id',auth('gate')->user()->id);
        };
        $exports = $exports->whereBetween('export_centers.created_at',[Carbon::now()->subDays(14),Carbon::now()]);
        !empty($cate) ? $exports = $exports->where('cate',$cate) : '';
        $exports = $exports->select([
            'export_centers.id',
            'export_centers.admin_id',
            'export_centers.export_no',
            'export_centers.condition',
            'export_centers.name',
            'export_centers.start_time',
            'export_centers.end_time',
            'export_centers.filename',
            DB::raw("(CASE WHEN export_centers.cate = 'statement' THEN '對帳單資料' WHEN export_centers.cate = 'orders' THEN '訂單資料' WHEN export_centers.cate = 'products' THEN '商品資料' WHEN export_centers.cate = 'vendors' THEN '商家資料' WHEN export_centers.cate = 'users' THEN '使用者資料' WHEN export_centers.cate = 'purchase' THEN '採購單資料' END) as cate"),
        ]);
        if(auth('gate')->user()->id != 40){
            $exports = $exports->addSelect([
                'admins.name as admin',
            ]);
        }
        $exports = $exports->orderBy('export_centers.created_at','desc')->paginate($list);
        foreach($exports as $export){
            $admin = AdminDB::find($export->admin_id);
            !empty($admin) ? $export->exportor = $admin->name : $export->exportor = null;
            $filePath = storage_path('app/exports/');
            $export->condition = json_decode($export->condition,true);
            if(!empty($export->condition['id'])){
                if($export->condition['model'] == 'products'){
                    $export->skus = ProductModelDB::whereIn('product_id',$export->condition['id'])->select('sku')->get();
                }elseif($export->condition['model'] == 'orders'){
                    $export->orderNumbers = OrderDB::whereIn('id',$export->condition['id'])->select('order_number')->get();
                }elseif($export->condition['model'] == 'purchase'){
                    $export->purchaseNumbers = PurchaseOrderDB::whereIn('id',$export->condition['id'])->select('purchase_no')->get();
                }elseif($export->condition['model'] == 'statement'){
                    $export->statementNumbers = StatementDB::whereIn('id',$export->condition['id'])->select('statement_no')->get();
                    $filePath = storage_path('app/exports/statements/');
                }
            }
            !empty($export->condition['con']) ? $export->cons = $export->condition['con'] : '';
            // dd($export->cons['erp_purchase_no']);
            $export->filePath = $filePath;
        }
        $cates = [
             ['value' => 'orders', 'name' => '訂單資料'],
             ['value' => 'purchase', 'name' => '採購單資料'],
             ['value' => 'statement', 'name' => '對帳單資料'],
            //  ['value' => 'products', 'name' => '商品資料'],
            //  ['value' => 'vendors', 'name' => '商家資料'],
            //  ['value' => 'users', 'name' => '使用者資料'],
        ];
        $categories = CategoryDB::orderBy('is_on','desc')->get();
        $vendors = VendorDB::orderBy('is_on','desc')->orderBy('id','desc')->get();
        $compact = array_merge($compact, ['exports','list','appends','cates','categories','vendors']);
        return view('gate.exportcenter.index',compact($compact));
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
        if($id == 0){
            $ids = request()->ids;
            $ids =  explode(',',$ids);
            if(is_array($ids)){
                $exports = ExportCenterDB::whereIn('id',$ids)->get();
                foreach($exports as $export){
                    if(file_exists(storage_path('app/exports/').$export->filename)){
                        unlink(storage_path('app/exports/').$export->filename);
                    }
                    $export->delete();
                }
            }
        }elseif(is_numeric($id) && $id > 0){
            $export = ExportCenterDB::findOrFail($id);
            if(file_exists(storage_path('app/exports/').$export->filename)){
                unlink(storage_path('app/exports/').$export->filename);
            }
            $export->delete();
        }
        return redirect()->back();
    }

    public function chkPwd(Request $request)
    {
        $adminId = auth('gate')->user()->id;
        $adminUser = AdminDB::find($adminId);
        $data = [];
        $data['pass'] = $data['message'] = $data['count'] = null;
        if(!empty($request->pwd)){
            if(Hash::check($request->pwd, env('GET_INFO_PWD'))){
                $adminUser->update(['lock_on' => 0]);
                $data['pass'] = true;
            }else{
                $adminUser->increment('lock_on');
                $data['count'] = $adminUser->lock_on;
                $data['message'] = '下載密碼輸入錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $data['message'] = '密碼輸入錯誤 3 次，帳號已被鎖定！請聯繫管理員。' : '';
                if($adminUser->lock_on >= 3){
                    $log = AdminLoginLogDB::create([
                        'admin_id' => $adminUser->id,
                        'result' => '下載密碼輸入錯誤 3 次，帳號鎖定。',
                        'ip' => $request->ip(),
                    ]);
                    auth('gate')->logout();
                }else{
                    $log = AdminLoginLogDB::create([
                        'admin_id' => $adminUser->id,
                        'result' => "下載密碼輸入錯誤 $adminUser->lock_on 次。",
                        'ip' => $request->ip(),
                    ]);
                }
            }
        }
        return response()->json($data);
    }

    public function download(Request $request)
    {
        $path = storage_path('app/exports/');
        $adminId = auth('gate')->user()->id;
        $adminUser = AdminDB::find($adminId);
        $data = [];
        $KeypassMemo = $data['pass'] = $data['message'] = $data['count'] = null;
        if(!empty($request->pwd)){
            if(Hash::check($request->pwd, env('GET_INFO_PWD'))){
                $adminUser->update(['lock_on' => 0]);
                if(!empty($request->id)){
                    $export = ExportCenterDB::find($request->id);
                    if(!empty($export)){
                        $file = $path.$export->filename;
                        if(file_exists($file)){
                            AdminKeypassLogDB::create([
                                'type' => '檔案匯出',
                                'is_pass' => 1,
                                'memo' => "$export->filename 檔案下載成功。",
                                'admin_id' => $adminUser->id,
                                'admin_name' => $adminUser->name,
                            ]);
                            return response()->download($file);
                        }else{
                            Session::put('warning', '檔案已不存在。');
                        }
                    }else{
                        Session::put('warning', '資料不存在。');
                    }
                }
            }else{
                $adminUser->increment('lock_on');
                $msg = '下載密碼輸入錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $msg = '密碼輸入錯誤 3 次，帳號已被鎖定！請聯繫管理員。' : '';
                Session::put('error', $msg);
                if($adminUser->lock_on >= 3){
                    $message = "下載密碼輸入錯誤 3 次，帳號鎖定。";
                }else{
                    $message = "下載密碼輸入錯誤 $adminUser->lock_on 次。";
                }
                AdminKeypassLogDB::create([
                    'type' => '檔案匯出',
                    'is_pass' => 0,
                    'memo' => $message,
                    'admin_id' => $adminUser->id,
                    'admin_name' => $adminUser->name,
                ]);
                $log = AdminLoginLogDB::create([
                    'admin_id' => $adminUser->id,
                    'result' => $message,
                    'ip' => $request->ip(),
                ]);
                if($adminUser->lock_on >= 3){
                    auth('gate')->logout();
                    return redirect('/');
                }
            }
        }
        return redirect()->back();
    }
}
