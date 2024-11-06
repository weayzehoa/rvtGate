<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MailTemplate as MailTemplateDB;
use App\Jobs\AdminSendEmail;

use App\Traits\GroupBuyingOrderFunctionTrait;

class GroupBuyOrderRefundMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,GroupBuyingOrderFunctionTrait;

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
        $orders = $this->getGroupBuyingOrderData($this->param,'Refund');
        if (count($orders) > 0) {
            foreach($orders as $order){
                //信件模板
                $mailTemplate = MailTemplateDB::find(15);
                $userMail = $order->receiver_email;
                $param['from'] = 'icarry@icarry.me'; //寄件者
                $param['name'] = 'iCarry 我來寄'; //寄件者名字
                $param['replyTo'] = 'icarry@icarry.me'; //回信
                $param['replyName'] = 'iCarry 我來寄'; //回信
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
            }
        }
    }
}
