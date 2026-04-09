<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Session-based cart service.
 *
 * Cart structure stored in session:
 *   cart => [
 *     product_id => [
 *       'id'       => int,
 *       'name'     => string,
 *       'price'    => float,
 *       'image'    => string|null,
 *       'quantity' => int,
 *       'shop'     => string|null,
 *     ],
 *     ...
 *   ]
 *
 * This keeps the cart self-contained and survives product edits —
 * quantities are re-validated on checkout before payment.
 */
final class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(private readonly RequestStack $requestStack) {}

    // ── Read ────────────────────────────────────────────────────

    /** Returns the raw cart array keyed by product ID. */
    public function getCart(): array
    {
        return $this->session()->get(self::SESSION_KEY, []);
    }

    /** Number of individual line items (not total quantity). */
    public function getItemCount(): int
    {
        return count($this->getCart());
    }

    /** Sum of all quantities. */
    public function getTotalQuantity(): int
    {
        return array_sum(array_column($this->getCart(), 'quantity'));
    }

    /** Subtotal as float (no tax, no shipping applied yet). */
    public function getSubtotal(): float
    {
        return array_reduce(
            $this->getCart(),
            static fn(float $carry, array $item): float => $carry + ($item['price'] * $item['quantity']),
            0.0
        );
    }

    // ── Write ───────────────────────────────────────────────────

    /**
     * Adds a product to the cart or increments its quantity.
     * Quantity is capped at 99 per line.
     */
    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $id   = (string) $product->getId();

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] = min(99, $cart[$id]['quantity'] + $quantity);
        } else {
            $cart[$id] = [
                'id'       => $product->getId(),
                'name'     => $product->getName(),
                'price'    => (float) $product->getPrice(),
                'image'    => $product->getProductImageUrls()[0] ?? null,
                'shop'     => $product->getShop()?->getStoreName(),
                'quantity' => min(99, $quantity),
            ];
        }

        $this->save($cart);
    }

    /**
     * Sets the exact quantity for a product.
     * Quantity <= 0 removes the item.
     */
    public function update(int $productId, int $quantity): void
    {
        $cart = $this->getCart();
        $id   = (string) $productId;

        if ($quantity <= 0) {
            unset($cart[$id]);
        } elseif (isset($cart[$id])) {
            $cart[$id]['quantity'] = min(99, $quantity);
        }

        $this->save($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[(string) $productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function session(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }

    private function save(array $cart): void
    {
        $this->session()->set(self::SESSION_KEY, $cart);
    }
}
