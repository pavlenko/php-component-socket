<?php

namespace PE\Component\Socket;

use PE\Component\Socket\Exception\InvalidArgumentException;
use PE\Component\Socket\Exception\RuntimeException;

interface FactoryInterface
{
    /**
     * Get configured select instance helper
     *
     * @return SelectInterface
     */
    public function getSelect(): SelectInterface;

    /**
     * Create client socket
     *
     * @param string $address Address to the socket to connect to.
     * @param array $context Stream transport related context.
     * @param float|null $timeout Connection timeout.
     * @return ClientInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createClient(string $address, array $context = [], ?float $timeout = null): ClientInterface;

    /**
     * Create server socket
     *
     * @param string $address Address to the socket to listen to.
     * @param array $context Stream transport related context.
     * @return ServerInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createServer(string $address, array $context = []): ServerInterface;
}
