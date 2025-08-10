<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\DB;

class AuthenticationController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'regno' => 'required|string|unique:students,regno',
                'dob' => 'nullable|date',
                'guardian' => 'nullable|string|max:255',
            ]);
            $role = Role::where('name', 'student')->first();
            if (!$role) {
                return response()->json(['message' => 'Student role not found'], 500);
            }
            DB::transaction(function () use ($validated, $role) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'role_id' => $role->id,
                ]);
                Student::create([
                    'user_id' => $user->id,
                    'regno' => $validated['regno'],
                    'name' => $validated['name'],
                    'dob' => $validated['dob'] ?? null,
                    'guardian' => $validated['guardian'] ?? null,
                ]);
            });
            return response()->json(['message' => 'Student registered successfully'], 201);

        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::with(['role', 'student', 'admin'])
                ->where('email', $validated['email'])
                ->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => new UserResource($user),
            ]);

        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
