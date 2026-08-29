<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreAddressRequest;
use App\Http\Requests\Customer\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AddressController extends Controller
{
    #[OA\Get(
        path: '/customer/addresses',
        tags: ['Customer Orders'],
        summary: "List the customer's saved addresses",
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Address list')],
    )]
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => AddressResource::collection($request->user()->addresses)]);
    }

    #[OA\Post(
        path: '/customer/addresses',
        tags: ['Customer Orders'],
        summary: 'Add a new address',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $request->user()->addresses()->create($request->validated());

        return response()->json(['data' => new AddressResource($address)], 201);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address->update($request->validated());

        return response()->json(['data' => new AddressResource($address->fresh())]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }
}
