<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
    public function login(Request $request): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email yoki parol noto‘g‘ri.',
            ], 422);
        }

        Auth::login($user);
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function switchDemoUser(Request $request): JsonResponse {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        Auth::login($user);
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function currentUser(Request $request): JsonResponse {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function listDemoUsers(): JsonResponse {
        $users = User::all();

        return response()->json([
            'users' => UserResource::collection($users),
        ]);
    }

    public function logout(Request $request): JsonResponse {
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();
        }

        Auth::guard('web')->logout();

        return response()->json([
            'message' => 'Muvaffaqiyatli chiqildi.',
        ]);
    }
}
