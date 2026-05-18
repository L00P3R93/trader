<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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

        $googleName = htmlspecialchars(strip_tags(trim($googleUser->getName() ?? '')), ENT_QUOTES, 'UTF-8');
        $googleEmail = strtolower(trim($googleUser->getEmail() ?? ''));

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        $user = User::where('email', $googleEmail)->first();

        if ($user) {
            $user->google_id = $googleUser->getId();
            $user->avatar = $googleUser->getAvatar();

            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            $user->save();

            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        $user = User::create([
            'account_no' => $this->generateAccountNumber(),
            'name' => $googleName,
            'email' => $googleEmail,
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => null,
        ]);

        $user->email_verified_at = now();
        $user->save();

        Mail::to($user)->send(new WelcomeMail($user));

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
