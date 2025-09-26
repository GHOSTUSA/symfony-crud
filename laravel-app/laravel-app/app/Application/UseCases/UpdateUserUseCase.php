<?php

namespace App\Application\UseCases;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Application\DTOs\UserDTO;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use Illuminate\Support\Facades\Hash;

class UpdateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private \Illuminate\Contracts\Container\Container $container
    ) {}

    public function execute(int $userId, UserDTO $userDTO): User
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($userDTO->name !== null) {
            $user->updateName($userDTO->name);
        }

        if ($userDTO->firstName !== null) {
            $user->updateFirstName($userDTO->firstName);
        }

        if ($userDTO->email !== null && $userDTO->email !== $user->getEmail()->getValue()) {
            $email = $this->container->make(Email::class, ['email' => $userDTO->email]);
            $user->updateEmail($email);
        }

        if ($userDTO->phone !== null) {
            $user->updatePhone($userDTO->phone);
        }

        if ($userDTO->password !== null) {
            $user->updatePassword(Hash::make($userDTO->password));
        }

        return $this->userRepository->update($user);
    }
}