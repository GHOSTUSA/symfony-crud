<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Common\Mediator\IMediator;
use App\Application\Commands\CreateUser\CreateUserCommand;
use App\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Application\Commands\DeleteUser\DeleteUserCommand;
use App\Application\Queries\GetAllUsers\GetAllUsersQuery;
use App\Application\Queries\GetUserById\GetUserByIdQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private IMediator $mediator
    ) {}

    public function index(): JsonResponse
    {
    $users = $this->mediator->query(new GetAllUsersQuery());
    $arrayUsers = array_map(function ($user) {
        return $user->jsonSerialize();
    }, $users);
    return response()->json(collect($arrayUsers));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'first_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        $command = new CreateUserCommand(
            $validated['name'],
            $validated['first_name'],
            $validated['email'],
            $validated['phone'],
            $validated['password']
        );

        $user = $this->mediator->send($command);
        return response()->json($user->jsonSerialize(), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'first_name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string',
            'password' => 'sometimes|string|min:6'
        ]);

        $command = new UpdateUserCommand(
            $id,
            $validated['name'] ?? null,
            $validated['first_name'] ?? null,
            $validated['email'] ?? null,
            $validated['phone'] ?? null,
            $validated['password'] ?? null
        );

        $user = $this->mediator->send($command);
        return response()->json($user->jsonSerialize());
    }

    public function destroy(int $id): JsonResponse
    {
        $this->mediator->send(new DeleteUserCommand($id));
        return response()->json(null, 204);
    }
}
