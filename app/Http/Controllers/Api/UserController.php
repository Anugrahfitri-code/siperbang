<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users',
            'role' => 'required|string',
            'section' => 'nullable|string',
            'status' => 'required|string',
            'password' => 'nullable|string|min:6',
        ]);

        $password = $request->input('password');
        $validated['password'] = Hash::make($password ?: 'password'); // Default password
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'username' => 'sometimes|required|string|unique:users,username,'.$user->id,
            'role' => 'sometimes|required|string',
            'section' => 'nullable|string',
            'status' => 'sometimes|required|string',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
