<?php

declare(strict_types=1);

namespace App\Service;

use NumberFormatter;

/**
 * CurrencyService
 *
 * Single source of truth for everything currency-related in the application.
 *
 * Usage (injection):
 *
 *   public function __construct(private readonly CurrencyService $currency) {}
 *
 *   $this->currency->format(1234.5);   // "₱1,234.50"
 *   $this->currency->getCode();        // "PHP"
 *   $this->currency->getSymbol();      // "₱"
 *
 * The currency code is bound from the `app.currency` Symfony parameter
 * (config/services.yaml), which reads the APP_CURRENCY environment variable.
 * Change the env var once → the entire application reflects the new currency.
 */
final class CurrencyService
{
    /** ISO 4217 currency code, e.g. "PHP", "USD", "EUR". */
    private readonly string $currencyCode;

    /**
     * @param string $currencyCode Injected via services.yaml `$currencyCode` bind.
     * @param string $locale       BCP 47 locale used for number formatting.
     *                             Defaults to the system default; override per-deployment
     *                             by injecting a different value if needed.
     */
    public function __construct(
        string $currencyCode,
        private readonly string $locale = 'en_PH',
    ) {
        // Normalise to uppercase so "php" and "PHP" are treated identically.
        $this->currencyCode = strtoupper(trim($currencyCode));
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    /**
     * Returns the ISO 4217 currency code (e.g. "PHP").
     */
    public function getCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * Returns the localised currency symbol for the current currency code.
     *
     * Uses PHP's intl extension (NumberFormatter) so the symbol is always
     * correct for the given locale — no hard-coded strings anywhere.
     *
     * Examples:
     *   PHP → ₱   (en_PH locale)
     *   USD → $
     *   EUR → €
     *   SGD → S$
     */
    public function getSymbol(): string
    {
        $formatter = new NumberFormatter(
            $this->locale . '@currency=' . $this->currencyCode,
            NumberFormatter::CURRENCY,
        );

        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        // Fall back to the currency code itself if intl cannot resolve the symbol.
        return ($symbol !== false && $symbol !== '') ? $symbol : $this->currencyCode;
    }

    // ── Formatting ───────────────────────────────────────────────────────────

    /**
     * Formats a monetary amount using the application currency and locale.
     *
     * @param float|int|string $amount  The amount to format.
     * @param bool             $symbol  When true (default), includes the currency symbol.
     *                                  When false, returns only the formatted number.
     *
     * Examples (PHP / en_PH):
     *   format(1234.5)        → "₱1,234.50"
     *   format(1234.5, false) → "1,234.50"
     *   format(0)             → "₱0.00"
     */
    public function format(float|int|string $amount, bool $symbol = true): string
    {
        $amount = (float) $amount;

        $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        if (!$symbol) {
            // Strip the currency symbol by re-formatting as decimal.
            $decimal = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
            $decimal->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
            $decimal->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);

            return $decimal->format($amount);
        }

        return $formatter->formatCurrency($amount, $this->currencyCode);
    }

    /**
     * Formats an amount stored in the smallest currency unit (e.g. centavos).
     *
     * Symfony's MoneyType stores amounts multiplied by `divisor` (default 100).
     * Use this method when reading raw DB values that are in centavos/cents.
     *
     * @param int $subunit Amount in smallest unit (e.g. 150000 = ₱1,500.00)
     */
    public function formatSubunit(int $subunit, int $divisor = 100): string
    {
        return $this->format($subunit / $divisor);
    }
}
