<?php

declare(strict_types=1);

namespace ItaliaMultimedia\XPay\Service\Recurring;

use Fig\Http\Message\RequestMethodInterface;
use ItaliaMultimedia\XPay\Contract\Recurring\RecurringPaymentServiceInterface;
use ItaliaMultimedia\XPay\DataTransfer\PaymentSystemSettings;
use ItaliaMultimedia\XPay\DataTransfer\Response\Recurring\Subsequent\AbstractResponseData;
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
final class SubsequentPaymentService extends AbstractSubsequentPaymentService
{
    private ?ResponseInterface $response;

    public function __construct(
        private ClientInterface $httpClient,
        DataExtractionContainerInterface $dataExtractionContainer,
        private PaymentSystemSettings $paymentSystemSettings,
        private RecurringPaymentServiceInterface $paymentService,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
        parent::__construct($dataExtractionContainer);
    }

    public function executeSubsequentPayment(
        string $numeroContratto,
        float $orderTotal,
        string $scadenza,
    ): AbstractResponseData {
        $request = $this->createRequest($numeroContratto, $orderTotal, $scadenza);

        $this->response = $this->executeRequest($request);

        $this->validateResponseStatusCode($this->response);

        $responseBodyAsArray = $this->getResponseBodyAsArray($this->response);

        $responseData = $this->getCompleteResponseData($responseBodyAsArray);

        $macCalculated = $this->generatePaymentResponseMacFromRequestInputRecurringSubsequent(
            $responseData->baseResponseData->esito,
            $responseData->baseResponseData->idOperazione,
            $responseData->baseResponseData->timeStamp,
        );

        // Validate mac
        if ($responseData->baseResponseData->mac !== $macCalculated) {
            throw new UnexpectedValueException('Invalid transaction data.');
        }

        return $responseData;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * json_encode: Despite using JSON_THROW_ON_ERROR flag, Phan 5.4.1 throws PhanPossiblyFalseTypeArgument.
     * If adding is_string check, PHPStan and Psalm instead throw error.
     *
     * @suppress PhanPossiblyFalseTypeArgument
     * @suppress PhanPossiblyFalseTypeArgumentInternal
     */
    private function createRequest(string $numeroContratto, float $orderTotal, string $scadenza): RequestInterface
    {
        $request = $this->requestFactory->createRequest(
            RequestMethodInterface::METHOD_POST,
            $this->paymentService->getRecurringPaymentSubsequentUrl(),
        );

        $requestParameters = $this->paymentService->createSubsequentPaymentRequestParameters(
            $numeroContratto,
            $orderTotal,
            $scadenza,
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

    private function executeRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }

    private function generatePaymentResponseMacFromRequestInputRecurringSubsequent(
        Esito $esito,
        string $idOperazione,
        string $timeStamp,
    ): string {
        return sha1(
            sprintf(
                'esito=%sidOperazione=%stimeStamp=%s%s',
                $esito->value,
                $idOperazione,
                $timeStamp,
                $this->paymentSystemSettings->macCalculationKey,
            ),
        );
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
