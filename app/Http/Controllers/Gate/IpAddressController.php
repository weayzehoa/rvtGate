<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Arcanedev\NoCaptcha\Rules\CaptchaRule;
use App\Models\IpAddress as IpAddressDB;
use App\Models\Admin as AdminDB;
use App\Models\AdminPwdUpdateLog as AdminPwdUpdateLogDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;

use Auth;
use Carbon\Carbon;
use Hash;
use Session;
use DB;

use App\Jobs\AdminSendSMS;
use PragmaRX\Google2FA\Google2FA;

class IpAddressController extends Controller
{
    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate',['except' => ['showIPsettingForm','ipsetting','showOtpForm','checkOtp','show2faForm','check2fa']]);
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
        $menuCode = 'M26S5';
        $appends = [];
        $compact = [];
        $adminTable = env('DB_DATABASE').'.'.(new AdminDB)->getTable();
        $ipAddressTable = env('DB_DATABASE').'.'.(new IpAddressDB)->getTable();
        $disable = ['::1','127.0.0.1','60.248.153.34','60.248.153.35','60.248.153.36'];
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
        $ips = IpAddressDB::select([
                '*',
                DB::raw("(CASE WHEN admin_id = 0 THEN '系統預設' ELSE (SELECT name from admins where ip_addresses.admin_id = admins.id limit 1) END) as name"),
            ])->orderBy('created_at','desc')->paginate($list);;
        $admins = AdminDB::orderBy('is_on','desc')->get();

