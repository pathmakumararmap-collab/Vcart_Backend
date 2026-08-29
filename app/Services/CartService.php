<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class CartService
{
    public function getOrCreateCart(?User $user, ?string $sessionId): Cart
    {
        if ($user) {
            return Cart::query()->firstOrCreate(['user_id' => $user->id]);
        }

        return Cart::query()->firstOrCreate(['session_id' => $sessionId]);
    }

    public function addItem(Cart $cart, int $productId, ?int $variantId, int $quantity): Cart
    {
        $product = Product::query()->findOrFail($productId);

        if ($variantId) {
            ProductVariant::query()->where('product_id', $product->id)->findOrFail($variantId);
        }

        $item = $cart->items()->firstOrNew([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ]);

        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->save();

        return $cart->fresh('items.product', 'items.variant');
    }

    public function updateItemQuantity(Cart $cart, int $itemId, int $quantity): Cart
    {
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }

        return $cart->fresh('items.product', 'items.variant');
    }

    public function removeItem(Cart $cart, int $itemId): Cart
    {
        $cart->items()->findOrFail($itemId)->delete();

        return $cart->fresh('items.product', 'items.variant');
    }

    public function clear(Cart $cart): Cart
    {
        $cart->items()->delete();

        return $cart->fresh();
    }

    /**
     * Called on login: folds a guest's session-based cart into their
     * account cart, so items added before signing in aren't lost.
     */
    public function mergeGuestCartIntoUser(User $user, ?string $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        $guestCart = Cart::query()->where('session_id', $sessionId)->first();

        if (! $guestCart || $guestCart->items()->doesntExist()) {
            return;
        }

        $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $userItem = $userCart->items()->firstOrNew([
                'product_id' => $guestItem->product_id,
                'product_variant_id' => $guestItem->product_variant_id,
            ]);

            $userItem->quantity = ($userItem->exists ? $userItem->quantity : 0) + $guestItem->quantity;
            $userItem->save();
        }

        $guestCart->delete();
    }
}
