<?php

namespace App\Controller\Customer;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
use App\Repository\PaymentRepository;
use App\Service\CartService;
use App\Service\PayPalService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly PayPalService $payPalService,
        private readonly CartService $cartService,
        private readonly EntityManagerInterface $em,
        private readonly PaymentRepository $paymentRepo,
    ) {}

    // ── Stripe: client confirms payment via Elements JS ──────────
    // No server-side step needed after createPaymentIntent —
    // Stripe Elements posts directly. We rely on the webhook below.

    // ── Stripe: success redirect ─────────────────────────────────

    #[Route('/payment/stripe/return', name: 'app_payment_stripe_return')]
    public function stripeReturn(Request $request): Response
    {
        $intentId = $request->query->get('payment_intent');

        if (!$intentId) {
            return $this->redirectToRoute('app_cart_index');
        }

        $payment = $this->paymentRepo->findOneBy(['providerTransactionId' => $intentId]);

        if (!$payment) {
            $this->addFlash('error', 'Payment not found.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Webhook will update status authoritatively; here we show the order page
        // even if webhook hasn't fired yet (optimistic redirect)
        $this->cartService->clear();

        return $this->redirectToRoute('app_order_thankyou', [
            'orderNumber' => $payment->getOrder()->getOrderNumber(),
        ]);
    }

    // ── Stripe: webhook ──────────────────────────────────────────

    /**
     * Stripe sends events here asynchronously.
     * CSRF protection is explicitly disabled for webhooks — Stripe signs the payload.
     *
     * Register URL in Stripe Dashboard:
     *   https://dashboard.stripe.com/webhooks → Add endpoint
     *   URL: https://yourdomain.com/webhooks/stripe
     *   Events to subscribe: payment_intent.succeeded, payment_intent.payment_failed
     */
    #[Route('/webhooks/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function stripeWebhook(Request $request): JsonResponse
    {
        try {
            $event = $this->stripeService->constructWebhookEvent(
                $request->getContent(),
                $request->headers->get('Stripe-Signature')
            );
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $newStatus = $this->stripeService->mapEventToStatus($event->type);

        if ($newStatus === null) {
            return $this->json(['received' => true]);   // Ignore unhandled event types
        }

        $intent  = $event->data->object;
        $payment = $this->paymentRepo->findOneBy(['providerTransactionId' => $intent->id]);

        if (!$payment) {
            return $this->json(['error' => 'Payment not found'], Response::HTTP_NOT_FOUND);
        }

        $this->updatePaymentAndOrder($payment, $newStatus, (array) $intent);

        return $this->json(['received' => true]);
    }

    // ── PayPal: buyer returns from PayPal ────────────────────────

    #[Route('/payment/paypal/return', name: 'app_payment_paypal_return')]
    public function paypalReturn(Request $request): Response
    {
        $paypalOrderId = $request->query->get('token');   // PayPal passes 'token' on return

        if (!$paypalOrderId) {
            $this->addFlash('error', 'PayPal payment was cancelled.');
            return $this->redirectToRoute('app_cart_index');
        }

        $payment = $this->paymentRepo->findOneBy(['providerTransactionId' => $paypalOrderId]);

        if (!$payment) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_cart_index');
        }

        try {
            $captureData   = $this->payPalService->captureOrder($paypalOrderId);
            $captureStatus = $captureData['purchase_units'][0]['payments']['captures'][0]['status'] ?? 'FAILED';
            $newStatus     = $this->payPalService->mapCaptureStatus($captureStatus);

            $this->updatePaymentAndOrder($payment, $newStatus, $captureData);
        } catch (\Throwable $e) {
            $this->updatePaymentAndOrder($payment, PaymentStatus::FAILED, []);
            $this->addFlash('error', 'PayPal capture failed. Please contact support.');
            return $this->redirectToRoute('app_cart_index');
        }

        $this->cartService->clear();

        return $this->redirectToRoute('app_order_thankyou', [
            'orderNumber' => $payment->getOrder()->getOrderNumber(),
        ]);
    }

    #[Route('/payment/paypal/cancel', name: 'app_payment_paypal_cancel')]
    public function paypalCancel(): Response
    {
        $this->addFlash('error', 'PayPal payment was cancelled. Your cart is saved.');
        return $this->redirectToRoute('app_checkout_index');
    }

    // ── Shared helper ────────────────────────────────────────────

    private function updatePaymentAndOrder(
        Payment $payment,
        PaymentStatus $newStatus,
        array $rawPayload,
    ): void {
        $payment->setStatus($newStatus);
        $payment->setRawPayload($rawPayload);

        $order = $payment->getOrder();

        $order->setStatus(match ($newStatus) {
            PaymentStatus::SUCCEEDED  => OrderStatus::PAID,
            PaymentStatus::FAILED     => OrderStatus::CANCELLED,
            PaymentStatus::PROCESSING => OrderStatus::AWAITING_PAYMENT,
            default                   => $order->getStatus(),
        });

        $this->em->flush();
    }
}
