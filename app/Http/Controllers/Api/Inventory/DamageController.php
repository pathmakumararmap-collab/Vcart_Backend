<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Damage\StoreDamageRequest;
use App\Http\Resources\DamageResource;
use App\Models\Damage;
use App\Repositories\Contracts\DamageRepositoryInterface;
use App\Services\DamageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DamageController extends Controller
{
    public function __construct(
        private readonly DamageRepositoryInterface $damages,
        private readonly DamageService $damageService,
    ) {}

    #[OA\Get(
        path: '/inventory/damages',
        tags: ['Inventory'],
        summary: 'List reported damaged stock',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Damage list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Damage::class);

        $damages = $this->damages->paginate((int) $request->integer('per_page', 15), ['warehouse']);

        return response()->json(DamageResource::collection($damages)->response()->getData(true));
    }

    #[OA\Post(
        path: '/inventory/damages',
        tags: ['Inventory'],
        summary: 'Report damaged stock (removes it from sellable inventory)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreDamageRequest $request): JsonResponse
    {
        $damage = $this->damageService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => new DamageResource($damage)], 201);
    }

    public function show(Damage $damage): JsonResponse
    {
        $this->authorize('view', $damage);

        return response()->json(['data' => new DamageResource($damage->load(['warehouse', 'items.product', 'items.variant']))]);
    }
}
