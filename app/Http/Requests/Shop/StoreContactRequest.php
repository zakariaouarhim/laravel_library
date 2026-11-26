<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // These three columns are varchar(191), not 255 — see
            // Schema::defaultStringLength(191) in AppServiceProvider. `message`
            // is TEXT, so max:5000 is already within bounds.
            //
            // Array syntax, not a pipe string, is mandatory for the not_regex
            // rules below: the patterns contain `|`, which the pipe-string
            // parser would split on, silently turning one rule into several
            // broken ones.
            //
            // not_regex targets the spam payload, matching the rule already
            // proven on signup (RegisterUserRequest). Of the 9,437 bot rows
            // purged from contact_messages on 2026-11-26, the link-dropping
            // campaign put a raw `<a href="...">` straight into the NAME
            // field. No real name, Arabic or Latin, contains <, >, ://,
            // http or www.
            'name'    => ['required', 'string', 'max:191', 'not_regex:/(<|>|:\/\/|\bhttps?\b|\bwww\.)/iu'],
            'email'   => ['required', 'email', 'max:191'],
            // `subject` is deliberately left unrestricted. A genuine customer
            // may well paste a URL when reporting a broken page, and the
            // honeypot already stops the automated traffic — a false reject
            // here is silent and costs a real sale.
            'subject' => ['required', 'string', 'max:191'],
            // Anchor and BBCode markup only, not links in general: a customer
            // asking about a book may legitimately paste its URL, but nobody
            // hand-types `<a href=` or `[url=` into a contact form.
            'message' => ['required', 'string', 'max:5000', 'not_regex:/(<a\s|<\/a>|\[url|\[link)/iu'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.not_regex'    => 'الاسم غير صالح: لا يمكن أن يحتوي على روابط أو وسوم HTML.',
            'message.not_regex' => 'الرسالة لا يمكن أن تحتوي على وسوم HTML أو روابط منسقة.',
        ];
    }
}
