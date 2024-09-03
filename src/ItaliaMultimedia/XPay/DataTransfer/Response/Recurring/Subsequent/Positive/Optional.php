<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive;

final readonly class Optional
{
    public function __construct(
        // can be empty string
        public string $regione,
        // can be empty string
        public string $tipoProdotto,
        // can be empty string
        public string $ppo,
    ) {
    }
}
