<?php

namespace App\Application\Commands\CreateUser;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Interface\UserRepositoryInterface;
use App\Application\Common\Mediator\ICommandHandler;
use App\Application\Common\Mediator\ICommand;

class CreateUserHandler implements ICommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(ICommand $command): User
    {
        if (!$command instanceof CreateUserCommand) {
            throw new \InvalidArgumentException('Handler expects CreateUserCommand');
        }

        $email = new Email($command->email);
        
        $user = new User(
            null,
            $command->name,
            $command->firstName,
            $email,
            $command->phone,
            $command->password
        );

        return $this->userRepository->create($user);
    }
}