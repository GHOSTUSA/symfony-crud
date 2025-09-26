<?php

namespace App\Application\Common\Mediator;

interface ICommandHandler
{
    public function handle(ICommand $command): mixed;
}