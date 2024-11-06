<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\AdminSendEmail;
use App\Models\SmsLog as SmsLogDB;
use App\Models\User as UserDB;
use App\Models\Country as CountryDB;
use App\Models\SystemSetting as SystemSettingDB;
use Twilio\Rest\Client as Twilio;
use AWS;
// use Nexmo;
use Curl;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Tea;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Console\Console;

class AdminSendSMS implements ShouldQueue
{
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
        $status = [];
        $awsId = $msgId = $message = $smsId = $supplier = $userId = $getResponse = '';
        $param = $this->param;
        isset($param['admin_id']) ? $adminId = $param['admin_id'] : $adminId = 0;
        isset($param['user_id']) ? $userId = $param['user_id'] : $userId = 0;
        isset($param['sms_id']) ? $smsId = $param['sms_id'] : $smsId = time();
        isset($param['return']) ? $return = true : $return = false;
        isset($param['message']) ? $message = $param['message'] : '';
        isset($param['supplier']) ? $supplier = strtolower($param['supplier']) : '';
        $sendMailParam['from'] = 'icarry@icarry.me'; //寄件者
        $sendMailParam['name'] = 'iCarry 中繼系統'; //寄件者名字
        $sendMailParam['model'] = 'mitakeAccountPointNotice';
        $sendMailParam['subject'] = "直流電通三竹餘額即將不足通知!!";
        $sendMailParam['to'] = ['crystal@icarry.me'];
        $sendMailParam['cc'] = ['grace@icarry.me','hsinchen@icarry.me'];
        !empty($param['phone']) ? $phone = '+'.ltrim(str_replace(array("o","-"," ","++"),array("+","","","+"),$param['phone']),'+') : $phone = '';
        //找不到$supplier時，從$phone判斷是否為台灣, 台灣則改用三竹
        if(empty($supplier)){
            strstr($phone,'+886') || substr($phone,0,2) == '09' ? $supplier = 'mitake' : '';
        }
        //未提供supplier時使用預設
        empty($supplier) ? $supplier = strtolower(SystemSettingDB::first()->sms_supplier) : '';
        //檢查三竹餘額
        if($supplier == 'mitake'){
            $account= env('MITAKE_ACCOUNT');
            $pwd=env('MITAKE_PASSWORD');
            $apiUrl="http://smexpress.mitake.com.tw/SmQueryGet.asp?username={$account}&password={$pwd}";
            $result=Curl::to($apiUrl)->withHeaders(['Content-Type:application/x-www-form-urlencoded','charset:utf-8'])
            ->withTimeout(5)->get();
            if(strstr($result, 'AccountPoint')) {
                $point = str_replace("AccountPoint=", "", $result);
                if(is_numeric($point)) {
                    if($point <= 0) {
                        $supplier = null;
                        $sendMailParam['AccountPoint'] = $point;
                        AdminSendEmail::dispatch($sendMailParam);
                    }
                }
            }
        }
        //測試站改用測試電話
        env('APP_ENV') == 'local' ? $phone = env('TEST_SMS_PHONE') : '';

