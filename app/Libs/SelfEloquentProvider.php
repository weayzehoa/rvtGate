<?php

namespace App\Libs;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class SelfEloquentProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];
        $authPassword = $user->getAuthPassword();
        //自定義加密方式。舊的是使用sha1的加密方式。 (Model那邊覆蓋預設 getAuthPassword() )
        return sha1($plain) == $authPassword['password'];
    }
}
