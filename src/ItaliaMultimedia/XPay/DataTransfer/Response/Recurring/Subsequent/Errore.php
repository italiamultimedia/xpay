<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent;

final readonly class Errore
{
    public function __construct(public int $codice, public string $messaggio)
    {
    }
}