        if(!empty($supplier) && !empty($phone) && !empty($message)){
            $status['sms_vendor'] = $supplier;
            $status['status'] = '傳送成功';
            try {
                switch ($supplier) {
                    case 'twilio':
                        $twilio = new Twilio(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
                        $resp = $twilio->messages->create(
                            $phone,
                            [
                                'from' => env('TWILIO_FROM_NUMBER'),
                                'body' => $message,
                                "statusCallback" => env('TWILIO_STATUS_CALLBACK'),
                            ]
                        );
                        $msgId = $resp->sid;
                        $getResponse = [
                            // 'user_id' => $userId,
                            'msg_sid' => $resp->sid,
                            'from' => $resp->from,
                            // 'to' => $resp->to,
                            // 'body' => $resp->body,
                            'status' => $resp->status,
                            'uri' => $resp->uri,
                            'date_sent' => date('Y-m-d H:i:s')
                        ];
                        $getResponse = json_encode($getResponse,JSON_UNESCAPED_UNICODE);
                        break;

                    // case 'nexmo':
                    //     Nexmo::message()->send([                        //使用 Nexmo 類 傳送 SMS
                    //         'to'   => $phone,                           //傳送對象
                    //         'from' => env('NEXMO_FROM'),                //從哪邊傳來
                    //         'text' => $message,                         //訊息內容
                    //     ]);
                    //     break;

                    case 'aws':
                        $awssms = AWS::createClient('sns');                //建立 AWS sns 類
                        $awssms->publish([
                            'Message' => $message,                      //訊息內容
                            'PhoneNumber' => $phone,                    //行動電話號碼 須符合 E.164 格式，ex: 886990123456
                            'MessageAttributes' => [                    //訊息屬性
                                'AWS.SNS.SMS.SMSType'  => [             //訊息類型
                                    'DataType'    => 'String',          //資料類型 字串
                                    'StringValue' => 'Transactional',   //性質 Transactional (交易類) Promotional (行銷類)
                                ]
                            ],
                        ]);
                        break;

                    case 'alibaba':
                        $config = new Config([
                            "accessKeyId" => env('ALICLOUD_ACCESS_KEY_ID'),
                            "accessKeySecret" => env('ALICLOUD_ACCESS_KEY_SECRET'),
                        ]);
                        $config->endpoint = "dysmsapi.aliyuncs.com";
                        $client = new Dysmsapi($config);

                        $sendSmsRequest = new SendSmsRequest([
                            "phoneNumbers" => $phone,
                            "signName" => env('ALICLOUD_SIGN_NAME'),
                            "templateCode" => env('ALICLOUD_TEMPLATE_CODE'),
                            "templateParam" => "{\"code\":\"{$message}\"}",
                        ]);
                        $resp = $client->sendSms($sendSmsRequest);
                        $getResponse = Utils::toJSONString(Tea::merge($resp));
                        $resp = json_decode($getResponse,true);
                        $msgId = $resp['body']['RequestId'];
                        $resp['body']['Message'] != 'OK' ? $status['status'] = '傳送失敗' : '';
                        $message = $resp['body']['Message'];
                        break;

                    case 'mitake':
                        $account= env('MITAKE_ACCOUNT');
                        $pwd=env('MITAKE_PASSWORD');
                        $dlvtime='';//簡訊預約時間。也就是希望簡訊何時送達手機，格式為YYYY-MM-DD HH:NN:SS或YYYYMMDDHHNNSS，或是整數值代表幾秒後傳送。空白則為即時簡訊
                        $vldtime='';//簡訊有效期限。格式為YYYY-MM-DD HH:NN:SS或YYYYMMDDHHNNSS，或是整數值代表傳送後幾秒後內有效。請勿超過大哥大業者預設之24小時期限
                        $smbody=urlencode($message);
                        $response=env('MITAKE_RESPONSE_URL');//狀態回報網址
                        $smsId=time();//客戶簡訊ID。用於避免重複發送，若有提供此參數，則會判斷該簡訊ID是否曾經發送，若曾發送過，則直接回覆之前發送的回覆值，並加上Duplicate=Y。必須維持唯一性
                        $phone=str_replace('+886','0',$phone); //$phone 受訊方手機號碼。請填入09帶頭的手機號碼。
                        $apiUrl="http://smexpress.mitake.com.tw:7003/SpLmGet";
                        $str = "username={$account}&password={$pwd}&CharsetURL=utf-8&dstaddr={$phone}&DestName={$userId}&dlvtime={$dlvtime}&vldtime={$vldtime}&smbody={$smbody}&response={$response}&clientID={$smsId}";//換長簡訊
                        $result=Curl::to($apiUrl)->withHeaders(['Content-Type:application/x-www-form-urlencoded','charset:utf-8'])
                        ->withTimeout(5)->withData($str)->post();
                        $result=str_replace(array("[1]",chr(13).chr(10)),array("?ok=1","&"),$result);
                        $result=mb_convert_encoding($result,"utf-8","big5");
                        //簡訊序號。發送失敗，則不會有此欄位。
                        //發送狀態。請參考附錄二的說明。
                        //剩餘點數。本次發送後的剩餘額度。
                        //是否為重複發送的簡訊。Y代表重複發送。
                        parse_str($result,$data);
                        if(!empty($data)){
                            $status['status'] = $this->mitakeStatus($data["statuscode"]);
                            $getResponse = json_encode($data,JSON_UNESCAPED_UNICODE);
                            isset($data["msgid"]) ? $msgId = $data["msgid"] : '';
                            //更新餘額
                            if(isset($data['AccountPoint'])){
                                SystemSettingDB::where('id',1)->update(['mitake_points' => $data['AccountPoint']]);
                                $send = [200,100,50,10,5,0];
                                if(in_array($data['AccountPoint'],$send)){
                                    AdminSendEmail::dispatch($sendMailParam);
                                }
                            };
                        }else{
                            $status['status'] = "傳送失敗";
                        }
                        break;

                    default:
                        # code...
                        break;
                }
            } catch (Exception $e) {
                $status['status'] = "傳送失敗";
            }
            $smsLog = SmsLogDB::create([
                'sms_id' => $smsId,
                'user_id' => $userId,
                'admin_id' => $adminId,
                'vendor' => $supplier,
                'get_response' => $getResponse,
                'status' => $status['status'],
                'message' => $message,
                'msg_id' => $msgId,
                'aws_id' => $awsId,
            ]);
            if($return == true){
                $status['status'] == '已送達業者' ? $status['status'] = '傳送成功' : '';
                return $status;
            }
        }else{
            if($return == true){
                $status['sms_vendor'] = $supplier;
                $status['status'] == '傳送失敗';
                return $status;
            }
        }
    }

    function mitakeStatus($str){
        switch($str){
            case '*':return '系統發生錯誤，請聯絡三竹資訊窗口人員';break;
            case 'a':return '簡訊發送功能暫時停止服務，請稍候再試';break;
            case 'b':return '簡訊發送功能暫時停止服務，請稍候再試';break;
            case 'c':return '請輸入帳號';break;
            case 'd':return '請輸入密碼';break;
            case 'e':return '帳號、密碼錯誤';break;
            case 'f':return '帳號已過期';break;
            case 'h':return '帳號已被停用';break;
            case 'k':return '無效的連線位址';break;
            case 'm':return '必須變更密碼，在變更密碼前，無法使用簡訊發送服務';break;
            case 'n':return '密碼已逾期，在變更密碼前，將無法使用簡訊發送服務';break;
            case 'p':return '沒有權限使用外部Http程式';break;
            case 'r':return '系統暫停服務，請稍後再試';break;
            case 's':return '帳務處理失敗，無法發送簡訊';break;
            case 't':return '簡訊已過期';break;
            case 'u':return '簡訊內容不得為空白';break;
            case 'v':return '無效的手機號碼';break;
            case '0':return '預約傳送中';break;
            case '1':return '已送達業者';break;
            case '2':return '已送達業者';break;
            case '3':return '已送達業者';break;
            case '4':return '已送達手機';break;
            case '5':return '內容有錯誤';break;
            case '6':return '門號有錯誤';break;
            case '7':return '簡訊已停用';break;
            case '8':return '逾時無送達';break;
            case '9':return '預約已取消';break;
            default:return '無法辨識的錯誤';break;
        }
    }
}
