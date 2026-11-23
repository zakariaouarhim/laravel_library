{{--
    Hidden spam traps, paired with App\Http\Middleware\AntiSpam.

    The decoy must come back empty, and the encrypted timestamp proves the form
    was actually rendered rather than posted blind. display:none is what keeps a
    real visitor safe: the field is skipped by browser autofill, unreachable by
    tab, and hidden from screen readers, so no human can trip either check.
    Spam bots parse the HTML without applying CSS and fill every input they find.
--}}
<div style="display:none" aria-hidden="true">
    <label for="hp_{{ \App\Http\Middleware\AntiSpam::HONEYPOT }}">Website</label>
    <input type="text"
           id="hp_{{ \App\Http\Middleware\AntiSpam::HONEYPOT }}"
           name="{{ \App\Http\Middleware\AntiSpam::HONEYPOT }}"
           value=""
           autocomplete="off"
           tabindex="-1">
</div>
<input type="hidden"
       name="{{ \App\Http\Middleware\AntiSpam::TIMESTAMP }}"
       value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
