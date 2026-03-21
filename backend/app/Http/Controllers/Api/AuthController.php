<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\SecurityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly SecurityLogService $securityLogService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => (string) $request->input('name'),
            'email' => (string) $request->input('email'),
            'password' => (string) $request->input('password'),
            'role' => 'user',
        ]);

        $token = $user->createToken($request->userAgent() ?? 'web')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', (string) $request->input('email'))->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            $this->securityLogService->log('auth.login_failed', [
                'email' => (string) $request->input('email'),
                'ip' => (string) $request->ip(),
                'user_agent' => (string) ($request->userAgent() ?? ''),
            ]);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken((string) $request->input('device_name', 'web'))->plainTextToken;

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
}
