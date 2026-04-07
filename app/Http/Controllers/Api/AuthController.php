<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'GoodPunch HR API',
    description: 'RESTful API for GoodPunch HR management system. Uses Sanctum token authentication.',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login and receive an access token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'mobile'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 422, description: 'Invalid credentials'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $deviceName = $request->device_name ?? $request->userAgent() ?? 'api';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new company + manager account',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'company_name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                    new OA\Property(property: 'company_name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Registration successful'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => 'required|string|max:255',
        ]);

        $company = Company::create([
            'name' => $validated['company_name'],
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['company_name']), 0, 6)).rand(100, 999),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'manager',
            'company_id' => $company->id,
        ]);

        $token = $user->createToken($request->userAgent() ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Revoke current access token',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Get the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'User details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
