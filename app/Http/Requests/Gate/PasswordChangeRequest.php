<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class PasswordChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account' => 'required',
            'oldpass' => 'required|min:6',
            'newpass' => 'required|different:oldpass|confirmed|min:6',
            'newpass_confirmation' => 'required|different:oldpass|min:6'
        ];
    }
}
