<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    #[OA\Get(
        path: '/admin/roles',
        tags: ['Admin Users'],
        summary: 'List roles with their permissions',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Role list')],
    )]
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('users.view'), 403);

        return response()->json([
            'data' => Role::query()->with('permissions:id,name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('users.update'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::query()->create(['name' => $validated['name'], 'guard_name' => 'sanctum']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json(['data' => $role->load('permissions:id,name')], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->can('users.update'), 403);

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json(['data' => $role->fresh()->load('permissions:id,name')]);
    }

    #[OA\Get(
        path: '/admin/permissions',
        tags: ['Admin Users'],
        summary: 'List all available permissions',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Permission list')],
    )]
    public function permissions(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('users.view'), 403);

        return response()->json(['data' => Permission::query()->get(['id', 'name'])]);
    }
}
