<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\UserService;
use App\Models\User;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return response()->json($this->userService->listUsers());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'first_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->userService->createUser($request->only(['name', 'first_name', 'email', 'phone', 'password']));

        return response()->json($user, 201);
    }

    public function show($id)
    {
        return response()->json($this->userService->getUser($id));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'first_name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
        ]);

        $updatedUser = $this->userService->updateUser($user, $request->only(['name', 'first_name', 'email', 'phone', 'password']));

        return response()->json($updatedUser);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->userService->deleteUser($user);
        return response()->json(null, 204);
    }
}
