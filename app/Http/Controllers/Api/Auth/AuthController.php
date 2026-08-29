<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ResolvesCartToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    use ResolvesCartToken;

    public function __construct(private readonly CartService $carts) {}

    #[OA\Post(
        path: '/auth/register',
        tags: ['Auth'],
        summary: 'Register a new customer account',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ],
        )),
        responses: [new OA\Response(response: 201, description: 'Registered')],
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->string('password')),
        ]);

        $user->assignRole('customer');

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        $this->carts->mergeGuestCartIntoUser($user, $this->cartToken($request));

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ], 201);
    }

    #[OA\Post(
        path: '/auth/login',
        tags: ['Auth'],
        summary: 'Log in and obtain a Sanctum token',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Authenticated'),
            new OA\Response(response: 422, description: 'Invalid credentials'),
        ],
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        $this->carts->mergeGuestCartIntoUser($user, $this->cartToken($request));

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ]);
    }

    #[OA\Post(
        path: '/auth/logout',
        tags: ['Auth'],
        summary: 'Revoke the current access token',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Logged out')],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    #[OA\Get(
        path: '/auth/me',
        tags: ['Auth'],
        summary: 'Get the authenticated user',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Current user')],
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('roles')),
        ]);
    }

    #[OA\Post(
        path: '/auth/forgot-password',
        tags: ['Auth'],
        summary: 'Send a password reset link to the given email',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email'],
            properties: [new OA\Property(property: 'email', type: 'string', format: 'email')],
        )),
        responses: [new OA\Response(response: 200, description: 'Reset link sent (if the email exists)')],
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Always respond with a generic success message, whether or not the
        // email exists, so the endpoint can't be used to enumerate accounts.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    #[OA\Post(
        path: '/auth/reset-password',
        tags: ['Auth'],
        summary: 'Reset a password using a token from the reset email',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['token', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Password reset'),
            new OA\Response(response: 422, description: 'Invalid or expired token'),
        ],
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Your password has been reset. You can now log in.']);
    }
}
