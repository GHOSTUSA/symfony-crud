<?php

namespace App\Application\Commands\UpdateUser;

use App\Application\Common\Mediator\ICommand;

class UpdateUserCommand implements ICommand
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $firstName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $password
    ) {}
}