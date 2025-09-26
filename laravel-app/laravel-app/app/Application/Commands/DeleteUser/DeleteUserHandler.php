<?php

namespace App\Application\Commands\DeleteUser;

use App\Interface\UserRepositoryInterface;
use App\Application\Common\Mediator\ICommandHandler;
use App\Application\Common\Mediator\ICommand;

class DeleteUserHandler implements ICommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(ICommand $command): void
    {
        if (!$command instanceof DeleteUserCommand) {
            throw new \InvalidArgumentException('Handler expects DeleteUserCommand');
        }

        $user = $this->userRepository->findById($command->id);
        $this->userRepository->delete($user);
    }
}