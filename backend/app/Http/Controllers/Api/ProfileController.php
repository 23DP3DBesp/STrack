<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'login' => [
                'sometimes',
                'string',
                'min:3',
                'max:60',
                'regex:/^[A-Za-z0-9_.\-]+$/',
                Rule::unique('users', 'login')->ignore($user->id),
            ],
            'display_name' => ['nullable', 'string', 'max:120'],
            'locale' => ['sometimes', 'string', Rule::in(['en', 'lv'])],
            'theme' => ['sometimes', 'string', Rule::in(['light', 'dark'])],
            'currency' => ['sometimes', 'string', 'max:8'],
            'distance_unit' => ['sometimes', 'string', Rule::in(['km', 'mi'])],
        ]);

        $user->fill($data)->save();

        return response()->json($user->fresh());
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:120', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        // Invalidate other tokens (keep the current one so user stays logged in)
        $current = $request->user()->currentAccessToken();
        $user->tokens()->where('id', '!=', $current?->id)->delete();

        return response()->json(['message' => 'Password updated']);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password is incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted']);
    }
}
