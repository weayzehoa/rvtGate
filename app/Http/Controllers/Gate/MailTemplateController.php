<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\MailTemplate as MailTemplateDB;
use App\Models\iCarryNoticeModel as NoticeModelDB;
use File;

class MailTemplateController extends Controller
{
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
        $menuCode = 'M32S0';
        $appends = [];
        $compact = [];
        $adminTable = env('DB_DATABASE').'.'.(new AdminDB)->getTable();
        $mailTemplateTable = env('DB_DATABASE').'.'.(new MailTemplateDB)->getTable();
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
        $mailTemplates = MailTemplateDB::join($adminTable,$adminTable.'.id',$mailTemplateTable.'.admin_id')
            ->select([
                $mailTemplateTable.'.*',
                $adminTable.'.name as admin_name',
            ])->paginate($list);

        return view('gate.mails.index', compact('menuCode','mailTemplates'));
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
        $menuCode = 'M32S0';
        $compact = [];
        $fileContent = null;
        $mailTemplate = MailTemplateDB::findOrFail($id);
        //目的目錄
        $destPath = resource_path('views/gate/mails/templates/');
        $fileContent = $mailTemplate->content;
        if(empty($fileContent)){
            if(file_exists($destPath.$mailTemplate->filename)){
                $fileContent = file_get_contents($destPath.$mailTemplate->filename);
            }
        }
        $fileContent = str_replace(['{{','}}'],['((','))'],$fileContent);
        $compact = array_merge($compact, ['menuCode','mailTemplate','fileContent']);
        return view('gate.mails.show', compact($compact));
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
        $mailTemplate = MailTemplateDB::findOrFail($id);
        $head =
"<style>
    body{
        font-family: Microsoft JhengHei,arial,sans-serif !important;
    }
</style>
<body>
";
        $end ="
</body>";

        $fileContent = $request->content;
        !empty($request->subject) ? $subject = $request->subject : $subject = null;
        $fileContent = str_replace(["&nbsp;","&#39;",'(( ',' ))'],["<br>","'",'{{ ',' }}'],$fileContent); //處理空白字元與'符號
        $mailTemplate->update([
            'admin_id' => auth('gate')->user()->id,
            'subject' => $subject,
            'content' => $fileContent
        ]);
        $fileContent = $head.$fileContent.$end;
        $filename = $mailTemplate->filename;
        $destPath = resource_path('views/gate/mails/templates/');
        file_put_contents($destPath.$filename,$fileContent);

        if($mailTemplate->id == 16){
            $noticeModel = NoticeModelDB::find(1);
            !empty($noticeModel) ? $noticeModel->update(['email_html' => $fileContent]) : '';
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
}
