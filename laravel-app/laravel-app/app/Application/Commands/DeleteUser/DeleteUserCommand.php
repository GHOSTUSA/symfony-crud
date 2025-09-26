<?php

namespace App\Application\Commands\DeleteUser;

use App\Application\Common\Mediator\ICommand;

class DeleteUserCommand implements ICommand
{
    public function __construct(
        public readonly int $id
    ) {}
}