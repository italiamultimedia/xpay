<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent;

use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive\Mandatory;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive\Optional;

final readonly class PositiveResponseData extends AbstractResponseData
{
    public function __construct(
        BaseResponseData $baseResponseData,
        public Mandatory $mandatory,
        public Optional $optional,
    ) {
        parent::__construct($baseResponseData);
    }
}
