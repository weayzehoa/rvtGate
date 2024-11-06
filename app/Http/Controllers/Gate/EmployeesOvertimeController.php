<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\EmployeeOvertimeFileImportJob;
use App\Traits\EmployeeOvertimesFunctionTrait;

use Session;

class EmployeesOvertimeController extends Controller
{
    use EmployeeOvertimesFunctionTrait;
    public function __construct()
    {
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M34S3';
        $vacations = $appends =  $compact = $orders = [];
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
            $appends = array_merge($appends, ['list' => $list]);
            $compact = array_merge($compact, ['list']);
        }

        $overtimes = $this->getEmployeeOvertimes(request(),'index');

        $compact = array_merge($compact, ['menuCode','overtimes','appends']);
        return view('gate.employees.overtimes', compact($compact));
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

    public function import(Request $request)
    {
        if ($request->hasFile('filename')) {
            $file = $request->file('filename');
            $uploadedFileMimeType = $file->getMimeType();
            $excelMimes = ['application/octet-stream','application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if(!in_array($uploadedFileMimeType, $excelMimes)){
                $message = "檔案格式錯誤，$request->type 只接受 Excel 檔案格式。";
                Session::put('error', $message);
                return redirect()->back();
            }else{
                $result = EmployeeOvertimeFileImportJob::dispatchNow($request); //直接馬上處理
                if($result == 'rows error'){
                    $message = '檔案內資料欄數錯誤，請檢查檔案是否符合。';
                    Session::put('error', $message);
                }elseif($result == 'sheets error'){
                    $message = '檔案內資料的 Sheet 數超過 1 個，請檢查檔案資料是否只有 1 個 Sheet。';
                    Session::put('error', $message);
                }
            }
        }
        return redirect()->back();
    }
}
