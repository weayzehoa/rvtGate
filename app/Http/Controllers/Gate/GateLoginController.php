<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Arcanedev\NoCaptcha\Rules\CaptchaRule;
use Auth;
use App\Models\Admin as AdminDB;
use App\Models\AdminPwdUpdateLog as AdminPwdUpdateLogDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;
use App\Models\IpAddress as IpAddressDB;
use DB;
use Hash;
use Session;
use Carbon\Carbon;
use App\Jobs\AdminSendSMS;
use PragmaRX\Google2FA\Google2FA;
use App\Http\Requests\Gate\PasswordChangeRequest;

class GateLoginController extends Controller
{
    // 先經過 middleware 檢查
    public function __construct()
    {
        $this->middleware('guest:gate', ['except' => ['showPwdChangeForm','passwordChange','showLoginForm','showOtpForm','logout','showUnlockForm','unlock','showUnlockOtpForm','checkUnlockOtp','show2faForm','showUnlock2faForm','checkUnlock2fa']]);
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
    }

    // 顯示 gate.login form 表單視圖
    public function showLoginForm()
    {
        return view('gate.login');
    }

    public function showOtpForm()
    {
        $data = Session::get('adminData');
        $id = $data['id'];
        $otpTime = $data['otpTime'];
        $last3Code = $data['last3Code'];
        $compact = ['last3Code','otpTime','id'];
        return view('gate.otp',compact($compact));
    }

    public function show2faForm()
    {
        $data = Session::get('adminData');
        $id = $data['id'];
        $compact = ['id'];
        return view('gate.2fa',compact($compact));
    }

    public function showUnlockForm()
    {
        return view('gate.unlock.unlock');
    }

