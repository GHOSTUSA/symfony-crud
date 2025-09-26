<?php

namespace App\Repository;

use App\Domain\Entities\User as UserEntity;
use App\Models\User;
use App\Interface\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Application\Factories\UserFactory;

class UserRepository implements UserRepositoryInterface
{
    private $factory;

    public function __construct(UserFactory $factory)
    {
        $this->factory = $factory;
    }

    public function all(): array
    {
        $users = User::all();
        return array_map(function ($user) {
            return $this->toEntity($user);
        }, $users->all());
    }

    public function findById(int $id): UserEntity
    {
        $user = User::findOrFail($id);
        return $this->toEntity($user);
    }

    public function create(UserEntity $userEntity): UserEntity
    {
        $user = User::create([
            'name' => $userEntity->getName(),
            'first_name' => $userEntity->getFirstName(),
            'email' => $userEntity->getEmail()->getValue(),
            'phone' => $userEntity->getPhone(),
            'password' => $userEntity->getPassword() ? bcrypt($userEntity->getPassword()) : null,
            'role' => $userEntity->getRole()->getValue()
        ]);

        return $this->toEntity($user);
    }

    public function update(UserEntity $userEntity): UserEntity
    {
        $user = User::findOrFail($userEntity->getId());
        $user->update([
            'name' => $userEntity->getName(),
            'first_name' => $userEntity->getFirstName(),
            'email' => $userEntity->getEmail()->getValue(),
            'phone' => $userEntity->getPhone(),
            'role' => $userEntity->getRole()->getValue()
        ]);

        return $this->toEntity($user);
    }

    public function delete(UserEntity $userEntity): void
    {
        $user = User::findOrFail($userEntity->getId());
        $user->delete();
    }

    private function toEntity(User $user): UserEntity
    {
        return $this->factory->create(
            $user->id,
            $user->name,
            $user->first_name,
            $user->email,
            $user->phone,
            null,
            $user->role
        );
    }
}
