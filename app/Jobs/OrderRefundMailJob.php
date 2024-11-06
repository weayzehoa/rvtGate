<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MailTemplate as MailTemplateDB;
use App\Traits\OrderFunctionTrait;
use App\Jobs\AdminSendEmail;

class OrderRefundMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait;

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
        $refund = $param['refund'];
        $orders = $this->getOrderData($this->param,'Refund');
        if (count($orders) > 0) {
            foreach($orders as $order){
                $user = $order->user;
                //下面客戶才需要發信件通知, 亞萬 031 改為憑證
                $v = ['001','002','003','004','005','006','007','008','009','037','063','073','086'];
                if(in_array($order->digiwin_payment_id,$v)){
                    $mailTemplate = MailTemplateDB::find(8);
                    //信件模板
                    !empty($order->user_email) ? $userMail = $order->user_email : $userMail = $oder->buyer_email; //如果購買者沒填信箱改抓訂單內的買受人email
                    $param['from'] = 'icarry@icarry.me'; //寄件者
                    $param['name'] = 'iCarry 我來寄'; //寄件者名字
                    $param['replyTo'] = 'icarry@icarry.me'; //回信
                    $param['replyName'] = 'iCarry 我來寄'; //回信
                    $param['cc'] = 'backup@icarry.me'; //備份一份
                    $param['model'] = $mailTemplate->file;
                    $param['subject'] = str_replace(['#^#orderNumber'],[$order->order_number],$mailTemplate->subject);
                    $param['order'] = $order; //製作Body的資料
                    $param['to'] = [];
                    if(env('APP_ENV') == 'local'){
                        $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                    }else{
                        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                        if(!empty($userMail) && preg_match($pattern,strtolower($userMail))){
                            $param['to'][] = strtolower($userMail); //收件者, 需使用陣列
                        }
                    }
                    //發送mail
                    if(count($param['to']) > 0){
                        try {
                            $result = AdminSendEmail::dispatchNow($param); //馬上執行
                            return ['status' => 'success','message' => '退款信件已寄出。'];
                        } catch (Exception $e) {
                            return ['status' => 'error','message' => '信件寄送失敗。'];
                        }
                    }else{
                        return ['status' => 'error','message' => '查無收件者信箱或收件者信箱錯誤。'];
                    }
                }else{
                    return ['status' => 'warning','message' => '退款通知僅限於iCarry訂單。'];
                }
            }
        }
    }
}
