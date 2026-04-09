<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartService $cartService,
    ) {}

    /**
     * Builds and persists an Order from the current cart + checkout address.
     * Does NOT flush — the caller must flush after attaching a Payment.
     */
    public function createFromCart(
        array $shippingAddress,
        string $paymentProvider,
        ?User $user = null,
        ?string $guestToken = null,
    ): Order {
        $cart  = $this->cartService->getCart();

        if (empty($cart)) {
            throw new \LogicException('Cannot create an order from an empty cart.');
        }

        $order = new Order();
        $order->setUser($user);
        $order->setGuestToken($guestToken);
        $order->setShippingAddress($shippingAddress);
        $order->setPaymentProvider($paymentProvider);
        $order->setStatus(OrderStatus::AWAITING_PAYMENT);

        $subtotal = 0.0;

        foreach ($cart as $row) {
            $product = $row['id']
                ? $this->em->find(Product::class, $row['id'])
                : null;

            $unitPrice = (float) $row['price'];
            $qty       = (int) $row['quantity'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $item = new OrderItem();
            $item->setProduct($product);
            $item->setProductName($row['name']);
            $item->setUnitPrice((string) $unitPrice);
            $item->setQuantity($qty);
            $item->setLineTotal((string) $lineTotal);

            $order->addItem($item);
            $this->em->persist($item);
        }

        $order->setSubtotal((string) $subtotal);
        $order->setTotal((string) $subtotal);   // Extend here for tax/shipping if needed

        $this->em->persist($order);

        return $order;
    }
}
