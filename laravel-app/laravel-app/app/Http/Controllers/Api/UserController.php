<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\DTOs\UserDTO;
use App\Application\UseCases\CreateUserUseCase;
use App\Application\UseCases\UpdateUserUseCase;
use App\Application\UseCases\DeleteUserUseCase;
use App\Application\UseCases\ListUsersUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private CreateUserUseCase $createUserUseCase,
        private UpdateUserUseCase $updateUserUseCase,
        private DeleteUserUseCase $deleteUserUseCase,
        private ListUsersUseCase $listUsersUseCase
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->listUsersUseCase->execute();
        $response = $users->map(function ($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'first_name' => $user->getFirstName(),
                'email' => $user->getEmail()->getValue(),
                'phone' => $user->getPhone(),
                'role' => $user->getRole()->getValue()
            ];
        });
        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'first_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
        ]);

        $userDTO = UserDTO::fromArray($request->all());
        $user = $this->createUserUseCase->execute($userDTO);

        return response()->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'first_name' => $user->getFirstName(),
            'email' => $user->getEmail()->getValue(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole()->getValue()
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string',
            'first_name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
        ]);

        $userDTO = UserDTO::fromArray($request->all());
        $user = $this->updateUserUseCase->execute($id, $userDTO);

        return response()->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'first_name' => $user->getFirstName(),
            'email' => $user->getEmail()->getValue(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole()->getValue()
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteUserUseCase->execute($id);
        return response()->json(null, 204);
    }
}
