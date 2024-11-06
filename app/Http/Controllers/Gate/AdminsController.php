<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Admin as AdminDB;
use App\Models\PowerAction as PowerActionDB;
use App\Models\AdminPwdUpdateLog as AdminPwdUpdateLogDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;

use Auth;
use Hash;
use Session;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Requests\Gate\AdminsCreateRequest;
use App\Http\Requests\Gate\AdminsUpdateRequest;
use App\Http\Requests\Gate\ChangePassWordRequest;

class AdminsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
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
        request()->has('list') ? $list = (INT)urldecode(request()->list) : $list = 30;
        request()->has('is_on') ? $is_on = (INT)urldecode(request()->is_on) : $is_on = 2;
        request()->has('keyword') ? $keyword = urldecode(request()->keyword) : $keyword = '';

        if($is_on==2){
            if($keyword){
                // $admins = AdminDB::search($keyword)->orderBy('id','desc')->paginate($list);
                $admins = AdminDB::where('name','like',"%$keyword%")
                                    ->orWhere('account','like',"%$keyword%")
                                    ->orWhere('email','like',"%$keyword%")
                                    ->orderBy('id','desc')->paginate($list);
            }else{
                $admins = AdminDB::orderBy('id','desc')->paginate($list);
            }
        }else{
            $admins = AdminDB::where('is_on',$is_on);
            if($keyword){
                // $admins = AdminDB::search($keyword)->where('is_on',$is_on)->orderBy('id','desc')->paginate($list);
                $admins = $admins->where(function ($query) use ($keyword) {
                        $query->where('name','like',"%$keyword%")
                        ->orWhere('account','like',"%$keyword%")
                        ->orWhere('email','like',"%$keyword%");
                });
            }
            $admins = $admins->orderBy('id','desc')->paginate($list);
        }

        $totalAdmins = AdminDB::get()->count();
        $totalEnable = AdminDB::where('is_on',1)->get()->count();
        $totalDisable = AdminDB::where('is_on',0)->get()->count();
        $menuCode = 'M26S1';
        return view('gate.admins.index', compact('menuCode','admins','totalAdmins','totalEnable','totalDisable','list','is_on','keyword'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menuCode = 'M26S1';
        $powerActions = PowerActionDB::all();
        return view('gate.admins.show',compact('powerActions','menuCode'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AdminsCreateRequest $request)
    {
        $data = $request->all();
        $data['password'] = app('hash')->make($data['password']);
        $admin = AdminDB::create($data);
        return redirect()->route('gate.admins.show', $admin->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $menuCode = 'M26S1';
        $admin = AdminDB::findOrFail($id);
        $google2fa = new Google2FA();
        if(empty($admin->google2fa_secret)){
            $secretKey = $google2fa->generateSecretKey(32);
            $admin->update(['google2fa_secret' => $secretKey]);
        }else{
            $secretKey = $admin->google2fa_secret;
        }
        env('APP_ENV') == 'local' ? $companyName = 'iCarry TEST' : $companyName = 'iCarry Admin';
        $companyEmail = $admin->email;
        $google2faUrl = $google2fa->getQRCodeUrl($companyName,$companyEmail,$secretKey);
        $qrCodeUrl = QrCode::generate($google2faUrl);
        $powerActions = PowerActionDB::all();
        return view('gate.admins.show',compact('admin','powerActions','menuCode','qrCodeUrl'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AdminsUpdateRequest $request, $id)
    {
        $data = $request->all();
        //透過id找出管理者資料
        $admin = AdminDB::findOrFail($id);
        //比對密碼
        if($data['password'] == $admin->password){
            $data['password'] = $admin->password;
        }else{
            $data['password'] = app('hash')->make($request->password);
            $log = AdminPwdUpdateLogDB::create([
                'admin_id' => $admin->id,
                'password' => $data['password'],
                'ip' => $this->loginIp,
                'editor_id' => Auth::user()->id,
            ]);
        }
        $data['lock_on'] = 0;
        if($data['is_on'] == 3){
            $data['lock_on'] = $data['is_on'];
            $data['is_on'] = 1;
        }
        AdminDB::findOrFail($id)->update($data);
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
        $admin = AdminDB::find($id)->delete();
        return redirect()->back();
    }

    /*
        啟用或禁用該帳號
     */
    public function active(Request $request)
    {
        isset($request->is_on) ? $is_on = $request->is_on : $is_on = 0;
        AdminDB::findOrFail($request->id)->fill(['is_on' => $is_on])->save();
        return redirect()->back();
    }

    /*
        解除帳號鎖定
     */
    public function unlock(Request $request, $id)
    {
        $admin = AdminDB::find($id);
        if(!empty($admin)){
            $name = $admin->name;
            $admin->update(['lock_on' => 0]);
            $log = AdminLoginLogDB::create([
                'admin_id' => $admin->id,
                'result' => '後台解鎖成功 ('.Auth::user()->name.'協助解鎖)',
                'ip' => $this->loginIp,
            ]);
            Session::put('success',"已解除 $name 帳號鎖定");
        }
        return redirect()->back();
    }
    /*
        搜尋姓名及帳號
    */
    // public function search(Request $request){
    //     if(!$request->has('keyword')){
    //         return redirect()->back();
    //     }
    //     $keyword = $request->keyword;
    //     $admins = AdminDB::where('name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orderBy('id', 'DESC')->paginate(15);
    //     return view()->make('gate.admins.index', compact('admins', 'keyword'));
    // }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changePassWordForm()
    {
        $admin = AdminDB::find(Auth()->user()->id);
        $google2fa = new Google2FA();
        if(empty($admin->google2fa_secret)){
            $secretKey = $google2fa->generateSecretKey(32);
            $admin->update(['google2fa_secret' => $secretKey]);
        }else{
            $secretKey = $admin->google2fa_secret;
        }
        env('APP_ENV') == 'local' ? $companyName = 'iCarry TEST' : $companyName = 'iCarry Admin';
        $companyEmail = $admin->email;
        $google2faUrl = $google2fa->getQRCodeUrl($companyName,$companyEmail,$secretKey);
        $qrCodeUrl = QrCode::generate($google2faUrl);
        return view('gate.admins.change_password',compact('admin','qrCodeUrl'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changePassWord(ChangePassWordRequest $request)
    {
        $data = $request->all();
        if(isset($data['renew']) && $data['renew'] == 1){
            $google2fa = new Google2FA();
            $secretKey = $google2fa->generateSecretKey(32);
            $data['google2fa_secret'] = $secretKey;
        }
        if(!empty($data['newpass']) && !empty($data['oldpass'])){
            if(!Hash::check ($data['oldpass'], Auth()->user()->password)){
                return redirect()->back()->withErrors(['oldpass' => '舊密碼輸入錯誤']);
            }
            $data['password'] = app('hash')->make($request->newpass);
        }
        $admin = AdminDB::findOrFail(Auth::user()->id);
        $admin->update($data);
        if(!empty($data['newpass']) && !empty($data['oldpass'])){
            $log = AdminPwdUpdateLogDB::create([
                'admin_id' => $admin->id,
                'password' => $data['newpass'],
                'ip' => $this->loginIp,
                'editor_id' => Auth::user()->id,
            ]);
        }
        Session::put('success','個人資料變更成功');
        return redirect()->back();
    }
}
