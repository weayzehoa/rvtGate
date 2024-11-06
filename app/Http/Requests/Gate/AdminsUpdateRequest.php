<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class AdminsUpdateRequest extends FormRequest
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
            'account' => 'required|min:3',
            'password' => 'required|min:6',
            'mobile' => 'required|regex:/^([0-9\s\+]*)$/|max:15',
            'email' => 'required|email',
            'is_on' => 'required',
        ];
    }
}
