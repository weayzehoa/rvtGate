<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class CompanySettingsRequest extends FormRequest
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
            'name' => 'required|max:255',
            'name_en' => 'required|regex:/^([a-zA-Z0-9\s\-\+\,\.\(\)]*)$/|max:255',
            'tax_id_num' => 'required|regex:/^([0-9]*)$/|max:8',
            'tel' => 'required|regex:/^([0-9\s\-\+]*)$/|max:30',
            'fax' => 'required|regex:/^([0-9\s\-\+]*)$/|max:30',
            'address' => 'required|max:255',
            'address_en' => 'required|regex:/^([a-zA-Z0-9\s\-\+\,\.\(\)]*)$/|max:255',
            'service_tel' => 'required|regex:/^([0-9\s\-\+]*)$/|max:30',
            'service_email' => 'required|email|max:255',
            'website' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'fb_url' => 'required|string|max:255',
            'Instagram_url' => 'required|string|max:255',
            'Telegram_url' => 'required|string|max:255',
            'line' => 'nullable|string|max:255',
            'wechat' => 'nullable|string|max:255',
        ];
    }
}
