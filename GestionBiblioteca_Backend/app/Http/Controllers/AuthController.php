<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    // ================================================================
    // PROCESAMIENTO INICIAL CON GOOGLE (Alumnos)
    // ================================================================
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'credential' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $response = Http::withoutVerifying()->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->credential,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de Google inválido o expirado.',
                ], 401);
            }

            $payload = $response->json();

            $clientId = config('services.google.client_id');
            $allowedDomain = config('services.google.workspace_domain');

            if (
                ($payload['aud'] ?? null) !== $clientId ||
                empty($payload['email_verified']) ||
                ($payload['hd'] ?? null) !== $allowedDomain
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta no pertenece al dominio institucional autorizado (@upve.edu.mx).',
                ], 403);
            }

            $correo = $payload['email'];
            $nombre = $payload['given_name'] ?? ($payload['name'] ?? '');
            $apellidoPaterno = $payload['family_name'] ?? '';

            $usuarioBuscado = Usuario::where('CorreoElectronico', $correo)->first();

            if (!$usuarioBuscado) {
                return response()->json([
                    'success' => true,
                    'es_nuevo' => true,
                    'message' => 'Correo institucional válido. Redirigiendo a completar registro.',
                    'datos_google' => [
                        'correo' => $correo,
                        'nombre' => $nombre,
                        'apellido_paterno' => $apellidoPaterno,
                        'apellido_materno' => ''
                    ]
                ]);
            }

            if (isset($usuarioBuscado->EstadoCuenta) && $usuarioBuscado->EstadoCuenta !== 'Activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta se encuentra inactiva o suspendida.',
                ], 403);
            }

            $token = $usuarioBuscado->createToken('usuario_token')->plainTextToken;
            $usuarioBuscado->load(['grupo.carrera']);

            $rolTexto = ($usuarioBuscado->Rol_ID === Rol::ADMIN) ? 'admin' : 'usuario';

            $cookie = cookie('token', $token, 60 * 24 * 7, '/', null, false, true, false, 'Lax');

            return response()->json([
                'success' => true,
                'es_nuevo' => false,
                'message' => 'Bienvenido al Sistema Bibliotecario, ' . $usuarioBuscado->NombreUsuario,
                'rol' => Crypt::encryptString($rolTexto),
                'usuario' => $usuarioBuscado
            ])->cookie($cookie);

        } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'success' => false,
            'message' => 'Error de autenticación con el servicio institucional. Intenta de nuevo más tarde.'
        ], 500);
        }
    }

    // ================================================================
    // COMPLETAR EL REGISTRO DE UN NUEVO USUARIO
    // ================================================================
    public function completarRegistro(Request $request)
    {
        try {
            $request->validate([
                'correo' => 'required|email|unique:usuarios,CorreoElectronico',
                'nombre' => 'required',
                'matricula' => 'required|unique:usuarios,Matricula',
                'telefono' => 'required',
                'grupo_id' => 'nullable'
            ]);

            $nuevoUsuario = new Usuario();
            $nuevoUsuario->Rol_ID = Rol::USUARIO;
            $nuevoUsuario->CorreoElectronico = $request->correo;
            $nuevoUsuario->NombreUsuario = $request->nombre;
            $nuevoUsuario->ApellidoPaterno = $request->apellido_paterno ?? '';
            $nuevoUsuario->ApellidoMaterno = $request->apellido_materno ?? '';
            $nuevoUsuario->Matricula = $request->matricula;
            $nuevoUsuario->Telefono = $request->telefono;
            $nuevoUsuario->Grupo_ID = $request->grupo_id ?: null; 
            $nuevoUsuario->EstadoCuenta = 'Activo'; 
            $nuevoUsuario->save();

            $token = $nuevoUsuario->createToken('usuario_token')->plainTextToken;
            $nuevoUsuario->load(['grupo.carrera']);

            $cookie = cookie('token', $token, 60 * 24 * 7, '/', null, false, true, false, 'Lax');

            return response()->json([
                'success' => true,
                'message' => 'Registro completado exitosamente.',
                'rol' => Crypt::encryptString('usuario'), 
                'usuario' => $nuevoUsuario
            ])->cookie($cookie);

        } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error al completar el registro. Intenta nuevamente.'
        ], 500);
        }
    }

    // ================================================================
    // CIERRE DE SESIÓN SEGURO (Revocación de Token Sanctum)
    // ================================================================
    public function logout(Request $request)
    {
        try {
            if ($request->user() && $request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente.'
            ])->withoutCookie('token');
        } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.'
        ])->withoutCookie('token');
        }
    }
}
