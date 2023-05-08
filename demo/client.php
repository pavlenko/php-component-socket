<?php

namespace PE\Component\Socket;

require_once __DIR__ . '/../vendor/autoload.php';

$select  = new Select();
$factory = new Factory($select);

$active = true;
$client = $factory->createClient('127.0.0.1:9999');

$client->setInputHandler(function (string $message) use ($client) {
    echo 'I: ' . trim($message) . "\n";
    if ('HELLO' === trim($message)) {
        sleep(1);
        $client->write("HELLO\n");
    }
});

$client->setCloseHandler(function (string $message = null) use (&$active) {
    echo '!: ' . trim($message ?: 'CLOSED') . "\n";
    $active = false;
});

$client->setErrorHandler(function (\Throwable $throwable) {
    echo 'E: ' . $throwable . "\n";
});

echo "!: Connected to remote {$client->getRemoteAddress()}\n";

while ($active) {
    $select->dispatch();
    usleep(1000);
}
