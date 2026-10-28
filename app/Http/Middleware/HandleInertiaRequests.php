<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware {
    protected $rootView = 'app';

    public function version(Request $request): ?string {
        return parent::version($request);
    }

    public function share(Request $request): array {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? (new UserResource($user))->resolve() : null,
            ],
            'demo_users' => UserResource::collection(User::all())->resolve(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'csrf_token' => csrf_token(),
        ];
    }
}
