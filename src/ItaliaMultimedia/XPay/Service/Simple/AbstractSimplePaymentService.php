<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service\Simple;

use ItaliaMultimedia\XPay\Contract\Simple\SimplePaymentServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\Configuration;
use ItaliaMultimedia\XPay\Service\AbstractPaymentService;
use Override;

use function sprintf;

/**
 * psalm: "Non-readonly class ItaliaMultimedia\XPay\Service\Simple\AbstractSimplePaymentService
 * `may not inherit from readonly class ItaliaMultimedia\XPay\Service\AbstractPaymentService"`
 * 'psalm: "Class ItaliaMultimedia\XPay\Service\Simple\AbstractSimplePaymentService'
 * 'may not inherit from final class ItaliaMultimedia\XPay\Service\AbstractPaymentService"'
 * However AbstractPaymentService is neither readonly nor final.
 *
 * @psalm-suppress InvalidExtendClass
 */
abstract class AbstractSimplePaymentService extends AbstractPaymentService implements SimplePaymentServiceInterface
{
    #[Override]
    public function getSimplePaymentStartUrl(): string
    {
        return sprintf(
            '%s%s',
            $this->getApiBaseUrl(),
            Configuration::SIMPLE_PAYMENT_API_ENDPOINT,
        );
    }
}
