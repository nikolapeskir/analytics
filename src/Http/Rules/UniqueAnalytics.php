<?php

namespace Leanmachine\Analytics\Http\Rules;

use Leanmachine\Analytics\Http\Analytic;
use Illuminate\Contracts\Validation\Rule;

class UniqueAnalytics implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ! Analytic::where('user_id', auth()->id())
            ->where('name', $value)
            ->first();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.unique');
    }
}
