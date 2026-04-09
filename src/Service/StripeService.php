<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Stripe integration — uses stripe/stripe-php SDK.
 *
 * Install:  composer require stripe/stripe-php
 *
 * Required .env vars:
 *   STRIPE_SECRET_KEY=sk_test_...
 *   STRIPE_PUBLISHABLE_KEY=pk_test_...
 *   STRIPE_WEBHOOK_SECRET=whsec_...
 *
 * Sandbox test cards:
 *   Success:  4242 4242 4242 4242  (any future date, any CVC)
 *   3DS:      4000 0027 6000 3184
 *   Decline:  4000 0000 0000 9995
 */
final class StripeService
{
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $secretKey,
        #[Autowire('%env(STRIPE_PUBLISHABLE_KEY)%')]
        private readonly string $publishableKey,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        private readonly string $webhookSecret,
    ) {
        Stripe::setApiKey($this->secretKey);
        Stripe::setApiVersion('2024-06-20');
    }

    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * Creates a Stripe PaymentIntent and attaches it to the Payment entity.
     * The caller must persist + flush.
     */
    public function createPaymentIntent(Order $order, Payment $payment): PaymentIntent
    {
        $intent = PaymentIntent::create([
            'amount'   => $order->getTotalInCents(),
            'currency' => strtolower($payment->getCurrency()),
            'metadata' => [
                'order_id'     => $order->getId(),
                'order_number' => $order->getOrderNumber(),
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $payment->setProviderTransactionId($intent->id);
        $payment->setProviderClientSecret($intent->client_secret);
        $payment->setStatus(PaymentStatus::PENDING);

        return $intent;
    }

    /**
     * Validates and parses a Stripe webhook payload.
     *
     * Usage in controller:
     *   $event = $stripeService->constructWebhookEvent(
     *       $request->getContent(),
     *       $request->headers->get('Stripe-Signature')
     *   );
     */
    public function constructWebhookEvent(string $payload, ?string $sigHeader): \Stripe\Event
    {
        if ($sigHeader === null) {
            throw new \InvalidArgumentException('Missing Stripe-Signature header.');
        }

        try {
            return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        } catch (SignatureVerificationException $e) {
            throw new \RuntimeException('Invalid webhook signature: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Maps a Stripe event type to our PaymentStatus.
     * Returns null when the event type is not relevant to payment status.
     */
    public function mapEventToStatus(string $eventType): ?PaymentStatus
    {
        return match ($eventType) {
            'payment_intent.succeeded'             => PaymentStatus::SUCCEEDED,
            'payment_intent.payment_failed'        => PaymentStatus::FAILED,
            'payment_intent.processing'            => PaymentStatus::PROCESSING,
            'payment_intent.requires_action'       => PaymentStatus::REQUIRES_ACTION,
            'charge.refunded'                      => PaymentStatus::REFUNDED,
            default                                => null,
        };
    }
}
