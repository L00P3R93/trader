<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotDisposableEmail implements ValidationRule
{
    /** @var array<int, string> */
    private array $blocklist = [
        'mailinator.com',
        'tempmail.com',
        'guerrillamail.com',
        'throwaway.email',
        'yopmail.com',
        'trashmail.com',
        'sharklasers.com',
        'dispostable.com',
        'guerrillamailblock.com',
        'grr.la',
        'guerrillamail.info',
        'spam4.me',
        'maildrop.cc',
        'getairmail.com',
        'fakeinbox.com',
        'mailnull.com',
        'spamgourmet.com',
        'trashmail.at',
        'trashmail.io',
        'throwam.com',
    ];

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = strtolower(trim(substr(strrchr((string) $value, '@'), 1)));

        if (in_array($domain, $this->blocklist, strict: true)) {
            $fail('Disposable email addresses are not allowed.');
        }
    }
}
