<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryVendor as VendorDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ExportCenter as ExportCenterDB;
use App\Models\MailTemplate as MailTemplateDB;
use App\Traits\StatementFunctionTrait;
use App\Jobs\AdminSendEmail;
use Zip;

class StatementSendToVendorAndDownloadJob implements ShouldQueue
{
    use StatementFunctionTrait;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param = $this->param;
        $files = [];
        $statements = $this->getStatementData($param,'notice');
        if(count($statements) > 0){
            foreach($statements as $statement){
                $vendor = VendorDB::where('id',$statement->vendor_id)->first();
                !empty($vendor->digiwin_vendor_no) ? $erpVendorId = $vendor->digiwin_vendor_no : $erpVendorId = 'A'.str_pad($statement->vendor_id,5,'0',STR_PAD_LEFT);
                $erpVendor = ErpVendorDB::find($erpVendorId);
                $param['statementYear'] = $originYear =  $year = explode('-',$statement->end_date)[0];
                $originMonth = explode('-',$statement->end_date)[1];
                $month = explode('-',$statement->end_date)[1];
                $param['statementMonth'] = (INT)$month;
                if($erpVendor->MA055 == '2530'){ //特殊廠商
                    if((INT)$month == 1){
                        $param['statementDateRang'] = '12/26～'.(INT)$month.'/25';
                    }else{
                        $param['statementDateRang'] = ((INT)$month-1).'/26～'.(INT)$month.'/25';
                    }
                    if((INT)$month == 12){
                        $year = $year + 1;
                        $month = 0;
                    }
                    $param['getBefore'] = $year.'/'.((INT)$month + 1).'/3';
                    $param['payDate'] = $year.'/'.((INT)$month + 1).'/30';
                }else{
                    if((INT)$month == 1){
                        $param['statementDateRang'] = '12/26～'.(INT)$month.'/25';
                    }else{
                        $param['statementDateRang'] = ((INT)$month-1).'/26～'.(INT)$month.'/25';
                    }

                    (INT)$month == 12 ? $param['getBefore'] = ($year+1).'/1/3' : $param['getBefore'] = $year.'/'.((INT)$month + 1).'/3';

                    if((INT)$month == 11 || (INT)$month == 12 ){
                        $year = $year + 1;
                    }
                    (INT)$month == 11 || (INT)$month == 12 ? $param['payDate'] = $year.'/'.($month - 10).'/10' : $param['payDate'] = $year.'/'.((INT)$month + 2).'/10';
                }
                $statement->bill_email = str_replace(' ','',str_replace(['/',';','|',':'],[',',',',',',','],$statement->bill_email));
                $files[] = $statement->filename;
                //寄送通知
                $companyName = $statement->company;
                $vendorName = $statement->vendor_name;
                if($param['type'] == 'Email'){
                    $param['from'] = 'anita@icarry.me'; //寄件者
                    $param['name'] = 'Anita Tu'; //寄件者名字
                    $param['replyTo'] = 'icarryop@icarry.me'; //回信
                    $param['replyName'] = 'iCarry'; //回信
                    $mailTemplate = MailTemplateDB::find(3);
                    if(!empty($mailTemplate)){
                        $param['subject'] = str_replace(['#^#year','#^#month','#^#companyName','#^#vendorName'],[$originYear,(INT)$originMonth,$companyName,$vendorName],$mailTemplate->subject);
                    }else{
                        $param['subject'] = '【'.$originYear.'年 '.(INT)$originMonth.'月 對帳單】直流電通-iCarry_'.$companyName.'('.$vendorName.')';
                    }
                    $param['files'] = [$statement->filename];
                    $param['cc'] = ['icarryop@icarry.me']; //副本, 需使用陣列
                    $param['to'] = [];
                    if(env('APP_ENV') == 'local'){
                        $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                    }else{
                        empty($statement->bill_email) ? $statement->bill_email = $statement->email : '';
                        $statement->bill_email = str_replace(' ','',str_replace(['/',';','|',':','／','；','：','｜','　','，','、'],[',',',',',',',',',',',',',',',',',',',',','],$statement->bill_email));
                        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                        $mails = explode(',',$statement->bill_email);
                        for($i=0;$i<count($mails);$i++){
                            $mail = strtolower($mails[$i]);
                            if(preg_match($pattern,$mail)){
                                $param['to'][] = $mail; //收件者, 需使用陣列
                            };
                        }
                    }
                    //發送mail
                    if(count($param['to']) > 0){
                        unset($statement->bill_email);
                        $statement->update(['notice_time' => date('Y-m-d H:i:s')]);
                        $result = AdminSendEmail::dispatch($param); //馬上執行
                    }
                }
            }
            if ($param['type'] == 'Download') {
                if(!empty($param['con']['payment_com'])){
                    $paymentCom = explode(',',$param['con']['payment_com'])[0];
                    $V = ErpVendorDB::find('A'.str_pad($paymentCom,5,'0',STR_PAD_LEFT));
                    $param['con']['payment_com'] = $V->MA025;
                }
                $destPath = '/exports/';
                $file = $param['filename'];
                $zip = Zip::create(public_path() . $destPath . $file);
                for($i=0; $i<count($files);$i++){
                    $addFiles[] = public_path() . $destPath . '/statements/' . $files[$i];
                }
                $zip->add($addFiles);
                $zip->close();
                //儲存紀錄到匯出中心資料表
                $param['end_time'] = date('Y-m-d H:i:s');
                $param['condition'] = json_encode($param,true);
                $param['cate'] = $param['model'];
                $log = ExportCenterDB::create($param);
            }
        }
    }
}
