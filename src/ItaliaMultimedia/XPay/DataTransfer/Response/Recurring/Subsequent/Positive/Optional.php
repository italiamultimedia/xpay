<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive;

final readonly class Optional
{
    public function __construct(
        // May be empty string in subsequent payments
        public string $nazione,
        // can be empty string
        public string $regione,
        // can be empty string
        public string $tipoProdotto,
        // can be empty string
        public string $ppo,
    ) {
    }
}
