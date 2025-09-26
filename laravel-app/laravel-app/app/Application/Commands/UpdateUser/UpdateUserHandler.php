<?php

namespace App\Application\Commands\UpdateUser;

use App\Domain\ValueObjects\Email;
use App\Interface\UserRepositoryInterface;
use App\Application\Common\Mediator\ICommandHandler;
use App\Application\Common\Mediator\ICommand;

class UpdateUserHandler implements ICommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(ICommand $command): mixed
    {
        if (!$command instanceof UpdateUserCommand) {
            throw new \InvalidArgumentException('Handler expects UpdateUserCommand');
        }

        $user = $this->userRepository->findById($command->id);
        
        if ($command->name !== null) {
            $user->updateName($command->name);
        }
        
        if ($command->firstName !== null) {
            $user->updateFirstName($command->firstName);
        }
        
        if ($command->email !== null) {
            $email = new Email($command->email);
            $user->updateEmail($email);
        }
        
        if ($command->phone !== null) {
            $user->updatePhone($command->phone);
        }
        
        if ($command->password !== null) {
            $user->updatePassword($command->password);
        }

        return $this->userRepository->update($user);
    }
}