<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service;

use ItaliaMultimedia\XPay\Contract\RequestInputServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\Configuration;
use ItaliaMultimedia\XPay\DataTransfer\PaymentSystemSettings;
use ItaliaMultimedia\XPay\DataTransfer\Request\RequestInput;
use ItaliaMultimedia\XPay\Enum\Esito;
use OutOfBoundsException;
use UnexpectedValueException;
use WebServCo\Data\Contract\Extraction\DataExtractionContainerInterface;

use function in_array;
use function preg_match;
use function sha1;
use function sprintf;

/**
 * Process request input into variables.
 */
abstract class AbstractRequestInputService implements RequestInputServiceInterface
{
    public const array REQUEST_INPUT_BLANKABLE_FIELDS = [RequestInput::COD_AUT];

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $parsedBody
     * @param array<mixed> $queryParams
     * @phpcs:enable
     */
    public function __construct(
        private DataExtractionContainerInterface $dataExtractionContainer,
        private array $parsedBody,
        protected PaymentSystemSettings $paymentSystemSettings,
        private array $queryParams,
    ) {
    }

    public function getValidatedString(string $key): string
    {
        $value = $this->getStringFromAnySource($key);

        // Some fields can be blank.
        if (in_array($key, self::REQUEST_INPUT_BLANKABLE_FIELDS, true) && $value === '') {
            return $value;
        }

        $this->validateInput($key, $value);

        return $value;
    }

    public function validateInputMac(): bool
    {
        $calculatedMac = $this->getValidatedString(RequestInput::MAC);
        $inputMac = $this->generatePaymentResponseMacFromRequestInput();

        if ($calculatedMac !== $inputMac) {
            throw new UnexpectedValueException('Invalid transaction data.');
        }

        return true;
    }

    /**
     * @return non-empty-string
     */
    protected function getValidationRule(string $key): string
    {
        return match ($key) {
            RequestInput::COD_AUT => '/^[a-zA-Z0-9]{2,6}$/',
            RequestInput::COD_TRANS => '/^[a-z0-9]{2,30}$/',
            RequestInput::DATA => '/^[0-9]{8}$/',
            RequestInput::IMPORTO => '/^[0-9]{1,8}$/',
            RequestInput::MAC => '/^[a-z0-9]{40}$/',
            RequestInput::ORARIO => '/^[0-9]{6}$/',
            RequestInput::RECURRING_ID_INITIAL, RequestInput::RECURRING_ID_SUBSEQUENT => '/^[a-z0-9]{5,30}$/',
            default => throw new UnexpectedValueException('Unhandled key.'),
        };
    }

    protected function validateInput(string $key, string $value): bool
    {
        if ($key === RequestInput::DIVISA) {
            return $this->validateDivisa($value);
        }

        if ($key === RequestInput::ESITO) {
            return $this->validateEsito($value);
        }

        if (preg_match($this->getValidationRule($key), $value) !== 1) {
            throw new UnexpectedValueException('Invalid data.');
        }

        return true;
    }

    private function generatePaymentResponseMacFromRequestInput(): string
    {
        return sha1(
            sprintf(
                'codTrans=%sesito=%simporto=%sdivisa=%sdata=%sorario=%scodAut=%s%s',
                $this->getValidatedString(RequestInput::COD_TRANS),
                $this->getValidatedString(RequestInput::ESITO),
                $this->getValidatedString(RequestInput::IMPORTO),
                $this->getValidatedString(RequestInput::DIVISA),
                $this->getValidatedString(RequestInput::DATA),
                $this->getValidatedString(RequestInput::ORARIO),
                $this->getValidatedString(RequestInput::COD_AUT),
                $this->paymentSystemSettings->macCalculationKey,
            ),
        );
    }

    /**
     * @throws \OutOfBoundsException if key does not exist
     */
    private function getStringFromAnySource(string $key): string
    {
        try {
            return $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($this->queryParams, $key);
        } catch (OutOfBoundsException) {
            return $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($this->parsedBody, $key);
        }
    }

    private function validateDivisa(string $value): bool
    {
        if ($value !== Configuration::CURRENCY) {
            throw new UnexpectedValueException('Invalid data.');
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @todo check PHPMD warning (no other way to init Enum)
     */
    private function validateEsito(string $value): bool
    {
        $esito = Esito::tryFrom($value);
        if ($esito === null) {
            throw new UnexpectedValueException('Invalid data.');
        }

        return true;
    }
}
