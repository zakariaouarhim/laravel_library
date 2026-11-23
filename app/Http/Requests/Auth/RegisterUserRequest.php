<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // max:191, not 255: Schema::defaultStringLength(191) in
            // AppServiceProvider means user.name/email are varchar(191), so a
            // 192-255 char value used to pass validation and then die on INSERT.
            //
            // not_regex blocks the spam payload itself. Every captured bot
            // signup put a promo link in the display name
            // ("SCAM SERVICE 24/7: https://..." / an <a href> tag); no real
            // name, Arabic or Latin, contains <, >, :// , http or www.
            'name'     => ['required', 'string', 'max:191', 'not_regex:/(<|>|:\/\/|\bhttps?\b|\bwww\.)/iu'],
            'email'    => 'required|email|max:191|unique:user,email',
            // bcrypt ignores anything past 72 bytes, so accepting more would
            // silently mislead the user about their own password.
            'password' => 'required|min:8|max:72|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'كلمة المرور يجب أن تحتوي على أحرف وأرقام على الأقل',
            'name.not_regex' => 'الاسم غير صالح: لا يمكن أن يحتوي على روابط أو وسوم HTML.',
        ];
    }
}
