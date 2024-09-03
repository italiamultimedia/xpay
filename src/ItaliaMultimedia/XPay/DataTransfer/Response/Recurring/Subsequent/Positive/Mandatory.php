<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive;

final readonly class Mandatory
{
    public function __construct(
        public string $codiceAutorizzazione,
        public string $data,
        public string $ora,
        public string $nazione,
        public string $codiceConvenzione,
        public string $brand,
        public string $tipoTransazione,
    ) {
    }
}
