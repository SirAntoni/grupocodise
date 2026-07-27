<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly Product $product,
        public readonly float $requested,
        public readonly float $available,
    ) {
        parent::__construct(sprintf(
            'Stock insuficiente para %s (%s): se requieren %s y hay %s disponibles.',
            $product->name,
            $product->code,
            rtrim(rtrim(number_format($requested, 2), '0'), '.'),
            rtrim(rtrim(number_format($available, 2), '0'), '.'),
        ));
    }
}
