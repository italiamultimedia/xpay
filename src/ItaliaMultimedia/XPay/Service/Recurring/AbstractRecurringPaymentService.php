<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service\Recurring;

use ItaliaMultimedia\XPay\Contract\Recurring\RecurringPaymentServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\Configuration;
use ItaliaMultimedia\XPay\Service\AbstractPaymentService;

use function date;
use function sha1;
use function sprintf;

abstract class AbstractRecurringPaymentService extends AbstractPaymentService implements
    RecurringPaymentServiceInterface
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
    ): array {
        $paymentRequestParameters = $this->createPaymentRequestParameters($languageCode, $numContratto, $orderTotal);

        /**
         * num_contratto
         *
         * Codice univoco assegnato dal merchant per l'abbinamento
         * con l'archivio contenente i dati sensibili della carta di credito.
         * AN MIN 5 MAX 30 Escluso il carattere + e gli apici
         */
        $paymentRequestParameters['num_contratto'] = $numContratto;
        $paymentRequestParameters['tipo_servizio'] = 'paga_multi';
        $paymentRequestParameters['tipo_richiesta'] = 'PP';

        return $paymentRequestParameters;
    }

    /**
     * Create request parameters to use for the subsequent payment request.
     * Xpay: Initiate payment (Pagamento ricorrente > Pagamenti successivi)
     *
     * @return array<string,int|string>
     */
    public function createSubsequentPaymentRequestParameters(string $numeroContratto, float $orderTotal): array
    {
        // Can not use createPaymentRequestParameters because the filed names are different.

        $codTrans = $this->generateCodTrans();
        $orderTotalInCents = (int) ($orderTotal * 100);
        $timeStamp = date('Uv');

        return [
            // "Alias assegnato da Nexi al merchant"
            'apiKey' => $this->paymentSystemSettings->alias,
            // "Identificativo transazione assegnato dal merchant "
            'codiceTransazione' => $codTrans,
            // "Il codice della divisa in cui l'importo è espresso."
            'divisa' => Configuration::CURRENCY_CODE,
            // "Importo da autorizzare espresso in centesimi di euro senza separatore,
            // i primi 2 numeri a destra rappresentano gli euro cent, es.: 5000 corrisponde a 50,00 €"
            'importo' => $orderTotalInCents,
            // "Message Code Authentication Campo di firma della transazione."
            'mac' => $this->generatePaymentRequestMacRecurringSubsequent(
                $codTrans,
                $numeroContratto,
                $orderTotalInCents,
                $timeStamp,
            ),
            // "Codice che consente a Nexi di salvare l'abbinamento tra l'utente e la carta di pagamento utilizzata"
            'numeroContratto' => $numeroContratto,
            // "Timestamp in formato millisecondi "
            'timeStamp' => $timeStamp,
        ];
    }

    public function getRecurringPaymentInitialUrl(): string
    {
        return sprintf(
            '%s%s',
            $this->getApiBaseUrl(),
            // Not an error, url is the same as the simple payment.
            Configuration::SIMPLE_PAYMENT_API_ENDPOINT,
        );
    }

    public function getRecurringPaymentSubsequentUrl(): string
    {
        return sprintf(
            '%s%s',
            $this->getApiBaseUrl(),
            Configuration::RECURRING_PAYMENT_SUBSEQUENT_API_ENDPOINT,
        );
    }

    private function generatePaymentRequestMacRecurringSubsequent(
        string $codiceTransazione,
        string $numeroContratto,
        int $orderTotalInCents,
        string $timeStamp,
    ): string {
        return sha1(
            sprintf(
                'apiKey=%snumeroContratto=%scodiceTransazione=%simporto=%ddivisa=%dtimeStamp=%s%s',
                $this->paymentSystemSettings->alias,
                $numeroContratto,
                $codiceTransazione,
                $orderTotalInCents,
                Configuration::CURRENCY_CODE,
                $timeStamp,
                $this->paymentSystemSettings->macCalculationKey,
            ),
        );
    }
}
