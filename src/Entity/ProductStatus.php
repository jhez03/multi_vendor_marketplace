<?php

namespace App\Entity;

enum ProductStatus: string
{
    case ACTIVE = 'InStock';
    case INACTIVE = 'OutOfStock';
    case SUSPENDED = 'Discontinued';
}
