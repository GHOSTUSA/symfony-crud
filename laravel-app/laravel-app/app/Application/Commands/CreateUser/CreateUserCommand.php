<?php

namespace App\Application\Commands\CreateUser;

use App\Application\Common\Mediator\ICommand;

class CreateUserCommand implements ICommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $firstName,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $password
    ) {}
}