        $compact = array_merge($compact, ['menuCode','ips','admins','disable']);
        return view('gate.settings.ipsetting', compact($compact));
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
        IpAddressDB::create($request->all());
        return redirect()->back();
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
        IpAddressDB::findOrFail($id)->update($request->all());
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
        IpAddressDB::findOrFail($id)->delete();
        return redirect()->back();
    }
    /*
        啟用或禁用
     */
    public function active(Request $request)
    {
        isset($request->is_on) ? $is_on = $request->is_on : $is_on = 0;
        IpAddressDB::findOrFail($request->id)->fill(['is_on' => $is_on])->save();
        return redirect()->back();
    }

    public function showIPsettingForm()
    {
        return view('gate.ipsetting.settingForm');
    }

    public function ipsetting(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'account'   => 'required',
            'mobile' => 'required',
            'g-recaptcha-response' => ['required', new CaptchaRule],
        ]);
        $mobile = $request->mobile;
        strstr($mobile,'+886') ? $mobile = str_replace(['+886'],['0'],$mobile) : '';
        $adminUser = AdminDB::where([['account',$request->account],['mobile',$mobile],['is_on',1],['lock_on','<',3]])->first();
        if(!empty($adminUser)) {
            $id = $adminUser->id;
            if($adminUser->verify_mode == 'sms'){
                $code = rand(100000,999999);
                $otpTime = Carbon::now()->addMinutes(10);
                $last3Code = substr($adminUser->mobile,-3);
                Session::put('adminData',['id'=>$id,'otpTime'=>$otpTime,'last3Code'=>$last3Code]);
                $adminUser->update(['otp' => $code, 'otp_time' => $otpTime]);
                $sms['return'] = true;
                $sms['admin_id'] = $adminUser->id;
                !empty($adminUser->sms_vendor) ? $sms['supplier'] = $adminUser->sms_vendor : '';
                $sms['phone'] = strstr($adminUser->mobile,'+') ? $adminUser->mobile : '+886'.ltrim($adminUser->mobile,'0');
                $sms['message'] = "iCarry中繼平台OTP號碼： $code ；若不是您本人操作卻收到此簡訊，請立即通知iCarry公司群組並標記技術部。";
                $result = AdminSendSMS::dispatchNow($sms);
                if($result['status'] == '傳送成功'){
                    return redirect()->route('ipsetting.sendOtp');
                }else{
                    $smsVendor = $result['sms_vendor'];
                    $message = "$smsVendor 簡訊傳送失敗，請聯繫系統管理員";
                    Session::put('error',"$smsVendor 簡訊傳送失敗，請聯繫系統管理員");
                }
            }else{
                Session::put('adminData',['id'=>$id]);
                return redirect()->route('ipsetting.2fa');
            }
        }else{
            $message = '帳號與電話號碼不存在。';
            Session::put('error',$message);
            $log = AdminLoginLogDB::create([
                'admin_id' => null,
                'result' => " $request->account 帳號與 $mobile 電話號碼不存在。",
                'ip' => $this->loginIp,
                'site' => '中繼後台',
            ]);
        }
        return redirect()->back()->withInput($request->only('account', 'mobile'))->withErrors(['account' => $message]);
    }

    public function showOtpForm()
    {
        $data = Session::get('adminData');
        $id = $data['id'];
        $otpTime = $data['otpTime'];
        $last3Code = $data['last3Code'];
        $myip = $this->loginIp;
        $compact = ['last3Code','otpTime','id','myip'];
        return view('gate.ipsetting.otpForm',compact($compact));
    }

    public function show2faForm()
    {
        $data = Session::get('adminData');
        $id = $data['id'];
        $myip = $this->loginIp;
        $compact = ['id','myip'];
        return view('gate.ipsetting.2faForm',compact($compact));
    }

    public function check2fa(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'verify'   => 'required|digits:6',
            'ip' => 'required|ip',
        ]);
        $adminUser = AdminDB::find($request->id);
        if(!empty($adminUser)){
            $google2fa = new Google2FA();
            $secretKey = $adminUser->google2fa_secret;
            $valid = $google2fa->verifyKey($secretKey, $request->verify);
            if($valid == true){
                $ip = $request->ip;
                $ipAddress = IpAddressDB::where('admin_id',$adminUser->id)->first();
                if(!empty($ipAddress)){
                    $ipAddress->update(['ip' => $ip, 'memo' => "管理者 $adminUser->name 手動建立。"]);
                }else{
                    IpAddressDB::create([
                        'admin_id' => $adminUser->id,
                        'is_on' => 1,
                        'ip' => $ip,
                        'memo' => "管理者 $adminUser->name 手動新增。",
                    ]);
                }
                $log = AdminLoginLogDB::create([
                    'admin_id' => $adminUser->id,
                    'result' => " $adminUser->name 修改登入IP: $ip 。",
                    'ip' => $this->loginIp,
                    'site' => '中繼後台',
                ]);
                Session::put('info','IP修改成功。請重新登入。');
                return redirect()->route('gate.login');
            }else{
                $message = "驗證碼錯誤。";
                return redirect()->back()->withInput($request->only('verify', 'ip'))->withErrors(['verify' => $message]);
            }
        }
    }

    public function checkOtp(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'otp'   => 'required|digits:6',
            'ip' => 'required|ip',
        ]);
        $adminUser = AdminDB::where([['otp',$request->otp],['is_on',1],['lock_on',0],['otp_time','>=',date('Y-m-d H:i:s')]])->find($request->id);
        if(!empty($adminUser)){
            $ip = $request->ip;
            $ipAddress = IpAddressDB::where('admin_id',$adminUser->id)->first();
            if(!empty($ipAddress)){
                $ipAddress->update(['ip' => $ip, 'memo' => "管理者 $adminUser->name 手動建立。"]);
            }else{
                IpAddressDB::create([
                    'admin_id' => $adminUser->id,
                    'is_on' => 1,
                    'ip' => $ip,
                    'memo' => "管理者 $adminUser->name 手動新增。",
                ]);
            }
            $log = AdminLoginLogDB::create([
                'admin_id' => $adminUser->id,
                'result' => " $adminUser->name 修改登入IP: $ip 。",
                'ip' => $this->loginIp,
                'site' => '中繼後台',
            ]);
            Session::put('info','IP修改成功。請重新登入。');
            return redirect()->route('gate.login');
        }else{
            $message = "驗證碼錯誤。";
            return redirect()->back()->withInput($request->only('otp', 'ip'))->withErrors(['otp' => $message]);
        }
    }
}
