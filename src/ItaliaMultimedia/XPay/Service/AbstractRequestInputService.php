<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service;

use ItaliaMultimedia\XPay\Contract\RequestInputServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\Configuration;
use ItaliaMultimedia\XPay\DataTransfer\Request\Esito;
use ItaliaMultimedia\XPay\DataTransfer\Request\RequestInput;
use OutOfBoundsException;
use UnexpectedValueException;
use WebServCo\Data\Contract\Extraction\DataExtractionContainerInterface;

use function in_array;
use function preg_match;

/**
 * Process request input into variables.
 */
abstract class AbstractRequestInputService implements RequestInputServiceInterface
{
    public const REQUEST_INPUT_BLANKABLE_FIELDS = [RequestInput::COD_AUT];

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $parsedBody
     * @param array<mixed> $queryParams
     * @phpcs:enable
     */
    public function __construct(
        private DataExtractionContainerInterface $dataExtractionContainer,
        private array $parsedBody,
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

    /**
     * @return non-empty-string
     */
    protected function getValidationRule(string $key): string
    {
        return match ($key) {
            RequestInput::COD_AUT => '/^[A-Z0-9]{2,6}$/',
            RequestInput::COD_TRANS => '/^[a-z0-9]{2,30}$/',
            RequestInput::DATA => '/^[0-9]{8}$/',
            RequestInput::IMPORTO => '/^[0-9]{3,8}$/',
            RequestInput::MAC => '/^[a-z0-9]{40}$/',
            RequestInput::ORARIO => '/^[0-9]{6}$/',
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

    private function validateEsito(string $value): bool
    {
        if (!in_array($value, Esito::VALUES, true)) {
            throw new UnexpectedValueException('Invalid data.');
        }

        return true;
    }
}
