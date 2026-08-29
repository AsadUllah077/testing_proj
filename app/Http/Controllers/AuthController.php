<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\UserLoginLogs;
use App\Trait\OtpTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use OtpTrait;
    public function index()
    {
        return view('pages.auth.login');
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();
            $user = User::where('email', $credentials['email'])->first();
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json(['message' => 'Invalid credentials', 'success' => false], 401);
            }

            // Technicians skip OTP — log in directly
            if ($user->role && str_contains(strtolower($user->role->name ?? ''), 'technician')) {
                Auth::login($user);
                UserLoginLogs::create(['user_id' => $user->id]);
                return response()->json([
                    'message'     => 'Login successful',
                    'success'     => true,
                    'redirect_to' => route('warehouse.technicians.index'),
                ], 200);
            }

            // $this->sendOtp($user);
            Auth::login($user);
            // return response()->json(['message' => 'OTP sent successfully', 'success' => true], 200);
            return response()->json(['message' => 'Login successful', 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage(), 'success' => false], 500);
        }
    }

    public function redirectToB2c(Request $request)
    {
        if ($message = $this->b2cConfigurationError()) {
            return redirect()->route('user.login.view')->with('error', $message);
        }

        $state = Str::random(40);
        $nonce = Str::random(40);

        $request->session()->put('azure_b2c_state', $state);
        $request->session()->put('azure_b2c_nonce', $nonce);

        $query = http_build_query([
            'client_id' => config('services.azure_b2c.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->b2cRedirectUri(),
            'response_mode' => 'query',
            'scope' => $this->b2cScope(),
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($this->b2cEndpoint('authorize').'?'.$query);
    }

    public function handleB2cCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('user.login.view')
                ->with('error', $request->input('error_description', 'Microsoft B2C login was cancelled or failed.'));
        }

        $expectedState = (string) $request->session()->pull('azure_b2c_state', '');
        $actualState = (string) $request->query('state', '');

        if ($expectedState === '' || $actualState === '' || ! hash_equals($expectedState, $actualState)) {
            return redirect()->route('user.login.view')->with('error', 'Invalid Microsoft B2C login session. Please try again.');
        }

        if (! $request->filled('code')) {
            return redirect()->route('user.login.view')->with('error', 'Microsoft B2C did not return an authorization code.');
        }

        if ($message = $this->b2cConfigurationError()) {
            return redirect()->route('user.login.view')->with('error', $message);
        }

        try {
            $tokenResponse = Http::asForm()->post($this->b2cEndpoint('token'), [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.azure_b2c.client_id'),
                'client_secret' => config('services.azure_b2c.client_secret'),
                'code' => $request->query('code'),
                'redirect_uri' => $this->b2cRedirectUri(),
                'scope' => $this->b2cScope(),
            ]);

            if ($tokenResponse->failed()) {
                Log::warning('Azure B2C token exchange failed.', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);

                return redirect()->route('user.login.view')->with('error', 'Microsoft B2C could not complete login.');
            }

            $idToken = $tokenResponse->json('id_token');

            if (! is_string($idToken) || $idToken === '') {
                return redirect()->route('user.login.view')->with('error', 'Microsoft B2C did not return an ID token.');
            }

            $claims = $this->decodeJwtPayload($idToken);
            $expectedNonce = (string) $request->session()->pull('azure_b2c_nonce', '');

            if ($expectedNonce !== '' && ! hash_equals($expectedNonce, (string) ($claims['nonce'] ?? ''))) {
                return redirect()->route('user.login.view')->with('error', 'Invalid Microsoft B2C login token.');
            }

            if (($claims['aud'] ?? null) !== config('services.azure_b2c.client_id')) {
                return redirect()->route('user.login.view')->with('error', 'Microsoft B2C returned a token for a different application.');
            }

            if ((int) ($claims['exp'] ?? 0) < time()) {
                return redirect()->route('user.login.view')->with('error', 'Microsoft B2C login token has expired.');
            }

            $email = $this->extractB2cEmail($claims);

            if (! $email) {
                return redirect()->route('user.login.view')->with('error', 'Microsoft B2C did not return an email address.');
            }

            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if (! $user) {
                return redirect()
                    ->route('user.login.view')
                    ->with('error', 'No ERP user is linked to this Microsoft B2C email address.');
            }

            Auth::login($user);
            $request->session()->regenerate();

            UserLoginLogs::create([
                'user_id' => $user->id,
            ]);

            return redirect($this->redirectPathFor($user));
        } catch (\Throwable $th) {
            Log::error('Azure B2C login failed.', [
                'message' => $th->getMessage(),
            ]);

            return redirect()->route('user.login.view')->with('error', 'Microsoft B2C login failed. Please try again.');
        }
    }

    public function verifyView()
    {
        return view('pages.auth.verify');
    }

    public function verify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|numeric|digits:4',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors(), 'success' => false], 422);
            }

            $otp = $request->otp;
            $user = User::where('otp', $request->otp)->first();
            if (!$user) {
                return response()->json(['message' => 'Not found', 'success' => false], 404);
            }
            if ($this->verifyOtp($user, $otp)) {
                Auth::login($user);

                UserLoginLogs::create([
                    'user_id' => $user->id
                ]);

                // Determine redirect based on role
                $redirectTo = '/dashboard';
                if ($user->role) {
                    $roleName = strtolower($user->role->name ?? '');
                    if (str_contains($roleName, 'technician')) {
                        $redirectTo = route('warehouse.technicians.index');
                    }
                }

                return response()->json([
                    'message'     => 'OTP verified successfully',
                    'success'     => true,
                    'redirect_to' => $redirectTo,
                ], 200);
            }
            return response()->json(['message' => 'Invalid OTP', 'success' => false], 401);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage(), 'success' => false], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function forgotPasswordView()
    {
        return view('pages.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status), 
                'success' => true,
                'redirect_to' => route('user.login.view')
            ]);
        }

        return response()->json(['message' => __($status), 'success' => false], 400);
    }

    public function resetPasswordView(Request $request, $token)
    {
        return view('pages.auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status), 'success' => true, 'redirect_to' => route('user.login.view')]);
        }

        return response()->json(['message' => __($status), 'success' => false], 400);
    }

    private function b2cConfigurationError(): ?string
    {
        $required = [
            'tenant' => config('services.azure_b2c.tenant'),
            'policy' => config('services.azure_b2c.policy'),
            'client_id' => config('services.azure_b2c.client_id'),
            'client_secret' => config('services.azure_b2c.client_secret'),
        ];

        foreach ($required as $value) {
            if (! is_string($value) || trim($value) === '') {
                return 'Microsoft B2C login is not configured yet.';
            }
        }

        return null;
    }

    private function b2cEndpoint(string $endpoint): string
    {
        $tenantDomain = $this->b2cTenantDomain();
        $policy = trim((string) config('services.azure_b2c.policy'), '/');

        return sprintf(
            'https://%s/%s/%s/oauth2/v2.0/%s',
            $this->b2cDomain($tenantDomain),
            $tenantDomain,
            rawurlencode($policy),
            $endpoint
        );
    }

    private function b2cTenantDomain(): string
    {
        $tenant = trim((string) config('services.azure_b2c.tenant'));

        if ($tenant === '') {
            return '';
        }

        $isGuid = (bool) preg_match('/^[0-9a-fA-F-]{36}$/', $tenant);

        return str_contains($tenant, '.') || $isGuid ? $tenant : $tenant.'.onmicrosoft.com';
    }

    private function b2cDomain(string $tenantDomain): string
    {
        $domain = trim((string) config('services.azure_b2c.domain'));

        if ($domain !== '') {
            return rtrim(preg_replace('#^https?://#', '', $domain), '/');
        }

        return strtok($tenantDomain, '.').'.b2clogin.com';
    }

    private function b2cRedirectUri(): string
    {
        return route('user.b2c.callback');
    }

    private function b2cScope(): string
    {
        return trim((string) config('services.azure_b2c.scope', 'openid profile email'));
    }

    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            throw new \RuntimeException('Invalid JWT format.');
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new \RuntimeException('Unable to decode JWT payload.');
        }

        $claims = json_decode($decoded, true);

        if (! is_array($claims)) {
            throw new \RuntimeException('Invalid JWT payload.');
        }

        return $claims;
    }

    private function extractB2cEmail(array $claims): ?string
    {
        foreach (['email', 'preferred_username', 'upn', 'signInNames.emailAddress'] as $claim) {
            if (! empty($claims[$claim]) && filter_var($claims[$claim], FILTER_VALIDATE_EMAIL)) {
                return $claims[$claim];
            }
        }

        if (! empty($claims['emails'])) {
            $emails = is_array($claims['emails']) ? $claims['emails'] : [$claims['emails']];

            foreach ($emails as $email) {
                if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }

        return null;
    }

    private function redirectPathFor(User $user): string
    {
        if ($user->role && str_contains(strtolower($user->role->name ?? ''), 'technician')) {
            return route('warehouse.technicians.index');
        }

        return route('dashboard');
    }
}
