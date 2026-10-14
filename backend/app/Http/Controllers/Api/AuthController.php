<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        // Hash even when the email is unknown, so response time doesn't reveal which accounts exist.
        $passwordMatches = Hash::check($request->validated('password'), $user->password ?? $this->dummyHash());

        if (! $user || ! $passwordMatches) {
            return response()->json(['message' => __('auth.failed')], Response::HTTP_UNAUTHORIZED);
        }

        $expiration = config('sanctum.expiration');
        $token = $user->createToken('admin-dashboard', ['*'], $expiration ? now()->addMinutes($expiration) : null);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function dummyHash(): string
    {
        static $hash;

        return $hash ??= Hash::make('dummy-password-for-constant-time-checks');
    }
}
