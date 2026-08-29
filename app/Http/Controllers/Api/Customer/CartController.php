<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Concerns\ResolvesCartToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    use ResolvesCartToken;

    public function __construct(private readonly CartService $carts) {}

    #[OA\Get(
        path: '/cart',
        tags: ['Cart'],
        summary: 'View the current cart (authenticated user, or guest via X-Cart-Token header)',
        parameters: [new OA\Parameter(name: 'X-Cart-Token', in: 'header', description: 'Guest cart token (omit when authenticated)', schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Cart contents')],
    )]
    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->getOrCreateCart(Auth::guard('sanctum')->user(), $this->cartToken($request));

        return $this->respond($request, $cart->load('items.product', 'items.variant'));
    }

    #[OA\Post(
        path: '/cart/items',
        tags: ['Cart'],
        summary: 'Add an item to the cart',
        responses: [new OA\Response(response: 200, description: 'Updated cart')],
    )]
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->carts->getOrCreateCart(Auth::guard('sanctum')->user(), $this->cartToken($request));

        $cart = $this->carts->addItem(
            $cart,
            $request->integer('product_id'),
            $request->input('product_variant_id'),
            $request->integer('quantity'),
        );

        return $this->respond($request, $cart);
    }

    public function updateItem(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        $cart = $this->carts->getOrCreateCart(Auth::guard('sanctum')->user(), $this->cartToken($request));

        $cart = $this->carts->updateItemQuantity($cart, $item, $request->integer('quantity'));

        return $this->respond($request, $cart);
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->carts->getOrCreateCart(Auth::guard('sanctum')->user(), $this->cartToken($request));

        $cart = $this->carts->removeItem($cart, $item);

        return $this->respond($request, $cart);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->carts->getOrCreateCart(Auth::guard('sanctum')->user(), $this->cartToken($request));

        $cart = $this->carts->clear($cart);

        return $this->respond($request, $cart);
    }

    private function respond(Request $request, Cart $cart): JsonResponse
    {
        $payload = ['data' => new CartResource($cart)];

        if (! Auth::guard('sanctum')->user()) {
            $payload['cart_token'] = $this->cartToken($request);
        }

        return response()->json($payload);
    }
}
