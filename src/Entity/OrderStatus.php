<?php

namespace App\Entity;

enum OrderStatus: string
{
    case PENDING          = 'pending';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case PAID             = 'paid';
    case PROCESSING       = 'processing';
    case SHIPPED          = 'shipped';
    case DELIVERED        = 'delivered';
    case CANCELLED        = 'cancelled';
    case REFUNDED         = 'refunded';
}
