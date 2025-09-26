<?php

namespace App\Application\Common\Mediator;

interface IMediator
{
    public function send(ICommand $command): mixed;
    public function query(IQuery $query): mixed;
}