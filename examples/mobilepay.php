<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Api\Client;
use Api\Request\Encoder\JsonRequestBodyEncoder;
use Api\Request\Handler\AddHeaderHandler;
use Api\Request\Handler\ChainRequestHandler;
use Api\Request\Method;
use Api\Request\Request;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use Api\Response\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class GetPaymentPointRequest extends Request
{
    public function __construct(
        string $paymentId
    ) {
        parent::__construct(
            Method::GET,
            sprintf('/v1/payments/%s', $paymentId)
        );
    }
}

final readonly class GetPaymentPointResponse
{
    public function __construct(
        public string $paymentPointId,
        public string $paymentPointName,
        public string $state
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $payload = $response->body();

        // todo: add validation

        return new self(
            $payload['paymentPointId'],
            $payload['paymentPointName'],
            $payload['state'],
        );
    }
}

$psr18HttpClient = new Psr18Client();
$requestBodyEncoder = new JsonRequestBodyEncoder();
$responseBodyDecoder = new JsonResponseBodyDecoder();

$mobilePayClient = new Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('MOBILEPAY_API_HOST'),
    requestHandler: new ChainRequestHandler([
        new AddHeaderHandler('Accept', 'application/json'),
        new AddHeaderHandler('Content-Type', 'application/json'),
        new AddHeaderHandler('Authorization', getEnvVarValue('MOBILEPAY_API_KEY')),
    ]),
    requestBodyEncoder: $requestBodyEncoder,
    responseBodyDecoder: $responseBodyDecoder,
);

$getPaymentPointRequest = new GetPaymentPointRequest('682B0415-B90D-4DA1-852E-B0D107F9A07D');
$response = $mobilePayClient->request($getPaymentPointRequest);
$getPaymentPointResponse = GetPaymentPointResponse::fromResponse($response);

echo $getPaymentPointResponse->paymentPointId;
echo $getPaymentPointResponse->paymentPointName;
echo $getPaymentPointResponse->state;
