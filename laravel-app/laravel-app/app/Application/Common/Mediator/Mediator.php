<?php

namespace App\Application\Common\Mediator;

use Illuminate\Container\Container;
use RuntimeException;

class Mediator implements IMediator
{
    private array $commandHandlers = [];
    private array $queryHandlers = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function registerCommandHandler(string $commandClass, string $handlerClass): void
    {
        $this->commandHandlers[$commandClass] = $handlerClass;
    }

    public function registerQueryHandler(string $queryClass, string $handlerClass): void
    {
        $this->queryHandlers[$queryClass] = $handlerClass;
    }

    public function send(ICommand $command): mixed
    {
        $commandClass = get_class($command);
        if (!isset($this->commandHandlers[$commandClass])) {
            throw new RuntimeException("No handler registered for command " . $commandClass);
        }

        $handlerClass = $this->commandHandlers[$commandClass];
        $handler = $this->container->make($handlerClass);

        return $handler->handle($command);
    }

    public function query(IQuery $query): mixed
    {
        $queryClass = get_class($query);
        if (!isset($this->queryHandlers[$queryClass])) {
            throw new RuntimeException("No handler registered for query " . $queryClass);
        }

        $handlerClass = $this->queryHandlers[$queryClass];
        $handler = $this->container->make($handlerClass);

        return $handler->handle($query);
    }
}