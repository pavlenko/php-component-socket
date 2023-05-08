<?php

namespace PE\Component\Socket;

require_once __DIR__ . '/../vendor/autoload.php';

$select  = new Select();
$factory = new Factory($select);

$time   = time();
$client = null;

$server = $factory->createServer(9999);
$server->setInputHandler(function (ClientInterface $connection) use (&$client, &$time) {
    echo "!: New connection from {$connection->getRemoteAddress()}\n";

    $time   = time();
    $client = $connection;
    $connection->setInputHandler(function (string $data) use ($connection) {
        echo 'I: ' . trim($data) . "\n";
        if ('HELLO' === trim($data)) {
            sleep(1);
            $connection->write("WELCOME\n");
        }
    });

    $connection->setCloseHandler(function (string $message = null) {
        echo '!: ' . trim($message ?: 'CLOSED') . "\n";
    });

    $connection->setErrorHandler(function (\Throwable $throwable) {
        echo 'E: ' . $throwable . "\n";
    });

    $connection->write("HELLO\n");
});

echo "!: Listening on {$server->getAddress()}\n";

while (true) {
    if (time() - $time > 5 && $client) {
        $client->close();
        $client = null;
    }
    $select->dispatch();
    usleep(1000);
}
