<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Api\Request\Encoder\JsonRequestBodyEncoder;
use Api\Request\Request;
use Api\Request\Method;
use Symfony\Component\HttpClient\Psr18Client;

$psr18HttpClient = new Psr18Client();
$requestBodyEncoder = new JsonRequestBodyEncoder();

$apiClient = new Api\Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    'https://reqbin.com',
    requestBodyEncoder: $requestBodyEncoder
);

$request = new Request(Method::POST, '/echo/post/json');
$request
    ->addHeader('Accept', 'application/json')
    ->addHeader('Content-Type', 'application/json')
    ->setBody(['json' => true])
;

try {
    $response = $apiClient->request($request);

    var_dump($response->statusCode());
    var_dump($response->body());
} catch (Exception $exception) {
    var_dump($exception->getMessage());
}
