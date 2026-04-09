<?php

namespace App\Entity;

enum PaymentStatus: string
{
    case PENDING   = 'pending';
    case REQUIRES_ACTION = 'requires_action';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED    = 'failed';
    case REFUNDED  = 'refunded';
    case CANCELLED = 'cancelled';
}
