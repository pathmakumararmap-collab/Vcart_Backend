<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: '/customer/profile',
        tags: ['Customer Orders'],
        summary: "Get the authenticated customer's profile",
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Profile')],
    )]
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    #[OA\Put(
        path: '/customer/profile',
        tags: ['Customer Orders'],
        summary: 'Update profile',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Updated')],
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return response()->json(['data' => new UserResource($user->fresh())]);
    }
}
