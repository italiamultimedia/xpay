<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service;

use ItaliaMultimedia\XPay\Contract\PaymentServiceInterface;
use ItaliaMultimedia\XPay\Contract\RequestInputServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\Configuration;
use ItaliaMultimedia\XPay\DataTransfer\PaymentSystemSettings;
use ItaliaMultimedia\XPay\DataTransfer\Request\RequestInput;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

use function hash;
use function microtime;
use function sha1;
use function sprintf;
use function substr;

abstract class AbstractPaymentService implements PaymentServiceInterface
{
    abstract protected function createCancelUrl(string $orderId): string;

    abstract protected function createNotificationUrl(string $orderId): string;

    abstract protected function createReturnUrl(string $orderId): string;

    public function __construct(
        protected LoggerInterface $logger,
        protected PaymentSystemSettings $paymentSystemSettings,
        protected RequestInputServiceInterface $requestInputService,
    ) {
    }

    /**
     * Create request parameters to use for the initial payment request.
     * Xpay: Initiate payment (Pagamento semplice > Avvio pagamento)
     *
     * @return array<string,int|string>
     */
    public function createPaymentRequestParameters(string $languageCode, string $orderId, float $orderTotal): array
    {
        $codTrans = $this->generateCodTrans();
        $orderTotalInCents = (int) ($orderTotal * 100);

        return [
            'alias' => $this->paymentSystemSettings->alias,
            'codTrans' => $codTrans,
            'divisa' => Configuration::CURRENCY,
            // Total must be in cents
            'importo' => $orderTotalInCents,
            'languageId' => $this->getLanguageId($languageCode),
            'mac' => $this->generatePaymentRequestMac($codTrans, $orderTotalInCents),
            'url' => $this->createReturnUrl($orderId),
            'urlpost' => $this->createNotificationUrl($orderId),
            'url_back' => $this->createCancelUrl($orderId),
        ];
    }

    public function getApiUrl(): string
    {
        return match ($this->paymentSystemSettings->environment) {
            Configuration::ENVIRONMENT_TEST => Configuration::API_URL_TEST,
            Configuration::ENVIRONMENT_PRODUCTION => Configuration::API_URL_PRODUCTION,
            default => throw new UnexpectedValueException('Unhandled environment.'),
        };
    }

    public function validateTransaction(): bool
    {
        $calculatedMac = $this->requestInputService->getValidatedString(RequestInput::MAC);
        $inputMac = $this->generatePaymentResponseMacFromRequestInput();

        if ($calculatedMac !== $inputMac) {
            throw new UnexpectedValueException('Invalid transaction data.');
        }

        return true;
    }

    /**
     * Generate `codTrans`.
     */
    protected function generateCodTrans(): string
    {
        // 30 is the maximum length accepted by Xpay.
        return substr(hash('sha256', microtime(), false), 0, 30);
    }

    private function generatePaymentRequestMac(string $codTrans, int $orderTotalInCents): string
    {
        return sha1(
            sprintf(
                'codTrans=%sdivisa=%simporto=%d%s',
                $codTrans,
                Configuration::CURRENCY,
                $orderTotalInCents,
                $this->paymentSystemSettings->macCalculationKey,
            ),
        );
    }

    private function generatePaymentResponseMacFromRequestInput(): string
    {
        return sha1(
            sprintf(
                'codTrans=%sesito=%simporto=%sdivisa=%sdata=%sorario=%scodAut=%s%s',
                $this->requestInputService->getValidatedString(RequestInput::COD_TRANS),
                $this->requestInputService->getValidatedString(RequestInput::ESITO),
                $this->requestInputService->getValidatedString(RequestInput::IMPORTO),
                $this->requestInputService->getValidatedString(RequestInput::DIVISA),
                $this->requestInputService->getValidatedString(RequestInput::DATA),
                $this->requestInputService->getValidatedString(RequestInput::ORARIO),
                $this->requestInputService->getValidatedString(RequestInput::COD_AUT),
                $this->paymentSystemSettings->macCalculationKey,
            ),
        );
    }

    private function getLanguageId(string $languageCode): string
    {
        return match ($languageCode) {
            'it' => 'ITA',
            default => 'ENG',
        };
    }
}
