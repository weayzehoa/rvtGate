<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class ChangePassWordRequest extends FormRequest
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
            'oldpass' => 'required_with:newpass',
            'newpass' => 'nullable|required_with:oldpass|confirmed|min:6',
            'newpass_confirmation' => 'required_with:newpass|required_with:oldpass',
            'sms_vendor' => 'nullable|in:aws,mitake',
        ];
    }
}
