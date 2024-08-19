<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Contract\Simple;

interface SimplePaymentServiceInterface
{
    /**
     * Create request parameters to use for the initial payment request.
     * Xpay: Initiate payment (Pagamento semplice > Avvio pagamento)
     *
     * @return array<string,int|string>
     */
    public function createPaymentRequestParameters(string $languageCode, string $orderId, float $orderTotal): array;

    public function getSimplePaymentStartUrl(): string;
}
