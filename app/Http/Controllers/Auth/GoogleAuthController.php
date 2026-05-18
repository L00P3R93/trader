<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        $user = User::create([
            'account_no' => $this->generateAccountNumber(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => null,
        ]);

        $user->email_verified_at = now();
        $user->save();

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    private function generateAccountNumber(): string
    {
        do {
            $accountNo = 'G'.strtoupper(Str::random(8));
        } while (User::where('account_no', $accountNo)->exists());

        return $accountNo;
    }
}
