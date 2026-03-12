<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendLoginCodeRequest;
use App\Http\Requests\Auth\VerifyLoginCodeRequest;
use App\Models\User;
use App\Notifications\Auth\LoginCodeNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\TwoFactorAuthenticatable;

class EmailLoginController extends Controller
{
    private const string PENDING_LOGIN_SESSION_KEY = 'email_login.pending';

    private const int LOGIN_CODE_TTL_MINUTES = 10;

    /**
     * Show the verification screen for an active email login attempt.
     */
    public function create(Request $request): RedirectResponse|View
    {
        $pendingLogin = $this->pendingLogin($request);

        if ($pendingLogin === null) {
            return redirect()->route('login');
        }

        return view('pages::auth.verify-login-code', [
            'email' => $pendingLogin['email'],
            'maskedEmail' => $this->maskEmail($pendingLogin['email']),
        ]);
    }

    /**
     * Send a fresh login code to the requested email address.
     */
    public function sendCode(SendLoginCodeRequest $request): RedirectResponse
    {
        $user = $request->loginUser();

        $this->issueLoginCode($request, $user, $request->boolean('remember'));

        return redirect()
            ->route('login.verify')
            ->with('status', __('We sent a 6-digit code to :email.', [
                'email' => $this->maskEmail($user->email),
            ]));
    }

    /**
     * Re-send the login code for the active email login attempt.
     */
    public function resendCode(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Start a new login to continue.')]);
        }

        $pendingLogin = $this->pendingLogin($request);

        $this->issueLoginCode($request, $user, (bool) ($pendingLogin['remember'] ?? false));

        return redirect()
            ->route('login.verify')
            ->with('status', __('We sent you a new code.'));
    }

    /**
     * Verify a login code and authenticate the pending user.
     */
    public function verifyCode(VerifyLoginCodeRequest $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Start a new login to continue.')]);
        }

        $cachedCode = Cache::get($this->cacheKey($user));

        if (! is_array($cachedCode) || ! Hash::check($request->string('code')->value(), $cachedCode['hash'] ?? '')) {
            throw ValidationException::withMessages([
                'code' => __('The verification code is invalid or has expired.'),
            ]);
        }

        $remember = (bool) ($this->pendingLogin($request)['remember'] ?? false);

        $this->clearPendingLogin($request, $user);

        if ($this->shouldChallengeTwoFactor($user)) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $remember,
            ]);

            return redirect()->route('two-factor.login');
        }

        Auth::guard(config('fortify.guard'))->login($user, $remember);
        $request->session()->regenerate();

        return app(LoginResponseContract::class)->toResponse($request);
    }

    /**
     * Create and send a login code for the given user.
     */
    private function issueLoginCode(Request $request, User $user, bool $remember): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($user), [
            'hash' => Hash::make($code),
        ], now()->addMinutes(self::LOGIN_CODE_TTL_MINUTES));

        $request->session()->put(self::PENDING_LOGIN_SESSION_KEY, [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'remember' => $remember,
        ]);

        $user->notify(new LoginCodeNotification($code, self::LOGIN_CODE_TTL_MINUTES));
    }

    /**
     * Get the current pending login payload from the session.
     *
     * @return array{user_id:int, email:string, remember:bool}|null
     */
    private function pendingLogin(Request $request): ?array
    {
        $pendingLogin = $request->session()->get(self::PENDING_LOGIN_SESSION_KEY);

        return is_array($pendingLogin) ? $pendingLogin : null;
    }

    /**
     * Resolve the user for the current pending login.
     */
    private function pendingUser(Request $request): ?User
    {
        $pendingLogin = $this->pendingLogin($request);

        if ($pendingLogin === null) {
            return null;
        }

        return User::query()->find($pendingLogin['user_id']);
    }

    /**
     * Clear the pending login session and cached code.
     */
    private function clearPendingLogin(Request $request, User $user): void
    {
        Cache::forget($this->cacheKey($user));
        $request->session()->forget(self::PENDING_LOGIN_SESSION_KEY);
    }

    /**
     * Determine whether the user should complete Fortify two-factor authentication.
     */
    private function shouldChallengeTwoFactor(User $user): bool
    {
        if (! Features::enabled(Features::twoFactorAuthentication())) {
            return false;
        }

        return filled($user->two_factor_secret)
            && ! is_null($user->two_factor_confirmed_at)
            && in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true);
    }

    /**
     * Build the cache key used for the user's login code.
     */
    private function cacheKey(User $user): string
    {
        return 'login-code:'.$user->getKey();
    }

    /**
     * Mask an email address for status messages.
     */
    private function maskEmail(string $email): string
    {
        [$localPart, $domain] = explode('@', $email);

        $visibleLocalPart = mb_substr($localPart, 0, min(2, mb_strlen($localPart)));
        $maskedLocalPart = $visibleLocalPart.str_repeat('*', max(mb_strlen($localPart) - mb_strlen($visibleLocalPart), 1));

        return $maskedLocalPart.'@'.$domain;
    }
}
