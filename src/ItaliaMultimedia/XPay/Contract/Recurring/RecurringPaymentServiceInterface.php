<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Contract\Recurring;

interface RecurringPaymentServiceInterface
{
    /**
     * Create request parameters to use for the initial payment request.
     * Xpay: Initiate payment (Pagamento ricorrente > Primo pagamento > Avvio pagamento)
     *
     * @return array<string,int|string>
     */
    public function createInitialPaymentRequestParameters(
        string $languageCode,
        string $numContratto,
        float $orderTotal,
    ): array;

    /**
     * Create request parameters to use for the subsequent payment request.
     * Xpay: Initiate payment (Pagamento ricorrente > Pagamenti successivi)
     *
     * @return array<string,int|string>
     */
    public function createSubsequentPaymentRequestParameters(string $numeroContratto, float $orderTotal,): array;

    public function getRecurringPaymentInitialUrl(): string;

    public function getRecurringPaymentSubsequentUrl(): string;
}
