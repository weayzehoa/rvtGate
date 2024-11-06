<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequisitionAbnormal as RequisitionAbnormalDB;
use App\Models\Admin as AdminDB;
use App\Models\StockinImport as StockinImportDB;

use App\Traits\RequisitionAbnormalFunctionTrait;

class RequisitionAbnormalController extends Controller
{
    use RequisitionAbnormalFunctionTrait;

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
        $menuCode = 'M33S3';
        $appends = [];
        $compact = [];
        $abnormals = [];
        $abnormals = $this->getRequisitionAbnormalData(request(),'index');

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

        foreach($abnormals as $abnormal){
            $admin = AdminDB::find($abnormal->admin_id);
            !empty($admin) ? $abnormal->admin_name = $admin->name : $abnormal->admin_name = null;
        }

        $compact = array_merge($compact, ['menuCode','abnormals','appends']);
        return view('gate.sellreturns.requisition_abnormal', compact($compact));
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
        $abnormal = RequisitionAbnormalDB::findOrFail($id);
        if(!empty($request->is_chk) && $request->is_chk == 1){
            $stockinImport = StockinImportDB::find($abnormal->stockin_import_id);
            !empty($stockinImport) ? $stockinImport->delete() : '';
            $abnormal->update([
                'is_chk' => 1,
                'chk_date' => date('Y-m-d H:i:s'),
                'admin_id' => auth('gate')->user()->id,
            ]);
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

    public function multiProcess(Request $request)
    {
        if(count($request->ids) > 0){
            $abnormals = RequisitionAbnormalDB::whereIn('id',$request->ids)->get();
            foreach($abnormals as $abnormal){
                $stockinImport = StockinImportDB::find($abnormal->stockin_import_id);
                !empty($stockinImport) ? $stockinImport->delete() : '';
                $abnormal->update([
                    'is_chk' => 1,
                    'chk_date' => date('Y-m-d H:i:s'),
                    'admin_id' => auth('gate')->user()->id,
                ]);
            }
        }
        return redirect()->back();
    }
}