    public function unlockAccount(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'account'   => 'required',
            'email'   => 'required|email',
            'g-recaptcha-response' => ['required', new CaptchaRule],
        ]);
        $adminUser = AdminDB::where([['account',$request->account],['email',$request->email]])->first();
        if(!empty($adminUser)){
            $id = $adminUser->id;
            $account = $adminUser->account;
            $email = $adminUser->email;
            if($adminUser->verify_mode == 'sms'){
                $code = rand(100000,999999);
                $otpTime = Carbon::now()->addMinutes(10);
                $last3Code = substr($adminUser->mobile,-3);
                Session::put('adminData',['account'=>$account, 'email'=>$email,'otpTime'=>$otpTime,'last3Code'=>$last3Code]);
                $adminUser->update(['otp' => $code, 'otp_time' => $otpTime]);
                $sms['return'] = true;
                !empty($adminUser->sms_vendor) ? $sms['supplier'] = $adminUser->sms_vendor : '';
                $sms['admin_id'] = $adminUser->id;
                $sms['phone'] = strstr($adminUser->mobile,'+') ? $adminUser->mobile : '+886'.ltrim($adminUser->mobile,'0');
                $sms['message'] = "iCarry中繼平台OTP號碼： $code ；若不是您本人操作卻收到此簡訊，請立即通知iCarry公司群組並標記技術部。";
                $result = AdminSendSMS::dispatchNow($sms);
                if($result['status'] == '傳送成功'){
                    return redirect()->route('unlockAccount.sendOtp');
                }else{
                    $smsVendor = $result['sms_vendor'];
                    $message = "$smsVendor 簡訊傳送失敗，請聯繫系統管理員";
                    Session::put('error',"$smsVendor 簡訊傳送失敗，請聯繫系統管理員");
                }
            }else{
                Session::put('adminData',['account'=>$account, 'email'=>$email]);
                return redirect()->route('unlockAccount.2fa');
            }
        }else{
            $message = '帳號 與 Email 錯誤。';
            Session::put('error',$message);
            $log = AdminLoginLogDB::create([
                'admin_id' => null,
                'result' => " $request->account 帳號與 $request->email 錯誤。",
                'ip' => $this->loginIp,
                'site' => '中繼後台',
            ]);
            return redirect()->back()->withInput($request->only('account', 'mobile'))->withErrors(['account' => $message]);
        }
    }

    public function showUnlockOtpForm()
    {
        $data = Session::get('adminData');
        $account = $data['account'];
        $email = $data['email'];
        $otpTime = $data['otpTime'];
        $last3Code = $data['last3Code'];
        $compact = ['last3Code','otpTime','account','email'];
        return view('gate.unlock.otpForm',compact($compact));
    }

    public function showUnlock2faForm()
    {
        $data = Session::get('adminData');
        $account = $data['account'];
        $email = $data['email'];
        $compact = ['account','email'];
        return view('gate.unlock.2faForm',compact($compact));
    }

    public function checkUnlock2fa(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'account' => 'required',
            'email' => 'required|email',
            'verify'   => 'required|digits:6',
        ]);
        $adminUser = AdminDB::where([['account',$request->account],['email',$request->email],['is_on',1]])->first();
        if(!empty($adminUser)){
            $google2fa = new Google2FA();
            $secretKey = $adminUser->google2fa_secret;
            $valid = $google2fa->verifyKey($secretKey, $request->verify);
            if($valid == true){
                $randomPassword = $this->randomString(8);
                //儲存新密碼並記錄
                $newPassWord = app('hash')->make($randomPassword);
                $adminUser->update(['password' => $newPassWord,'lock_on' => 0]);
                $changeLog = AdminPwdUpdateLogDB::create([
                    'admin_id' => $adminUser->id,
                    'password' => $newPassWord,
                    'ip' => $this->loginIp,
                    'editor_id' => $adminUser->id,
                ]);
                $log = AdminLoginLogDB::create([
                    'admin_id' => $adminUser->id,
                    'result' => '使用 unlockAccount 變更密碼',
                    'ip' => $this->loginIp,
                    'site' => '中繼後台',
                ]);
                $sms['return'] = true;
                $sms['admin_id'] = $adminUser->id;
                !empty($adminUser->sms_vendor) ? $sms['supplier'] = $adminUser->sms_vendor : '';
                $sms['phone'] = strstr($adminUser->mobile,'+') ? $adminUser->mobile : '+886'.ltrim($adminUser->mobile,'0');
                $sms['message'] = "密碼已變更，請使用新密碼登入：".$randomPassword."；若不是您本人操作卻收到此簡訊，請立即通知iCarry公司群組並標記技術部。";
                $result = AdminSendSMS::dispatchNow($sms);
                if($result['status'] == '傳送成功'){
                    Session::put('success','密碼已變更，請使用簡訊傳送的新密碼登入，登入後請重新變更密碼。');
                    return redirect()->route('gate.login');
                }else{
                    $smsVendor = $result['sms_vendor'];
                    $message = "$smsVendor 簡訊傳送失敗，請聯繫系統管理員";
                    Session::put('error',"$smsVendor 簡訊傳送失敗，請聯繫系統管理員");
                }
            }else{
                $message = "驗證碼錯誤。";
            }
        }else{
            $message = "驗證碼錯誤。";
        }
        return redirect()->back()->withInput($request->only('otp'))->withErrors(['otp' => $message]);
    }

    public function checkUnlockOtp(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'account' => 'required',
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);
        $adminUser = AdminDB::where([['account',$request->account],['email',$request->email],['otp',$request->otp],['is_on',1],['otp_time','>=',date('Y-m-d H:i:s')]])->first();
        if(!empty($adminUser)){
            $randomPassword = $this->randomString(8);
            //儲存新密碼並記錄
            $newPassWord = app('hash')->make($randomPassword);
            $adminUser->update(['password' => $newPassWord,'lock_on' => 0]);
            $changeLog = AdminPwdUpdateLogDB::create([
                'admin_id' => $adminUser->id,
                'password' => $newPassWord,
                'ip' => $this->loginIp,
                'editor_id' => $adminUser->id,
            ]);
            $log = AdminLoginLogDB::create([
                'admin_id' => $adminUser->id,
                'result' => '使用 unlockAccount 變更密碼',
                'ip' => $this->loginIp,
                'site' => '中繼後台',
            ]);
            $sms['return'] = true;
            $sms['admin_id'] = $adminUser->id;
            !empty($adminUser->sms_vendor) ? $sms['supplier'] = $adminUser->sms_vendor : '';
            $sms['phone'] = strstr($adminUser->mobile,'+') ? $adminUser->mobile : '+886'.ltrim($adminUser->mobile,'0');
            $sms['message'] = "密碼已變更，請使用新密碼登入：".$randomPassword."；若不是您本人操作卻收到此簡訊，請立即通知iCarry公司群組並標記技術部。";
            $result = AdminSendSMS::dispatchNow($sms);
            if($result['status'] == '傳送成功'){
                Session::put('success','密碼已變更，請使用簡訊傳送的新密碼登入，登入後請重新變更密碼。');
                return redirect()->route('gate.login');
            }else{
                $smsVendor = $result['sms_vendor'];
                $message = "$smsVendor 簡訊傳送失敗，請聯繫系統管理員";
                Session::put('error',"$smsVendor 簡訊傳送失敗，請聯繫系統管理員");
            }
        }else{
            $message = "驗證碼錯誤。";
        }
        return redirect()->back()->withInput($request->only('otp'))->withErrors(['otp' => $message]);
    }

    public function verify2fa(Request $request)
    {
        $adminUser = AdminDB::find($request->id);
        if(!empty($adminUser)){
            $google2fa = new Google2FA();
            $secretKey = $adminUser->google2fa_secret;
            $valid = $google2fa->verifyKey($secretKey, $request->verify);
            if($valid == true){
                $adminUser->update(['lock_on' => 0]);
                Auth::guard('gate')->login($adminUser);
                // 驗證無誤 記錄後轉入 dashboard
                $log = AdminLoginLogDB::create([
                    'admin_id' => $adminUser->id,
                    'result' => $adminUser->name.' 登入成功',
                    'ip' => $this->loginIp,
                    'site' => '中繼後台',
                ]);
                activity('後台管理')->causedBy($adminUser)->log('登入成功');
                return redirect()->intended(route('gate.dashboard'));
            }else{
                $adminUser->lock_on < 3 ? $adminUser->increment('lock_on') : '';
                $message = '驗證碼錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $message = '帳號已被鎖定！請聯繫管理員。' : '';
                if($adminUser->lock_on >= 3){
                    $log = AdminLoginLogDB::create([
                        'admin_id' => $adminUser->id,
                        'result' => '驗證碼輸入錯誤三次，帳號鎖定。',
                        'ip' => $this->loginIp,
                        'site' => '中繼後台',
                    ]);
                    $message = '帳號已被鎖定！請聯繫管理員。';
                }
                return redirect()->back()->withInput($request->only('id'))->withErrors(['verify' => $message]);
            }
        }else{
            return redirect()->to('login');
        }
    }

    public function otp(Request $request)
    {
        $adminUser = AdminDB::find($request->id);
        if(!empty($adminUser)){
            if($request->otp == $adminUser->otp){
                $now = date('Y-m-d H:i:s');
                if(strtotime($now) <= strtotime($adminUser->otp_time)){
                    $adminUser->update(['lock_on' => 0]);
                    Auth::guard('gate')->login($adminUser);
                    // 驗證無誤 記錄後轉入 dashboard
                    $log = AdminLoginLogDB::create([
                        'admin_id' => $adminUser->id,
                        'result' => $adminUser->name.' 登入成功',
                        'ip' => $this->loginIp,
                        'site' => '中繼後台',
                    ]);
                    activity('後台管理')->causedBy($adminUser)->log('登入成功');
                    return redirect()->intended(route('gate.dashboard'));
                }else{
                    $message = '驗證碼已逾時，請按返回登入重新登入';
                    return redirect()->back()->withInput($request->only('id','otp','last3Code'))->withErrors(['otp' => $message, 'return' => 'yes']);
                }
            }else{
                $adminUser->lock_on < 3 ? $adminUser->increment('lock_on') : '';
                $message = '驗證碼錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $message = '帳號已被鎖定！請聯繫管理員。' : '';
                if($adminUser->lock_on >= 3){
                    $log = AdminLoginLogDB::create([
                        'admin_id' => $adminUser->id,
                        'result' => '驗證碼輸入錯誤三次，帳號鎖定。',
                        'ip' => $this->loginIp,
                        'site' => '中繼後台',
                    ]);
                    $message = '帳號已被鎖定！請聯繫管理員。';
                }
                return redirect()->back()->withInput($request->only('id','otp','last3Code'))->withErrors(['otp' => $message]);
            }
        }else{
            return redirect()->to('login');
        }
    }

    // 登入
    public function login(Request $request)
    {
        // 驗證表單資料
        $this->validate($request, [
            'account'   => 'required',
            'password' => 'required|min:6',
            'g-recaptcha-response' => ['required', new CaptchaRule],
        ]);
        $adminUser = AdminDB::where('account',$request->account)->first();
        if(!empty($adminUser)){
            if($adminUser->lock_on < 3){
                $changeLog = AdminPwdUpdateLogDB::where('admin_id',$adminUser->id)
                ->select([DB::raw("DATEDIFF(NOW(),admin_pwd_update_logs.created_at) as last_modified")])
                ->orderBy('created_at','desc')->first();
                //直接撈資料表出來比對密碼方式
                $chkPassword = Hash::check($request->password, $adminUser->password);
                //檢查變更密碼是否超過90天
                if(env('APP_ENV') != 'local' && ($adminUser->password == null || empty($changeLog) || $changeLog->last_modified >= 90)){
                    // 轉至變更密碼表單
                    return redirect()->to('passwordChange');
                }elseif($chkPassword){
                    $passedIps = IpAddressDB::where('disable',1)->select('ip')->get()->pluck('ip')->all();
                    if(in_array($this->loginIp,$passedIps)){
                        $adminUser->update(['lock_on' => 0]);
                        Auth::guard('gate')->login($adminUser);
                        // 驗證無誤 記錄後轉入 dashboard
                        $log = AdminLoginLogDB::create([
                            'admin_id' => $adminUser->id,
                            'result' => $adminUser->name.' 登入成功',
                            'ip' => $this->loginIp,
                            'site' => '中繼後台',
                        ]);
                        activity('後台管理')->causedBy($adminUser)->log('登入成功');
                        return redirect()->intended(route('gate.dashboard'));
                    }elseif($adminUser->mobile == null){
                        $message = '尚未設定電話號碼，請聯繫管理員';
                        $log = AdminLoginLogDB::create([
                            'admin_id' => $adminUser->id,
                            'result' => '登入失敗，尚未設定電話號碼！',
                            'ip' => $this->loginIp,
                            'site' => '中繼後台',
                        ]);
                    }elseif($adminUser->lock_on <= 2){
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
                                return redirect()->to('otp');
                            }else{
                                $smsVendor = $result['sms_vendor'];
                                $message = "$smsVendor 簡訊傳送失敗，請聯繫系統管理員";
                                Session::put('error',"$smsVendor 簡訊傳送失敗，請聯繫系統管理員");
                            }
                        }else{
                            Session::put('adminData',['id'=>$id]);
                            return redirect()->to('2fa');
                        }
                    }else{
                        $message = '帳號已被鎖定！請聯繫管理員。';
                        $log = AdminLoginLogDB::create([
                            'admin_id' => $adminUser->id,
                            'result' => '登入失敗，帳號已被鎖定！',
                            'ip' => $this->loginIp,
                            'site' => '中繼後台',
                        ]);
                    }
                }elseif($adminUser->is_on == 0){
                    $message = '帳號已被停用！';
                    $log = AdminLoginLogDB::create([
                        'admin_id' => $adminUser->id,
                        'result' => '登入失敗，帳號已被停用！',
                        'ip' => $this->loginIp,
                        'site' => '中繼後台',
                    ]);
                }else{
                    $adminUser->lock_on < 3 ? $adminUser->increment('lock_on') : '';
                    $message = '帳號密碼錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                    $adminUser->lock_on >= 3 ? $message = '帳號已被鎖定！請聯繫管理員。' : '';
                    if($adminUser->lock_on >= 3){
                        $log = AdminLoginLogDB::create([
                            'admin_id' => $adminUser->id,
                            'result' => '密碼輸入錯誤三次，帳號鎖定。',
                            'ip' => $this->loginIp,
                            'site' => '中繼後台',
                        ]);
                    }
                }
            }else{
                $message = '帳號已被鎖定！請聯繫管理員。';
                $log = AdminLoginLogDB::create([
                    'account' => $request->account,
                    'result' => '登入失敗，帳號已被鎖定',
                    'ip' => $this->loginIp,
                    'site' => '中繼後台',
                ]);
            }
            return redirect()->back()->withInput($request->only('account', 'remember'))->withErrors(['account' => $message]);
        }
        $log = AdminLoginLogDB::create([
            'account' => $request->account,
            'result' => '登入失敗',
            'ip' => $this->loginIp,
            'site' => '中繼後台',
        ]);
        // 驗證失敗 返回並拋出表單內容 只拋出 account 與 remember 欄位資料
        // 訊息 [使用者名稱或密碼錯誤] 為了不讓別人知道到底帳號是否存在
        return redirect()->back()->withInput($request->only('account', 'remember'))->withErrors(['account' => trans('auth.failed')]);
    }

    // 登出
    public function logout()
    {
        // 紀錄行為
        $adminUser = AdminDB::find(Auth::guard('gate')->id());
        if(!empty($adminUser)){
            activity('後台管理')->causedBy($adminUser)->log('登出成功');
            $log = AdminLoginLogDB::create([
                'admin_id' => $adminUser->id,
                'result' => '登出成功',
                'ip' => $this->loginIp,
                'site' => '中繼後台',
            ]);
            // 登出
            Auth::guard('gate')->logout();
        }
        return redirect('/');
    }

    public function showPwdChangeForm()
    {
        return view('gate.change_password');
    }

    public function passwordChange(PasswordChangeRequest $request)
    {
        $admin = AdminDB::where([['account',$request->account],['is_on',1],['lock_on',0]])
            ->select([
                '*',
                'last_modified_pwd' => AdminPwdUpdateLogDB::whereColumn('admin_pwd_update_logs.admin_id','admins.id')
                    ->select('password')->orderBy('created_at','desc')->limit(1),
            ])->first();

            if(!empty($admin)){
                if($admin->password == null){
                    //儲存新密碼並記錄
                    $newPassWord = app('hash')->make($request->newpass);
                    $admin->update(['password' => $newPassWord]);
                    $log = AdminPwdUpdateLogDB::create([
                        'admin_id' => $admin->id,
                        'password' => $newPassWord,
                        'ip' => $this->loginIp,
                        'editor_id' => $admin->id,
                    ]);
                    Session::put('success','密碼已更新，請重新登入。');
                    return redirect('/');
                }else{
                    if(!Hash::check ($request->oldpass, $admin->password)){
                        return redirect()->back()->withInput($request->only('account'))->withErrors(['oldpass' => '舊密碼輸入錯誤']);
                    }elseif(Hash::check ($request->newpass, $admin->last_modified_pwd)){
                        return redirect()->back()->withInput($request->only('account'))->withErrors(['oldpass' => '新密碼不可與上次修改的密碼相同']);
                    }else{ //儲存新密碼並記錄
                        $newPassWord = app('hash')->make($request->newpass);
                        $admin->update(['password' => $newPassWord]);
                        $log = AdminPwdUpdateLogDB::create([
                            'admin_id' => $admin->id,
                            'password' => $newPassWord,
                            'ip' => $this->loginIp,
                            'editor_id' => $admin->id,
                        ]);
                        Session::put('success','密碼已更新，請重新登入。');
                        return redirect('/');
                    }
                }
        }
        return redirect()->back()->withErrors(['account' => '帳號不存在/禁用/鎖定。']);
    }

    protected function randomString($length = 1, $characters = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNPQRSTUVWXYZ') {
        if (!is_int($length) || $length < 0) {
            return false;
        }
        $characters_length = strlen($characters) - 1;
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, $characters_length)];
        }
        return $string;
    }



}
