<?php

namespace App\Repository;

use App\Domain\Entities\User as UserEntity;
use App\Models\User;
use App\Interface\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Container\Container;
use App\Application\Factories\UserFactory;

class UserRepository implements UserRepositoryInterface
{
    private $container;
    private $factory;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->factory = $container->make('UserEntityFactory');
    }

    public function all(): array
    {
        return User::all()->map(function ($user) {
            return $this->toEntity($user);
        })->toArray();
    }

    public function find($id): UserEntity
    {
        $user = User::findOrFail($id);
        return $this->toEntity($user);
    }

    public function create(array $data): UserEntity
    {
        $user = User::create([
            'name' => $data['name'],
            'first_name' => $data['firstName'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => isset($data['password']) ? bcrypt($data['password']) : null,
            'role' => $data['role'] ?? 'user'
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
