<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service\Recurring;

use Fig\Http\Message\RequestMethodInterface;
use ItaliaMultimedia\XPay\Contract\Recurring\RecurringPaymentServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\PaymentSystemSettings;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\ResponseData;
use ItaliaMultimedia\XPay\Enum\Esito;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use UnexpectedValueException;
use WebServCo\Data\Contract\Extraction\DataExtractionContainerInterface;

use function is_array;
use function json_decode;
use function json_encode;
use function sha1;
use function sprintf;
use function strlen;

use const JSON_THROW_ON_ERROR;

/**
 * Recurring payment sys.
 *
 * Executes a subsequent payment using XPay API.
 */
final class SubsequentPaymentService
{
    public function __construct(
        private ClientInterface $httpClient,
        private DataExtractionContainerInterface $dataExtractionContainer,
        private PaymentSystemSettings $paymentSystemSettings,
        private RecurringPaymentServiceInterface $paymentService,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function executeSubsequentPayment(string $numeroContratto, float $orderTotal): Esito
    {
        $request = $this->createRequest($numeroContratto, $orderTotal);

        $response = $this->getResponse($request);

        $this->validateResponseStatusCode($response);

        $responseBodyAsArray = $this->getResponseBodyAsArray($response);

        $responseData = $this->getResponseData($responseBodyAsArray);

        $this->validateResponseData($responseBodyAsArray, $responseData);

        $macCalculated = $this->generatePaymentResponseMacFromRequestInputRecurringSubsequent(
            $responseData->esito,
            $responseData->idOperazione,
            $responseData->timeStamp,
        );

        // Validate mac
        if ($responseData->mac !== $macCalculated) {
            throw new UnexpectedValueException('Invalid transaction data.');
        }

        return $this->getValidatedEsito($responseData->esito);
    }

    /**
     * json_encode: Despite using JSON_THROW_ON_ERROR flag, Phan 5.4.1 throws PhanPossiblyFalseTypeArgument.
     * If adding is_string check, PHPStan and Psalm instead throw error.
     *
     * @suppress PhanPossiblyFalseTypeArgument
     * @suppress PhanPossiblyFalseTypeArgumentInternal
     */
    private function createRequest(string $numeroContratto, float $orderTotal): RequestInterface
    {
        $request = $this->requestFactory->createRequest(
            RequestMethodInterface::METHOD_POST,
            $this->paymentService->getRecurringPaymentSubsequentUrl(),
        );

        $requestParameters = $this->paymentService->createSubsequentPaymentRequestParameters(
            $numeroContratto,
            $orderTotal,
        );
        $requestBody = json_encode($requestParameters, JSON_THROW_ON_ERROR);

        $request = $request->withBody($this->streamFactory->createStream($requestBody));

        $request = $request
            ->withHeader('Content-Length', (string) strlen($requestBody))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            // Leave empty string, cURL will list all supported
            ->withHeader('Accept-Encoding', '');

        return $request;
    }

    private function generatePaymentResponseMacFromRequestInputRecurringSubsequent(
        string $esito,
        string $idOperazione,
        string $timeStamp,
    ): string {
        return sha1(
            sprintf(
                'esito=%sidOperazione=%stimeStamp=%s%s',
                $esito,
                $idOperazione,
                $timeStamp,
                $this->paymentSystemSettings->macCalculationKey,
            ),
        );
    }

    private function getResponse(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     */
    private function getResponseBodyAsArray(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            // Possible situation: the body contents were read elsewhere and the stream was not rewinded.
            throw new UnexpectedValueException('Response body is empty.');
        }

        $array = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($array)) {
            throw new UnexpectedValueException('Error decoding JSON data.');
        }

        return $array;
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    private function getResponseData(array $responseBodyAsArray): ResponseData
    {
        return new ResponseData(
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'esito'),
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'idOperazione'),
            $this->dataExtractionContainer->getLooseArrayDataExtractionService()
                ->getString($responseBodyAsArray, 'mac'),
            $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                ->getNonEmptyString($responseBodyAsArray, 'timeStamp'),
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

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $responseBodyAsArray
     */
    private function validateResponseData(array $responseBodyAsArray, ResponseData $responseData): bool
    {
        if ($responseData->esito !== 'OK' || $responseData->idOperazione === '' || $responseData->mac === '') {
            // Response contains errors. Try to get more information.
            throw new UnexpectedValueException(
                $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                    ->getNonEmptyString($responseBodyAsArray, 'errore/messaggio', 'Unknown XPay error.'),
                $this->dataExtractionContainer->getLooseArrayNonEmptyDataExtractionService()
                    ->getNonEmptyInt($responseBodyAsArray, 'errore/codice', 500),
            );
        }

        return true;
    }

    private function validateResponseStatusCode(ResponseInterface $response): bool
    {
        /**
         * Get status code.
         *
         * Note: XPay returns 200 also on errors, so this is probably useless.
         */
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new UnexpectedValueException('Response does not contain 200 status code.');
        }

        return true;
    }
}
