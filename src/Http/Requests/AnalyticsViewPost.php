<?php

namespace Leanmachine\Analytics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsViewPost extends FormRequest
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
            'accountId' => ['required','string','min:1','max:50'],
            'propertyId' => ['required','string','min:1','max:50'],
            'viewId' => ['required','string','min:1','max:50'],
            'foreignId' => ['required','string','min:1','max:50']
        ];
    }
}
