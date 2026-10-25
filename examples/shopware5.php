<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

$psr18HttpClient = new Symfony\Component\HttpClient\Psr18Client();
$requestBodyEncoder = new Jschaedl\ApiClient\Request\Encoder\JsonRequestBodyEncoder();
$responseBodyDecoder = new Jschaedl\ApiClient\Response\Decoder\JsonResponseBodyDecoder();

$shopware5AdminApiClient = new Jschaedl\ApiClient\Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    getEnvVarValue('SHOPWARE5_API_HOST'),
    requestHandler: new Jschaedl\ApiClient\Request\Handler\ChainRequestHandler([
        new Jschaedl\ApiClient\Request\Handler\AddHeaderHandler('Accept', 'application/json'),
        new Jschaedl\ApiClient\Request\Handler\AddHeaderHandler(
            'Authorization',
            'Basic'.base64_encode(sprintf('%s:%s', getEnvVarValue('SHOPWARE5_API_USER'), getEnvVarValue('SHOPWARE5_API_KEY')))
        ),
    ]),
    requestBodyEncoder: $requestBodyEncoder,
    responseBodyDecoder: $responseBodyDecoder,
);

$response = $shopware5AdminApiClient->request(
    new \Jschaedl\ApiClient\Request\Request(\Jschaedl\ApiClient\Request\Method::GET, '/api/categories')
);

var_dump($response->statusCode());
var_dump($response->body());
