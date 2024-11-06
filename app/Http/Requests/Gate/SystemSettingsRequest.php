<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class SystemSettingsRequest extends FormRequest
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
            'sms_supplier' => 'required',
            'email_supplier' => 'required',
            'invoice_supplier' => 'required',
            'customer_service_supplier' => 'required',
            'payment_supplier' => 'required',
            'gross_weight_rate' => 'required',
        ];
    }
}
