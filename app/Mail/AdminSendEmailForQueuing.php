<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminSendEmailForQueuing extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $path = storage_path('app/exports/');
        $mail = $this->subject($this->details['subject']); //主旨
        !empty($this->details['from']) ? $mail = $mail->from($this->details['from'], $this->details['name']) : '';
        !empty($this->details['replyTo']) ? $mail = $mail->replyTo($this->details['replyTo'], $this->details['replyName']) : '';
        if($this->details['model'] == 'statement'){
            $path = public_path().'/exports/statements/';
            $mail = $mail->view('gate.mails.templates.StatementMailBody');
        }elseif($this->details['model'] == 'GroupBuyOrderMailBody'){
            $mail = $mail->view('gate.mails.templates.GroupBuyOrderMailBody');
        }elseif($this->details['model'] == 'GroupBuyOrderSellMailBody'){
            $mail = $mail->view('gate.mails.templates.GroupBuyOrderSellMailBody');
        }elseif($this->details['model'] == 'NormalOrderMailBody'){
            $mail = $mail->view('gate.mails.templates.NormalOrderMailBody');
        }elseif($this->details['model'] == 'RefunMailBody'){
            $mail = $mail->view('gate.mails.templates.RefunMailBody');
        }elseif($this->details['model'] == 'AirportPickupOrderMailBody'){
            $mail = $mail->view('gate.mails.templates.AirportPickupOrderMailBody');
        }elseif($this->details['model'] == 'AsiamileOrderMailBody'){
            $mail = $mail->view('gate.mails.templates.AsiamileOrderMailBody');
        }elseif($this->details['model'] == 'AsiamileAirportPickupOrderMailBody'){
            $mail = $mail->view('gate.mails.templates.AsiamileAirportPickupOrderMailBody');
        }elseif($this->details['model'] == 'KlookOrderMailBody'){
            $mail = $mail->view('gate.mails.templates.KlookOrderMailBody');
        }elseif($this->details['model'] == 'purchaseModifyMailBody'){
            $mail = $mail->view('gate.mails.templates.purchaseModifyMailBody');
        }elseif($this->details['model'] == 'PriceChangeFailMailBody'){
            $mail = $mail->view('gate.mails.templates.priceChangeFailMailBody');
        }elseif($this->details['model'] == 'mitakeAccountPointNotice'){
            $mail = $mail->view('gate.mails.templates.mitakeAccountPointNoticeMailBody');
        }elseif($this->details['model'] == 'CheckInvoiceCountMailBody'){
            $mail = $mail->view('gate.mails.templates.CheckInvoiceCountMailBody');
        }elseif($this->details['model'] == 'PriceRecoverFailMailBody'){
            $mail = $mail->view('gate.mails.templates.priceRecoverFailMailBody');
        }elseif($this->details['model'] == 'zeroProductNoticeMailBody'){
            $mail = $mail->view('gate.mails.templates.zeroProductNoticeMailBody');
        }elseif($this->details['model'] == 'nidinProductNotice'){
            $mail = $mail->view('gate.mails.templates.nidinProductNoticeMailBody');
        }elseif($this->details['model'] == 'nidinOrderReturn'){
            $mail = $mail->view('gate.mails.templates.nidinOrderReturnMailBody');
        }else{
            if(in_array($this->details['vendor'],$this->details['specialVendor'])){ //使用blade樣板
                if($this->details['version'] == 'old'){
                    $mail = $mail->view('gate.mails.templates.purchaseMailBodyForSpecialVendor');
                }else{
                    $mail = $mail->view('gate.mails.templates.purchaseMailBodyForSpecialVendorNew');
                }
            }else{
                if($this->details['version'] == 'old') {
                    $mail = $mail->view('gate.mails.templates.purchaseMailBodyForNormal');
                }else{
                    $mail = $mail->view('gate.mails.templates.purchaseMailBodyForNormalNew');
                }
            }
        }
        if(!empty($this->details['files'])){
            if(count($this->details['files']) > 0){
                $files = $this->details['files'];
                for($i=0; $i<count($this->details['files']);$i++){
                    $mail = $mail->attach($path.$this->details['files'][$i]);
                }
            }
        }
        return $mail;
    }
}
