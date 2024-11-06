<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class SubmenusRequest extends FormRequest
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
            'mainmenu_id' => 'required|numeric',
            'name' => 'required|min:3|max:12',
            'url_type' => 'required|numeric',
            'is_on' => 'boolean|numeric',
            'open_window' => 'boolean|numeric',
        ];
    }
}
