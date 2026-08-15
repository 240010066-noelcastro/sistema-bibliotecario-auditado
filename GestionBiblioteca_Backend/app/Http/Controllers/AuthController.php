<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Enums\Rol;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
            $jwks = Cache::remember('google_jwks_certs', 60 * 60 * 24, function () {
                $response = Http::timeout(5)->get('https://www.googleapis.com/oauth2/v3/certs');
                return $response->json();
            });

            if (!$jwks) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fue posible validar las llaves de autenticación.'
                ], 500);
            }

            $decoded = JWT::decode($request->credential, JWK::parseKeySet($jwks));
            $payload = (array) $decoded;

            $validIssuers = ['accounts.google.com', 'https://accounts.google.com'];
            $clientId = config('services.google.client_id', env('GOOGLE_CLIENT_ID'));
            $allowedDomain = config('services.google.workspace_domain', env('GOOGLE_WORKSPACE_DOMAIN', 'upve.edu.mx'));

            if (!in_array($payload['iss'] ?? '', $validIssuers, true) || ($payload['aud'] ?? '') !== $clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token institucional no válido o alterado.'
                ], 401);
            }

            if (empty($payload['email_verified']) || ($payload['hd'] ?? null) !== $allowedDomain) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta no pertenece al dominio institucional autorizado (@upve.edu.mx).'
                ], 403);
            }

            $googleSub = $payload['sub'] ?? null;
            $correo = $payload['email'] ?? '';
            $nombre = $payload['given_name'] ?? ($payload['name'] ?? '');
            $apellidoPaterno = $payload['family_name'] ?? '';

            $usuarioBuscado = Usuario::where('GoogleSub', $googleSub)
                ->orWhere('CorreoElectronico', $correo)
                ->first();

            if (!$usuarioBuscado) {
                $registroToken = Str::random(64);

                Cache::put(
                    "registro-google:{$registroToken}",
                    [
                        'google_sub'       => $googleSub,
                        'correo'           => $correo,
                        'nombre'           => $nombre,
                        'apellido_paterno' => $apellidoPaterno,
                    ],
                    now()->addMinutes(10)
                );

                return response()->json([
                    'success'        => true,
                    'es_nuevo'       => true,
                    'registro_token' => $registroToken,
                    'message'        => 'Correo verificado. Redirigiendo a completar registro.'
                ], 200);
            }

            if (empty($usuarioBuscado->GoogleSub) && $googleSub) {
                $usuarioBuscado->GoogleSub = $googleSub;
                $usuarioBuscado->save();
            }

            if (isset($usuarioBuscado->EstadoCuenta) && $usuarioBuscado->EstadoCuenta !== 'Activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta se encuentra inactiva o suspendida.',
                ], 403);
            }

            return $this->crearRespuestaSesion(
                $usuarioBuscado,
                'Bienvenido al Sistema Bibliotecario, ' . $usuarioBuscado->NombreUsuario
            );

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación con el servicio institucional.'
            ], 401);
        }
    }

    // ================================================================
    // COMPLETAR EL REGISTRO DE UN NUEVO USUARIO
    // ================================================================
    public function completarRegistro(Request $request)
    {
        $request->validate([
            'registro_token'   => ['required', 'string', 'size:64'],
            'matricula'        => ['required', 'string', 'max:30', 'unique:usuarios,Matricula'],
            'telefono'         => ['required', 'string', 'max:20'],
            'grupo_id'         => ['nullable', 'integer', 'exists:grupos,Grupo_ID'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $datosRegistro = Cache::pull("registro-google:{$request->registro_token}");

            if (!$datosRegistro) {
                return response()->json([
                    'success' => false,
                    'message' => 'El proceso de registro expiró o no es válido. Inicia sesión con Google de nuevo.'
                ], 403);
            }

            $nuevoUsuario = new Usuario();
            $nuevoUsuario->GoogleSub         = $datosRegistro['google_sub'];
            $nuevoUsuario->Rol_ID            = Rol::USUARIO;
            $nuevoUsuario->CorreoElectronico = $datosRegistro['correo'];
            $nuevoUsuario->NombreUsuario     = $datosRegistro['nombre'];
            $nuevoUsuario->ApellidoPaterno   = $datosRegistro['apellido_paterno'];
            $nuevoUsuario->ApellidoMaterno   = $request->apellido_materno ?? '';
            $nuevoUsuario->Matricula         = $request->matricula;
            $nuevoUsuario->Telefono          = $request->telefono;
            $nuevoUsuario->Grupo_ID          = $request->grupo_id ?: null;
            $nuevoUsuario->EstadoCuenta      = 'Activo';
            $nuevoUsuario->save();

            return $this->crearRespuestaSesion($nuevoUsuario, 'Registro completado exitosamente.');

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al completar el registro. Intenta nuevamente.'
            ], 500);
        }
    }

    // ================================================================
    // HELPER: CREACIÓN DE SESIÓN SEGURA Y COOKIE
    // ================================================================
    private function crearRespuestaSesion(Usuario $usuario, string $mensaje)
    {
        $token = $usuario->createToken('usuario_token')->plainTextToken;
        $usuario->load(['grupo.carrera']);

        $rolTexto = ($usuario->Rol_ID === Rol::ADMIN) ? 'admin' : 'usuario';
        $isSecure = config('app.env') === 'production' || request()->isSecure();

        $cookie = cookie(
            'token',
            $token,
            60 * 24 * 7,
            '/',
            null,
            $isSecure,
            true,
            false,
            'Lax'
        );

        return response()->json([
            'success' => true,
            'es_nuevo' => false,
            'message' => $mensaje,
            'rol'     => Crypt::encryptString($rolTexto),
            'usuario' => $usuario
        ])->cookie($cookie);
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
