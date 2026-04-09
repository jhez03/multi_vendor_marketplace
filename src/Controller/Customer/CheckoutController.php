<?php

namespace App\Controller\Customer;

use App\Service\CartService;
use App\Service\OrderService;
use App\Service\PayPalService;
use App\Service\StripeService;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/checkout', name: 'app_checkout_')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly StripeService $stripeService,
        private readonly PayPalService $payPalService,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── Step 1: address + provider choice ───────────────────────

    #[Route('', name: 'index')]
    public function index(): Response
    {
        if ($this->cartService->getItemCount() === 0) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_index');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('customer/checkout/index.html.twig', [
            'user'              => $user,
            'profile'           => $user?->getCustomerProfile(),
            'cart'              => $this->cartService->getCart(),
            'subtotal'          => $this->cartService->getSubtotal(),
            'stripePublishKey'  => $this->stripeService->getPublishableKey(),
            'paypalClientId'    => $this->payPalService->getClientId(),
        ]);
    }

    // ── Step 2a: initiate Stripe ─────────────────────────────────

    #[Route('/stripe', name: 'stripe', methods: ['POST'])]
    public function stripe(Request $request): Response
    {
        if ($this->cartService->getItemCount() === 0) {
            return $this->redirectToRoute('app_cart_index');
        }

        $shippingAddress = $this->extractAddress($request);

        if ($errors = $this->validateAddress($shippingAddress)) {
            foreach ($errors as $e) {
                $this->addFlash('error', $e);
            }
            return $this->redirectToRoute('app_checkout_index');
        }

        $order   = $this->orderService->createFromCart(
            $shippingAddress,
            'stripe',
            $this->getUser(),
            $this->getGuestToken($request),
        );

        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setProvider('stripe');
        $payment->setAmount($order->getTotal());
        $payment->setCurrency('PHP');
        $payment->setStatus(PaymentStatus::PENDING);

        $this->em->persist($payment);

        $intent = $this->stripeService->createPaymentIntent($order, $payment);
        $this->em->flush();

        return $this->render('customer/checkout/stripe.html.twig', [
            'user'             => $this->getUser(),
            'profile'          => $this->getUser()?->getCustomerProfile(),
            'order'            => $order,
            'payment'          => $payment,
            'clientSecret'     => $intent->client_secret,
            'publishableKey'   => $this->stripeService->getPublishableKey(),
        ]);
    }

    // ── Step 2b: initiate PayPal ─────────────────────────────────

    #[Route('/paypal', name: 'paypal', methods: ['POST'])]
    public function paypal(Request $request): Response
    {
        if ($this->cartService->getItemCount() === 0) {
            return $this->redirectToRoute('app_cart_index');
        }

        $shippingAddress = $this->extractAddress($request);

        if ($errors = $this->validateAddress($shippingAddress)) {
            foreach ($errors as $e) {
                $this->addFlash('error', $e);
            }
            return $this->redirectToRoute('app_checkout_index');
        }

        $order   = $this->orderService->createFromCart(
            $shippingAddress,
            'paypal',
            $this->getUser(),
            $this->getGuestToken($request),
        );

        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setProvider('paypal');
        $payment->setAmount($order->getTotal());
        $payment->setCurrency('PHP');
        $payment->setStatus(PaymentStatus::PENDING);

        $this->em->persist($payment);

        $returnUrl = $this->generateUrl(
            'app_payment_paypal_return',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cancelUrl = $this->generateUrl(
            'app_checkout_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $approvalUrl = $this->payPalService->createOrder($order, $payment, $returnUrl, $cancelUrl);
        $this->em->flush();

        // Redirect buyer to PayPal approval page
        return $this->redirect($approvalUrl);
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function extractAddress(Request $request): array
    {
        return [
            'full_name'    => trim((string) $request->request->get('full_name', '')),
            'phone'        => trim((string) $request->request->get('phone', '')),
            'address_line' => trim((string) $request->request->get('address_line', '')),
            'city'         => trim((string) $request->request->get('city', '')),
            'province'     => trim((string) $request->request->get('province', '')),
            'postal_code'  => trim((string) $request->request->get('postal_code', '')),
        ];
    }

    private function validateAddress(array $address): array
    {
        $errors = [];
        $required = ['full_name', 'address_line', 'city', 'province', 'postal_code'];

        foreach ($required as $field) {
            if (empty($address[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        return $errors;
    }

    private function getGuestToken(Request $request): ?string
    {
        if ($this->getUser()) {
            return null;
        }

        $session = $request->getSession();
        $token   = $session->get('guest_token');

        if (!$token) {
            $token = bin2hex(random_bytes(16));
            $session->set('guest_token', $token);
        }

        return $token;
    }
    #[Route('/checkout/paypal/create-order', name: 'app_checkout_paypal_create', methods: ['POST'])]
    public function paypalCreateOrder(Request $request): Response
    {
        if ($this->cartService->getItemCount() === 0) {
            return $this->json(['error' => 'Empty cart'], 400);
        }

        $shippingAddress = $this->extractAddress($request);

        if ($errors = $this->validateAddress($shippingAddress)) {
            return $this->json(['error' => implode(', ', $errors)], 422);
        }

        $order = $this->orderService->createFromCart(
            $shippingAddress,
            'paypal',
            $this->getUser(),
            $this->getGuestToken($request),
        );

        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setProvider('paypal');
        $payment->setAmount($order->getTotal());
        $payment->setCurrency('PHP');
        $payment->setStatus(PaymentStatus::PENDING);

        $this->em->persist($payment);

        $returnUrl = $this->generateUrl(
            'app_payment_paypal_return',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $cancelUrl = $this->generateUrl(
            'app_payment_paypal_cancel',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->payPalService->createOrder($order, $payment, $returnUrl, $cancelUrl);
        $this->em->flush();

        return $this->json(['paypalOrderId' => $payment->getProviderTransactionId()]);
    }
}
