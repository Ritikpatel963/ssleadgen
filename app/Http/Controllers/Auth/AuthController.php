<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ─── Login ────────────────────────────────────────────────────────────────

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $request->incrementLoginAttempts();

            return back()->withErrors([
                'email' => __('These credentials do not match our records.'),
            ])->onlyInput('email');
        }

        // Block inactive users immediately after credential check
        if (! Auth::user()->isActive()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ])->onlyInput('email');
        }

        Auth::user()->update(['last_login_at' => now()]);

        $request->clearLoginAttempts();
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended', route(Auth::user()->defaultRouteName()));

        return redirect($intended);
    }

    public function redirectToAzure(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! $this->azureSsoIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft sign-in is not configured. Please use your password or contact the administrator.',
            ]);
        }

        return Socialite::driver('azure')->redirect();
    }

    public function handleAzureCallback(Request $request): RedirectResponse
    {
        if (! $this->azureSsoIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft sign-in is not configured. Please use your password or contact the administrator.',
            ]);
        }

        try {
            $identity = Socialite::driver('azure')->user();
        } catch (ConnectException $exception) {
            Log::warning('Microsoft Entra ID connection failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'The server could not connect to Microsoft sign-in. Please try again.',
            ]);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $details = json_decode((string) $response?->getBody(), true);

            Log::warning('Microsoft Entra ID sign-in request was rejected.', [
                'status' => $response?->getStatusCode(),
                'error' => is_array($details) ? ($details['error'] ?? null) : null,
                'error_description' => is_array($details) ? ($details['error_description'] ?? null) : null,
                'trace_id' => is_array($details) ? ($details['trace_id'] ?? null) : null,
                'correlation_id' => is_array($details) ? ($details['correlation_id'] ?? null) : null,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft sign-in could not be completed. Please try again.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Microsoft Entra ID sign-in failed.', [
                'exception' => $exception::class,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft sign-in could not be completed. Please try again.',
            ]);
        }

        $email = strtolower(trim((string) $identity->getEmail()));
        $user = $email === ''
            ? null
            : User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $user->isActive()) {
            return redirect()->route('login')->withErrors([
                'email' => 'No active account is assigned to this Microsoft account. Contact your administrator.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        $intended = $request->session()->pull('url.intended', route($user->defaultRouteName()));

        return redirect($intended);
    }

    private function azureSsoIsConfigured(): bool
    {
        $azure = config('services.azure', []);
        $tenant = strtolower((string) ($azure['tenant'] ?? ''));

        return filled($azure['client_id'] ?? null)
            && filled($azure['client_secret'] ?? null)
            && filled($azure['redirect'] ?? null)
            && filled($tenant)
            && ! in_array($tenant, ['common', 'organizations', 'consumers'], true);
    }

    // ─── Register ─────────────────────────────────────────────────────────────

    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Account created! Please sign in.');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
