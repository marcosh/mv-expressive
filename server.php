<?php

use React\EventLoop\Factory;
use React\Http\Server as HttpServer;
use React\Socket\Server as Socket;
use React2Psr7\ReactRequestHandler;
use Zend\Expressive\Application;

require_once 'vendor/autoload.php';

$loop      = Factory::create();
$socket    = new Socket($loop);
$http      = new HttpServer($socket);
$container = require 'config/container.php';

$http->on('request', new ReactRequestHandler($container->get(Application::class)));

// Listen on all ports; omit second argument to restrict to localhost.
$socket->listen(1337, '0.0.0.0');
$loop->run();
