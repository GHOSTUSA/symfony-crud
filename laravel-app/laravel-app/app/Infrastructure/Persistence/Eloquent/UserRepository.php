<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Entities\User as UserEntity;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\User as UserModel;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserRole;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findAll(): Collection
    {
        return UserModel::all()->map(function ($user) {
            return $this->toEntity($user);
        });
    }

    public function findById(int $id): ?UserEntity
    {
        $user = UserModel::find($id);
        return $user ? $this->toEntity($user) : null;
    }

    public function save(UserEntity $user): UserEntity
    {
        $model = new UserModel();
        $model->fill([
            'name' => $user->getName(),
            'first_name' => $user->getFirstName(),
            'email' => $user->getEmail()->getValue(),
            'phone' => $user->getPhone(),
            'password' => $user->getPassword(),
            'role' => $user->getRole()->getValue()
        ]);
        $model->save();

        return $this->toEntity($model);
    }

    public function update(UserEntity $user): UserEntity
    {
        $model = UserModel::findOrFail($user->getId());
        $model->update([
            'name' => $user->getName(),
            'first_name' => $user->getFirstName(),
            'email' => $user->getEmail()->getValue(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole()->getValue()
        ]);

        return $this->toEntity($model);
    }

    public function delete(UserEntity $user): void
    {
        UserModel::findOrFail($user->getId())->delete();
    }

    public function __construct(
        private \Illuminate\Contracts\Container\Container $container
    ) {}

    private function toEntity(UserModel $model): UserEntity
    {
        return $this->container->make('UserEntityFactory')(
            $model->id,
            $model->name,
            $model->first_name,
            $model->email,
            $model->phone,
            $model->password,
            $model->role
        );
    }
}