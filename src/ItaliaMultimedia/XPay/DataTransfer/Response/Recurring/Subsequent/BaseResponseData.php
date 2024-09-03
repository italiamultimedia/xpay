<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent;

use ItaliaMultimedia\XPay\Enum\Esito;

final readonly class BaseResponseData
{
    public function __construct(
        // 'OK', 'KO'.
        public Esito $esito,
        // Empty on error. `string(0) ""`
        public string $idOperazione,
        // Empty string on error. `string(1) " "`
        public string $mac,
        public string $timeStamp,
    ) {
    }
}
