<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MailTemplate as MailTemplateDB;

class CheckMailTemplateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //檢查信件檔案內容是否相等
        $mailTemplates = MailTemplateDB::get();
        foreach($mailTemplates as $mailTemplate){
            $filename = $mailTemplate->filename;
            $destPath = resource_path('views/gate/mails/templates/');
            if(!empty($mailTemplate->content)){
                $fileContent = file_get_contents($destPath.$filename);
                if($mailTemplate->content != $fileContent){
                    file_put_contents($destPath.$filename,$mailTemplate->content);
                }
            }
        }
    }
}
