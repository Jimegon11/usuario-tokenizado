<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Obtener los 10 primeros usuarios.
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::take(10)->get();

            return response()->json([
                'success' => true,
                'message' => 'Usuarios obtenidos correctamente.',
                'data' => $users,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ]);
        }
    }

    /**
     * Crear usuario hasheando la contraseña.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente.',
                'data' => $user,
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ]);
        }
    }

    /**
     * Iniciar sesión generando y devolviendo un token de sesión.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas.',
                    'data' => null,
                ], 401);
            }

            $oldToken = Token::where('user_id', $user->id)->first();
            if ($oldToken) {
                $oldToken->delete();
            }

            $token = hash('sha256', Str::random(64));
            Token::create([
                'user_id' => $user->id,
                'token' => $token,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada correctamente.',
                'data' => $token,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ]);
        }
    }

    /**
     * Actualizar el nombre del usuario autenticado mediante token.
     */
    public function updateName(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'token' => ['required', 'string'],
                'name' => ['required', 'string', 'max:255'],
            ]);

            $tokenRecord = Token::where('token', $data['token'])->first();

            if (! $tokenRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado.',
                    'data' => null,
                ], 401);
            }

            $tokenRecord->user->update(['name' => $data['name']]);

            return response()->json([
                'success' => true,
                'message' => 'Nombre actualizado correctamente.',
                'data' => $tokenRecord->user->fresh(),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ]);
        }
    }
}
