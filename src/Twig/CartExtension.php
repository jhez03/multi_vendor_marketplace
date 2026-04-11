<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * CartExtension
 *
 * Exposes cart state as Twig globals so any template — including shared
 * partials like _header.html.twig — can read cart data without requiring
 * every controller to manually pass the cart as a template variable.
 *
 * This mirrors the pattern already used by CurrencyExtension.
 *
 * Available in all templates automatically (registered via autoconfigure).
 *
 * ── Globals ──────────────────────────────────────────────────────────────────
 *
 *   {{ cart_count }}        {# total quantity across all line items  #}
 *   {{ cart_subtotal }}     {# float subtotal for the current session #}
 *
 * Why globals instead of a controller argument?
 *   The header is rendered via {% include %}, not a sub-request/ESI, so it
 *   shares the same template context as the parent. Passing the count from
 *   every single controller would violate DRY and create a hidden coupling
 *   where forgetting to pass it silently breaks the badge. A Twig global
 *   is the idiomatic Symfony solution for "ambient" data like this.
 *
 * Security note:
 *   CartService reads only the current user's session — no cross-user
 *   data leakage is possible.
 */
final class CartExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly CartService $cartService) {}

    /**
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        return [
            // Total item quantity shown on the cart badge (e.g. 3)
            'cart_count'    => $this->cartService->getTotalQuantity(),
            // Subtotal used in mini-cart previews (e.g. 1299.00)
            'cart_subtotal' => $this->cartService->getSubtotal(),
        ];
    }
}
