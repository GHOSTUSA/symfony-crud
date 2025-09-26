<?php

namespace App\Service;

use App\Interface\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function listUsers()
    {
        return $this->userRepo->all();
    }

    public function createUser(array $data)
    {
        $data['role'] = (strpos($data['email'], '@company.com') !== false) ? 'Administrateur' : 'Utilisateur standard';

        $data['password'] = Hash::make($data['password']);

        return $this->userRepo->create($data);
    }

    public function getUser($id)
    {
        return $this->userRepo->find($id);
    }

    public function updateUser(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['email'])) {
            $data['role'] = (strpos($data['email'], '@company.com') !== false) ? 'Administrateur' : 'Utilisateur standard';
        }

        return $this->userRepo->update($user, $data);
    }

    public function deleteUser(User $user)
    {
        $this->userRepo->delete($user);
    }
}
