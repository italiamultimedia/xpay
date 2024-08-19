<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent;

final readonly class ResponseData
{
    public function __construct(
        // 'OK', 'KO'.
        public string $esito,
        // Empty on error. `string(0) ""`
        public string $idOperazione,
        // Empty string on error. `string(1) " "`
        public string $mac,
        public string $timeStamp,
    ) {
    }
}
