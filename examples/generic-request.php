<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Api\Request\Request;
use Api\Request\Method;
use Symfony\Component\HttpClient\Psr18Client;

$psr18HttpClient = new Psr18Client();

$apiClient = new Api\Client(
    $psr18HttpClient,
    $psr18HttpClient,
    $psr18HttpClient,
    'https://reqbin.com',
);

$request = new Request(Method::POST, '/echo/post/json');
$request
    ->addHeader('Accept', 'application/json')
    ->addHeader('Content-Type', 'application/json')
    ->setBody(['json' => true])
;

$response = $apiClient->request($request);

var_dump($response->statusCode());
var_dump($response->body());
