<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CurrencyService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * CurrencyExtension
 *
 * Exposes the CurrencyService to Twig templates so they never reference
 * currency symbols, codes, or formatting logic directly.
 *
 * Available in all templates automatically (registered via autoconfigure).
 *
 * ── Filters ─────────────────────────────────────────────────────────────────
 *
 *   {{ product.price | currency_format }}          {# ₱1,234.50 #}
 *   {{ product.price | currency_format(false) }}   {# 1,234.50  #}
 *
 * ── Functions ────────────────────────────────────────────────────────────────
 *
 *   {{ currency_symbol() }}   {# ₱  #}
 *   {{ currency_code() }}     {# PHP #}
 *
 * ── Global variable ──────────────────────────────────────────────────────────
 *
 *   {{ currency.symbol }}     {# ₱   #}
 *   {{ currency.code }}       {# PHP #}
 *
 *   This global is convenient for templates that reference the symbol multiple
 *   times — assign once at the top and use the variable throughout.
 */
final class CurrencyExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly CurrencyService $currency) {}

    // ── Filters ──────────────────────────────────────────────────────────────

    public function getFilters(): array
    {
        return [
            /**
             * Format a monetary value using the application currency.
             *
             * Usage:
             *   {{ 1234.50 | currency_format }}         {# ₱1,234.50 #}
             *   {{ product.price | currency_format }}
             *   {{ product.price | currency_format(false) }}  {# no symbol #}
             */
            new TwigFilter(
                'currency_format',
                fn(mixed $amount, bool $symbol = true): string => $this->currency->format((float) $amount, $symbol),
            ),
        ];
    }

    // ── Functions ────────────────────────────────────────────────────────────

    public function getFunctions(): array
    {
        return [
            /**
             * Returns the currency symbol for the current application currency.
             *
             * Usage: {{ currency_symbol() }}  →  ₱
             */
            new TwigFunction(
                'currency_symbol',
                fn(): string => $this->currency->getSymbol(),
            ),

            /**
             * Returns the ISO 4217 currency code.
             *
             * Usage: {{ currency_code() }}  →  PHP
             */
            new TwigFunction(
                'currency_code',
                fn(): string => $this->currency->getCode(),
            ),
        ];
    }

    // ── Globals ──────────────────────────────────────────────────────────────

    /**
     * Injects a `currency` global object into every Twig template.
     *
     * Usage:
     *   {{ currency.symbol }}   {# ₱   #}
     *   {{ currency.code }}     {# PHP #}
     *
     * This is the recommended approach for templates that display the symbol
     * alongside every price (e.g. a product grid) — read it once from the
     * global rather than calling currency_symbol() on every iteration.
     *
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        return [
            'currency' => [
                'code'   => $this->currency->getCode(),
                'symbol' => $this->currency->getSymbol(),
            ],
        ];
    }
}
