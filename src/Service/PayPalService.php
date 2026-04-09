<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * PayPal Orders API v2 integration — uses symfony/http-client (already in composer).
 * No extra SDK needed.
 *
 * Required .env vars:
 *   PAYPAL_CLIENT_ID=AZD...
 *   PAYPAL_CLIENT_SECRET=EFG...
 *   PAYPAL_MODE=sandbox          # or 'live'
 *
 * Sandbox setup:
 *   1. Go to https://developer.paypal.com/dashboard/
 *   2. Create a REST API application → get Client ID + Secret
 *   3. Use sandbox buyer credentials to test payments
 *   4. Webhook: https://developer.paypal.com/dashboard/webhooksSimulator
 *
 * Test buyer: use the auto-created sandbox personal account from your dashboard.
 */
final class PayPalService
{
    private const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    private const LIVE_URL    = 'https://api-m.paypal.com';

    private string $baseUrl;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(PAYPAL_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(PAYPAL_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        #[Autowire('%env(PAYPAL_MODE)%')]
        private readonly string $mode,
    ) {
        $this->baseUrl = $mode === 'live' ? self::LIVE_URL : self::SANDBOX_URL;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    // ── OAuth ────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/v1/oauth2/token', [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body'       => ['grant_type' => 'client_credentials'],
        ]);

        return $response->toArray()['access_token'];
    }

    // ── Orders API ───────────────────────────────────────────────

    /**
     * Creates a PayPal order and attaches approval link to the Payment entity.
     * Returns the approval URL to redirect the buyer to PayPal.
     */
    public function createOrder(
        Order $order,
        Payment $payment,
        string $returnUrl,
        string $cancelUrl,
    ): string {
        $token    = $this->getAccessToken();
        $currency = strtoupper($payment->getCurrency());

        // PayPal uses decimal string amounts
        $amount   = number_format((float) $order->getTotal(), 2, '.', '');

        $response = $this->httpClient->request('POST', $this->baseUrl . '/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'PayPal-Request-Id' => $order->getOrderNumber(), // idempotency
            ],
            'json' => [
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->getOrderNumber(),
                    'description'  => 'MultiVendor Marketplace order ' . $order->getOrderNumber(),
                    'amount'       => [
                        'currency_code' => $currency,
                        'value'         => $amount,
                    ],
                ]],
                'application_context' => [
                    'brand_name'          => 'MultiVendor Marketplace',
                    'landing_page'        => 'NO_PREFERENCE',
                    'user_action'         => 'PAY_NOW',
                    'return_url'          => $returnUrl,
                    'cancel_url'          => $cancelUrl,
                ],
            ],
        ]);

        $data         = $response->toArray();
        $approvalLink = '';

        foreach ($data['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalLink = $link['href'];
                break;
            }
        }

        $payment->setProviderTransactionId($data['id']);
        $payment->setProviderClientSecret($approvalLink);   // reuse field for approval URL
        $payment->setStatus(PaymentStatus::PENDING);

        return $approvalLink;
    }

    /**
     * Captures a PayPal order after the buyer approves it.
     * Call this on the return URL handler.
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $token    = $this->getAccessToken();

        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId . '/capture',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]
        );

        return $response->toArray();
    }

    /**
     * Fetches the current status of a PayPal order.
     */
    public function getOrderDetails(string $paypalOrderId): array
    {
        $token    = $this->getAccessToken();

        $response = $this->httpClient->request(
            'GET',
            $this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId,
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]
        );

        return $response->toArray();
    }

    /**
     * Maps a PayPal capture status string to our PaymentStatus.
     */
    public function mapCaptureStatus(string $paypalStatus): PaymentStatus
    {
        return match ($paypalStatus) {
            'COMPLETED' => PaymentStatus::SUCCEEDED,
            'DECLINED'  => PaymentStatus::FAILED,
            'PENDING'   => PaymentStatus::PROCESSING,
            default     => PaymentStatus::FAILED,
        };
    }
}
