<?php

declare(strict_types=1);

use Api\Client;
use Api\ClientInterface;
use Api\Request\Encoder\JsonRequestBodyEncoder;
use Api\Request\Handler\AccessToken;
use Api\Request\Handler\AccessTokenProviderInterface;
use Api\Request\Handler\AddAccessTokenHandler;
use Api\Request\Handler\AddHeaderHandler;
use Api\Request\Handler\ChainRequestHandler;
use Api\Request\IsPost;
use Api\Request\Method;
use Api\Request\Request;
use Api\Request\RequestInterface;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use Api\Response\ResponseInterface;
use Psl\Type\TypeInterface;
use Symfony\Component\HttpClient\Psr18Client;

use function Psl\Type\positive_int;
use function Psl\Type\shape;
use function Psl\Type\string;

require_once __DIR__.'/../vendor/autoload.php';

readonly class AccessTokenRequest implements RequestInterface
{
    use IsPost;

    public function __construct(
        private string $clientId,
        private string $clientSecret
    ) {
    }

    public function uri(): string
    {
        return '/api/oauth/token';
    }

    public function body(): ?array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
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
        $body = self::type()->coerce($response->body());

        return new self(
            $body['token_type'],
            $body['expires_in'],
            $body['access_token'],
        );
    }

    private static function type(): TypeInterface
    {
        return shape([
            'token_type' => string(),
            'expires_in' => positive_int(),
            'access_token' => string(),
        ], true);
    }
}

$psr18HttpClient = new Psr18Client();

$accessTokenClient = new Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('SHOPWARE6_API_HOST'),
    requestHandler: new ChainRequestHandler([
        new AddHeaderHandler('Accept', 'application/json'),
        new AddHeaderHandler('Content-Type', 'application/json'),
    ]),
    requestBodyEncoder: new JsonRequestBodyEncoder(),
    responseBodyDecoder: new JsonResponseBodyDecoder(),
);

class AccessTokenProvider implements AccessTokenProviderInterface
{
    private static ?AccessToken $accessToken = null;

    public function __construct(
        private readonly ClientInterface $client
    ) {
    }

    public function provideToken(): AccessToken
    {
        if (self::$accessToken && !self::$accessToken->isExpired(new DateTimeImmutable())) {
            return self::$accessToken;
        }

        $accessTokenResponse = AccessTokenResponse::fromResponse(
            $this->client->request(
                new AccessTokenRequest(
                    getEnvVarValue('SHOPWARE6_CLIENT_ID'),
                    getEnvVarValue('SHOPWARE6_CLIENT_SECRET'),
                )
            )
        );

        return self::$accessToken = new AccessToken(
            $accessTokenResponse->tokenType,
            $accessTokenResponse->accessToken,
            (new DateTimeImmutable())->modify(sprintf('+%dseconds', $accessTokenResponse->expiresIn)),
        );
    }
}

$shopware6AdminApiClient = new Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('SHOPWARE6_API_HOST'),
    requestHandler: new ChainRequestHandler([
        new AddHeaderHandler('Accept', 'application/json'),
        new AddHeaderHandler('Content-Type', 'application/json'),
        new AddAccessTokenHandler(
            new AccessTokenProvider($accessTokenClient),
            'Authorization'
        ),
    ]),
    requestBodyEncoder: new JsonRequestBodyEncoder(),
    responseBodyDecoder: new JsonResponseBodyDecoder(),
);

$response = $shopware6AdminApiClient->request(
    new Request(Method::GET, '/api/category')
);

var_dump($response->statusCode());
var_dump($response->body());
