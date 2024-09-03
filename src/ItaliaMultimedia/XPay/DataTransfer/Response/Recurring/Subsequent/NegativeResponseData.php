<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent;

final readonly class NegativeResponseData extends AbstractResponseData
{
    public function __construct(BaseResponseData $baseResponseData, public Errore $errore,)
    {
        parent::__construct($baseResponseData);
    }
}
