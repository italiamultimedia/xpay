<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent;

abstract readonly class AbstractResponseData
{
    public function __construct(public BaseResponseData $baseResponseData,)
    {
    }
}
