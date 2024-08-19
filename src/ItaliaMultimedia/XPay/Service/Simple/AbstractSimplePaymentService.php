<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service\Simple;

use ItaliaMultimedia\XPay\Contract\Simple\SimplePaymentServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\Configuration;
use ItaliaMultimedia\XPay\Service\AbstractPaymentService;

use function sprintf;

abstract class AbstractSimplePaymentService extends AbstractPaymentService implements SimplePaymentServiceInterface
{
    public function getSimplePaymentStartUrl(): string
    {
        return sprintf(
            '%s%s',
            $this->getApiBaseUrl(),
            Configuration::SIMPLE_PAYMENT_API_ENDPOINT,
        );
    }
}
