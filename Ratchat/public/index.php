<?php
use React\EventLoop\Loop;
use MyApp\Chat;

require __DIR__ . '/../vendor/autoload.php';

$port = $argc > 1 ? $argv[1] : 8000;
$impl = sprintf('React\EventLoop\%sLoop', $argc > 2 ? $argv[2] : 'StreamSelect');

$chat = new Chat();

$loop = new $impl;
$loop->addPeriodicTimer(2, function () use ($chat){
    $chat->load();
});

$sock = new React\Socket\Server('0.0.0.0:' . $port, $loop);

$wsServer = new Ratchet\WebSocket\WsServer($chat);
// This is enabled to test https://github.com/ratchetphp/Ratchet/issues/430
// The time is left at 10 minutes so that it will not try to every ping anything
// This causes the Ratchet server to crash on test 2.7
$wsServer->enableKeepAlive($loop, 100);

$app = new Ratchet\Http\HttpServer($wsServer);

$server = new Ratchet\Server\IoServer($app, $sock, $loop);
$server->run();