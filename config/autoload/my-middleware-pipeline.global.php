<?php

use Psr7Middlewares\Middleware\ClientIp;
use Psr7Middlewares\Middleware\Geolocate;
use Psr7Middlewares\Middleware\BasicAuthentication;

use Geocoder\ProviderAggregator;
use Geocoder\HostIp;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use App\Middleware\AccessLog;

// setup basic authentication
$users = [
    'marco' => 'perone',
    'steve' => 'maraspin'
];

$basicAuthentication = new BasicAuthentication($users);

// setup access log
$logger = new Logger('access');
$logFile = __DIR__ . '/../../data/log/' . date("Ymd") . '.log';
$logger->pushHandler(new StreamHandler($logFile));

$formatter = function (
    ServerRequestInterface $request,
    ResponseInterface $response
) {
    $addresses = '';
    foreach (Geolocate::getLocation($request) as $address) {
        $addresses .= json_encode($address->toArray());
    }

    return sprintf(
        '%s %s %s %s [%s] "%s %s %s/%s" %d %d',
        ClientIp::getIp($request),
        $addresses,
        BasicAuthentication::getUsername($request),
        $request->getUri()->getUserInfo() ?: '-',
        strftime('%d/%b/%Y:%H:%M:%S %z'),
        strtoupper($request->getMethod()),
        $request->getUri()->getPath(),
        strtoupper($request->getUri()->getScheme()),
        $request->getProtocolVersion(),
        $response->getStatusCode(),
        $response->getBody()->getSize()
    );
};

$accessLog = new AccessLog($logger, $formatter);

return [
    'middleware_pipeline' => [
        'basic_authentication' => [
            'middleware' => $basicAuthentication,
            'priority' => 4000
        ],
        'clientip' => [
            'middleware' => ClientIp::class,
            'priority' => 3000
        ],
        'geolocate' => [
            'middleware' => Geolocate::class,
            'priority' => 2000
        ],
        'access-log' => [
            'middleware' => $accessLog,
            'priority' => 1000
        ]
    ]
];
