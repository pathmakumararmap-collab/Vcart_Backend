<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    #[OA\Get(
        path: '/admin/users',
        tags: ['Admin Users'],
        summary: 'List users (staff & customers)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'User list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->users->paginate((int) $request->integer('per_page', 15), ['roles']);

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    #[OA\Post(
        path: '/admin/users',
        tags: ['Admin Users'],
        summary: 'Create a staff/admin user and assign roles',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->safe()->except('roles');
        $data['password'] = Hash::make($request->string('password'));

        $user = $this->users->create($data);
        $user->syncRoles($request->input('roles'));

        return response()->json(['data' => new UserResource($user->load('roles'))], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(['data' => new UserResource($user->load('roles'))]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->safe()->except(['roles', 'password']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password'));
        }

        $this->users->update($user, $data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles'));
        }

        return response()->json(['data' => new UserResource($user->fresh('roles'))]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user);

        return response()->json(['message' => 'User deleted.']);
    }
}
