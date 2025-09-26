<?php

namespace App\Application\Common\Mediator;

interface IQueryHandler
{
    public function handle(IQuery $query): mixed;
}