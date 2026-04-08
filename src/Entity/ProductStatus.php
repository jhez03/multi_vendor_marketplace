<?php

namespace App\Entity;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case DRAFT = 'draft';
    case SUSPENDED = 'Discontinued';
}
