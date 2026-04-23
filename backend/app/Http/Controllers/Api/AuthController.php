<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\MailtrapEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly MailtrapEmailService $mailtrapEmailService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'login' => Str::lower(trim((string) $request->input('login'))),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'password' => (string) $request->input('password'),
        ]);

        $verificationUrl = $this->verificationUrlFor($user);
        $verificationEmailSent = $this->mailtrapEmailService->sendVerificationEmail($user, $verificationUrl);

        return response()->json([
            'user' => $user,
            'email' => $user->email,
            'verification_email_sent' => $verificationEmailSent,
            'message' => 'Account created. Please verify your email before logging in.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('login', Str::lower(trim((string) $request->input('login'))))
            ->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
                'code' => 'email_not_verified',
            ], 403);
        }

        $deviceName = trim((string) $request->input('device_name', 'web'));

        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['nullable', 'string', 'max:120', 'required_without:email'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255', 'required_without:login'],
        ]);

        $login = isset($data['login']) ? Str::lower(trim((string) $data['login'])) : null;
        $email = isset($data['email']) ? Str::lower(trim((string) $data['email'])) : null;

        $user = User::query()
            ->when($email, fn ($query) => $query->where('email', $email))
            ->when(! $email && $login, fn ($query) => $query->where('login', $login))
            ->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $this->mailtrapEmailService->sendVerificationEmail($user, $this->verificationUrlFor($user));
        }

        return response()->json([
            'message' => 'If the account exists and is not verified, a verification email has been sent.',
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);

        if (! $user || ! $request->hasValidSignature() || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->away($this->frontendVerificationUrl('invalid'));
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->away($this->frontendVerificationUrl('already-verified', $user->email));
        }

        $user->markEmailAsVerified();

        return redirect()->away($this->frontendVerificationUrl('verified', $user->email));
    }

    private function verificationUrlFor(User $user): string
    {
        return URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }

    private function frontendVerificationUrl(string $status, ?string $email = null): string
    {
        $baseUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $query = http_build_query(array_filter([
            'status' => $status,
            'email' => $email,
        ]));

        return "{$baseUrl}/verify-email?{$query}";
    }
}
