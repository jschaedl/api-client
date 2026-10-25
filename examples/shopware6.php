<?php

declare(strict_types=1);

use Jschaedl\ApiClient\Client;
use Jschaedl\ApiClient\ClientInterface;
use Jschaedl\ApiClient\Request\Encoder\JsonRequestBodyEncoder;
use Jschaedl\ApiClient\Request\Handler\AccessToken;
use Jschaedl\ApiClient\Request\Handler\AccessTokenProviderInterface;
use Jschaedl\ApiClient\Request\Handler\AddAccessTokenHandler;
use Jschaedl\ApiClient\Request\Handler\AddHeaderHandler;
use Jschaedl\ApiClient\Request\Handler\ChainRequestHandler;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Response\Decoder\JsonResponseBodyDecoder;
use Jschaedl\ApiClient\Response\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

require_once __DIR__.'/../vendor/autoload.php';

class AccessTokenRequest extends Request
{
    public function __construct(
        string $clientId,
        string $clientSecret
    ) {
        parent::__construct(Method::POST, '/api/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
    }
}

class AccessTokenResponse
{
    public function __construct(
        public string $tokenType,
        public int $expiresIn,
        public string $accessToken,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $body = $response->body();

        if (!is_array($body)
            || !is_string($body['token_type'] ?? null)
            || !is_int($body['expires_in'] ?? null) || $body['expires_in'] < 1
            || !is_string($body['access_token'] ?? null)
        ) {
            throw new UnexpectedValueException('Unexpected access token response.');
        }

        return new self($body['token_type'], $body['expires_in'], $body['access_token']);
    }
}

class AccessTokenProvider implements AccessTokenProviderInterface
{
    private static ?AccessToken $accessToken = null;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $clientId,
        private readonly string $clientSecret
    ) {
    }

    public function provideToken(): AccessToken
    {
        if (self::$accessToken && !self::$accessToken->isExpired(new DateTimeImmutable())) {
            return self::$accessToken;
        }

        $accessTokenRequest = new AccessTokenRequest($this->clientId, $this->clientSecret);
        $response = $this->client->request($accessTokenRequest);
        $accessTokenResponse = AccessTokenResponse::fromResponse($response);

        return self::$accessToken = new AccessToken(
            $accessTokenResponse->tokenType,
            $accessTokenResponse->accessToken,
            (new DateTimeImmutable())->modify(sprintf('+%dseconds', $accessTokenResponse->expiresIn)),
        );
    }
}

$psr18HttpClient = new Psr18Client();
$requestBodyEncoder = new JsonRequestBodyEncoder();
$responseBodyDecoder = new JsonResponseBodyDecoder();

$accessTokenClient = new Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('SHOPWARE6_API_HOST'),
    requestHandler: new ChainRequestHandler([
        new AddHeaderHandler('Accept', 'application/json'),
    ]),
    requestBodyEncoder: $requestBodyEncoder,
    responseBodyDecoder: $responseBodyDecoder,
);

$accessTokenProvider = new AccessTokenProvider(
    $accessTokenClient,
    getEnvVarValue('SHOPWARE6_CLIENT_ID'),
    getEnvVarValue('SHOPWARE6_CLIENT_SECRET'),
);

$shopware6AdminApiClient = new Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('SHOPWARE6_API_HOST'),
    requestHandler: new ChainRequestHandler([
        new AddHeaderHandler('Accept', 'application/json'),
        new AddAccessTokenHandler($accessTokenProvider, 'Authorization'),
    ]),
    requestBodyEncoder: $requestBodyEncoder,
    responseBodyDecoder: $responseBodyDecoder,
);

$response = $shopware6AdminApiClient->request(
    new Request(Method::GET, '/api/category')
);

var_dump($response->statusCode());
var_dump($response->body());
