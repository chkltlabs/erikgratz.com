<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class EmailOrPhoneRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //must be an email...
        return (! Validator::make([$attribute => $value], [
            $attribute => 'required|email',
        ])->fails())
            // ...or phone
            || (! Validator::make([$attribute => $value], [
                $attribute => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            ])->fails());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute field must be a valid email or phone number.';
    }
}
