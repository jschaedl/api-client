<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

$psr18HttpClient = new Symfony\Component\HttpClient\Psr18Client();
$requestBodyEncoder = new Api\Request\Encoder\JsonRequestBodyEncoder();
$responseBodyDecoder = new Api\Response\Decoder\JsonResponseBodyDecoder();

$shopware5AdminApiClient = new Api\Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('SHOPWARE5_API_HOST'),
    requestHandler: new Api\Request\Handler\ChainRequestHandler([
        new Api\Request\Handler\AddHeaderHandler('Accept', 'application/json'),
        new Api\Request\Handler\AddHeaderHandler('Content-Type', 'application/json'),
        new Api\Request\Handler\AddHeaderHandler(
            'Authorization',
            'Basic'.base64_encode(sprintf('%s:%s', getEnvVarValue('SHOPWARE5_API_USER'), getEnvVarValue('SHOPWARE5_API_KEY')))
        ),
    ]),
    requestBodyEncoder: $requestBodyEncoder,
    responseBodyDecoder: $responseBodyDecoder,
);

$response = $shopware5AdminApiClient->request(
    new \Api\Request\Request(\Api\Request\Method::GET, '/api/categories')
);

var_dump($response->statusCode());
var_dump($response->body());
