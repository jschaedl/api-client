<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Api\Client;
use Api\Request\Encoder\JsonRequestBodyEncoder;
use Api\Request\Handler\AddHeaderHandler;
use Api\Request\Handler\ChainRequestHandler;
use Api\Request\HasNoRequestSpecificHeaders;
use Api\Request\IsGet;
use Api\Request\RequestInterface;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use Api\Response\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final readonly class GetPaymentPointRequest implements RequestInterface
{
    use IsGet;
    use HasNoRequestSpecificHeaders;

    public function __construct(
        private string $paymentId
    ) {
    }

    public function uri(): string
    {
        return sprintf('/v1/payments/%s', $this->paymentId);
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
$requestHandler = new ChainRequestHandler([
    new AddHeaderHandler('Accept', 'application/json'),
    new AddHeaderHandler('Content-Type', 'application/json'),
    new AddHeaderHandler('Authorization', getEnvVarValue('MOBILEPAY_API_KEY')),
]);
$requestBodyEncoder = new JsonRequestBodyEncoder();
$responseBodyDecoder = new JsonResponseBodyDecoder();

$mobilePayClient = new Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    'https://api.sandbox.mobilepay.dk',
    requestHandler: $requestHandler,
    requestBodyEncoder: $requestBodyEncoder,
    responseBodyDecoder: $responseBodyDecoder,
);

$response = GetPaymentPointResponse::fromResponse(
    $mobilePayClient->request(
        new GetPaymentPointRequest('682B0415-B90D-4DA1-852E-B0D107F9A07D')
    )
);

echo $response->paymentPointId;
echo $response->paymentPointName;
echo $response->state;
