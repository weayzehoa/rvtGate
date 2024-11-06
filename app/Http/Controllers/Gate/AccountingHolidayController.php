<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountingHoliday as AccountingHolidayDB;
use App\Traits\UniversalFunctionTrait;
use Session;

class AccountingHolidayController extends Controller
{
    use UniversalFunctionTrait;

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
        $menuCode = 'M26S7';
        $appends =  $compact = $holidays = [];
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
        }

        $holidays = AccountingHolidayDB::where('type','erpACRTCProcess');

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        $holidays = $holidays->orderBy('exclude_date', 'desc')->paginate($list);

        $compact = array_merge($compact, ['menuCode','holidays','appends']);
        return view('gate.settings.accounting_holiday', compact($compact));
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
        $message = null;
        $data = $request->all();
        if(!empty($data['exclude_date'])){
            $data['exclude_date'] = $this->convertAndValidateDate($data['exclude_date']);
            if($data['exclude_date'] != false){
                if(in_array(date('w',strtotime($data['exclude_date'])),[0,6])){
                    if(date('w',strtotime($data['exclude_date'])) == 6){
                        $message = '日期不可為週六。';
                    }elseif(date('w',strtotime($data['exclude_date'])) == 0){
                        $message = '日期不可為週日。';
                    }
                }else{
                    AccountingHolidayDB::create($data);
                }
            }else{
                $message = '日期格式錯誤。';
            }
        }
        !empty($message) ? Session::put('error',$message) : '';
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
        $holiday = AccountingHolidayDB::find($id)->delete();
        return redirect()->back();
    }
}
