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

    public function update(UserEntity $userEntity, array $data): UserEntity
    {
        $user = User::findOrFail($userEntity->getId());
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'first_name' => $data['firstName'] ?? $user->first_name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'role' => $data['role'] ?? $user->role
        ]);

        if (isset($data['password'])) {
            $user->password = bcrypt($data['password']);
            $user->save();
        }

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
