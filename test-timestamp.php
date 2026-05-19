<?php

require 'vendor/autoload.php';

use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampRequest;

$data = random_bytes(32);

$request = (new Rfc3161TimestampRequest())
    ->build($data);

$response = (new HttpTimestampClient(
    'http://timestamp.digicert.com'
))->requestToken($request);

file_put_contents(
    'tests/Output/timestamp-response.tsr',
    $response
);

echo 'Timestamp response size: ' . strlen($response) . PHP_EOL;