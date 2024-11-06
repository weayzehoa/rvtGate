<?php

namespace App\Traits;
use Illuminate\Support\Str;

trait SuperPointAPIFunctionTrait
{
    protected function chkUserRegister($order)
    {
        $uuid = Str::uuid()->toString();
        
    }

    protected function crypted($plainText)
    {
        $crypted = null;
        if(!empty($plainText)){
            //SuperPoint 提供的 KEY
            $key = base64_decode(env('SUPERPOINT_AES_KEY'));
            //SuperPoint 提供的 IV
            $iv = base64_decode(env('SUPERPOINT_AES_IV'));
            $crypted = openssl_encrypt($plainText, "aes-256-cbc", $key, 0, $iv);
        }
        return $crypted;
    }

    protected function decrypted($serialNo)
    {
        $decrypted = null;
        if(!empty($plainText)){
            //SuperPoint 提供的 KEY
            $key = base64_decode(env('SUPERPOINT_AES_KEY'));
            //SuperPoint 提供的 IV
            $iv = base64_decode(env('SUPERPOINT_AES_IV'));
            $decrypted = openssl_decrypt($plainText, "aes-256-cbc", $key, 0, $iv);
        }
        return $decrypted;
    }

}
