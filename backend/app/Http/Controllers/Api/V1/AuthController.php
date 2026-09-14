<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', strtolower($request->string('email')->toString()))->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Le credenziali non sono valide.'],
            ]);
        }

        if (! $user->isActive()) {
            abort(403, 'Account disabilitato.');
        }

        $user->tokens()->where('name', 'pms-web')->delete();
        $token = $user->createToken('pms-web', ['pms:access'])->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user->load('roles:id,key,name'),
                'token' => $token,
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load('roles:id,key,name'),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['data' => null]);
    }
}
