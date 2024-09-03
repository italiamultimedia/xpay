<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service\Recurring;

use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\AbstractResponseData;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\BaseResponseData;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Errore;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\NegativeResponseData;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive\Mandatory;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\Positive\Optional;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\PositiveResponseData;
use ItaliaMultimedia\XPay\Enum\Esito;
use UnexpectedValueException;
use WebServCo\Data\Contract\Extraction\DataExtractionContainerInterface;

/**
 * Abstract class to reduce coupling between objects
 */
abstract class AbstractSubsequentPaymentService
{
    public function __construct(private DataExtractionContainerInterface $dataExtractionContainer)
    {
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    protected function getCompleteResponseData(array $responseBodyAsArray,): AbstractResponseData
    {
        $baseResponseData = $this->getBaseResponseData($responseBodyAsArray);
        if (
            $baseResponseData->esito !== Esito::OK
            || $baseResponseData->idOperazione === ''
            || $baseResponseData->mac === ''
        ) {
            return new NegativeResponseData(
                $baseResponseData,
                new Errore(
                    $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                        ->getNonEmptyInt($responseBodyAsArray, 'errore/codice', 500),
                    $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                        ->getNonEmptyString($responseBodyAsArray, 'errore/messaggio', 'Unknown XPay error.'),
                ),
            );
        }

        return $this->getPositiveResponseData($baseResponseData, $responseBodyAsArray);
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    private function getBaseResponseData(array $responseBodyAsArray): BaseResponseData
    {
        return new BaseResponseData(
            $this->getValidatedEsito(
                $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                    ->getNonEmptyString($responseBodyAsArray, 'esito'),
            ),
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'idOperazione'),
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'mac'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'timeStamp'),
        );
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    private function getPositiveMandatoryResponseData(array $responseBodyAsArray): Mandatory
    {
        return new Mandatory(
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'codiceAutorizzazione'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'data'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'ora'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'nazione'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'codiceConvenzione'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'brand'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'tipoTransazione'),
        );
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    private function getPositiveOptionalResponseData(array $responseBodyAsArray): Optional
    {
        return new Optional(
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'regione'),
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'tipoProdotto'),
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'ppo'),
        );
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    private function getPositiveResponseData(
        BaseResponseData $baseResponseData,
        array $responseBodyAsArray,
    ): PositiveResponseData {
        return new PositiveResponseData(
            $baseResponseData,
            $this->getPositiveMandatoryResponseData($responseBodyAsArray),
            $this->getPositiveOptionalResponseData($responseBodyAsArray),
        );
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @todo check PHPMD warning (no other way to init Enum)
     */
    private function getValidatedEsito(string $esito): Esito
    {
        // Validate esito
        $enum = Esito::tryFrom($esito);
        if ($enum === null) {
            throw new UnexpectedValueException('Invalid transaction data.');
        }

        return $enum;
    }
}
