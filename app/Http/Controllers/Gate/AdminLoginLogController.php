<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;

class AdminLoginLogController extends Controller
{
    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    public function index()
    {
        $menuCode = 'M26S5';
        $adminLogs = [];
        $appends = [];
        $compact = [];
        $adminLogs = AdminLoginLogDB::orderBy('created_at', 'desc');
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
        !empty($admin_id) ? $adminLogs = $adminLogs->where('admin_id',$admin_id) : '';
        !empty($created_at) ? $adminLogs = $adminLogs->where('created_at','>=',$created_at) : '';
        !empty($created_at_end) ? $adminLogs = $adminLogs->where('created_at','<=',$created_at_end) : '';
        !empty($result) ? $adminLogs = $adminLogs->where('result','like',"%$result%") : '';
        $adminLogs = $adminLogs->addSelect([
            'admin_account' => AdminDB::whereColumn('admins.id','admin_login_logs.admin_id')
                ->select('account')->limit(1),
            'admin_name' => AdminDB::whereColumn('admins.id','admin_login_logs.admin_id')
                ->select('name')->limit(1),
        ]);
        $adminLogs = $adminLogs->paginate($list);
        $admins = AdminDB::orderBy('is_on','desc')->orderBy('created_at','desc')->get();
        $compact = array_merge($compact, ['adminLogs','admins','menuCode','list','appends']);
        return view('gate.logcenter.adminloginlog_index', compact($compact));
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
}
