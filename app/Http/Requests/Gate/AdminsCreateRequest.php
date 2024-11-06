<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class AdminsCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'account' => 'required|unique:admins|min:3', //unique:admins 檢查在admins資料表中account欄位是否已經存在.
            'password' => 'required|min:6',
            'email' => 'required|email',
            'is_on' => 'required|boolean',
        ];
    }
}
