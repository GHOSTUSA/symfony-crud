<?php

namespace App\Application\Queries\GetUserById;

use App\Application\Common\Mediator\IQuery;

class GetUserByIdQuery implements IQuery
{
    public function __construct(
        public readonly int $id
    ) {}
}