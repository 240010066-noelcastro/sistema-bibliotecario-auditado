<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        // 🛡️ BLOQUEO DE SEGURIDAD: Solo el Administrador puede listar usuarios
        if (!$request->user() || $request->user()->Rol_ID !== Rol::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado. Requiere permisos de Administrador.'], 403);
        }

        try {
            // Hacemos LEFT JOIN, pero FILTRAMOS estrictamente al Rol (Usuarios/Alumnos)
            $query = DB::table('usuarios')
                ->join('roles', 'usuarios.Rol_ID', '=', 'roles.Rol_ID')
                ->leftJoin('grupos', 'usuarios.Grupo_ID', '=', 'grupos.Grupo_ID')
                ->leftJoin('carreras', 'grupos.Carrera_ID', '=', 'carreras.Carrera_ID')
                ->select('usuarios.*', 'grupos.NombreGrupo', 'carreras.NombreCarrera')
                ->where('usuarios.Rol_ID', '=', Rol::USUARIO->value);

            // 1. Motor de búsqueda en tiempo real
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('usuarios.Matricula', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CONCAT(usuarios.NombreUsuario, ' ', IFNULL(usuarios.ApellidoPaterno, ''), ' ', IFNULL(usuarios.ApellidoMaterno, '')) LIKE ?", ["%{$search}%"])
                      ->orWhere('usuarios.NombreUsuario', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.ApellidoPaterno', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.CorreoElectronico', 'LIKE', "%{$search}%")
                      ->orWhere('grupos.NombreGrupo', 'LIKE', "%{$search}%")
                      ->orWhere('carreras.NombreCarrera', 'LIKE', "%{$search}%")
                      ->orWhere('usuarios.Telefono', 'LIKE', "%{$search}%");
                });
            }

            // Filtro desplegable por Estado (Solo aplica si no es 'Todos')
                      if ($request->has('estado') && !empty($request->estado) && $request->estado !== 'Todos') {
                          $query->where('usuarios.EstadoCuenta', '=', $request->estado);
                     }

            // 2. Exportar todos los datos (Excel/PDF)
            if ($request->has('all')) {
                return response()->json(['success' => true, 'data' => $query->get()]);
            }

            // 3. Paginación
            $usuarios = $query->paginate(6);
            return response()->json(['success' => true, 'data' => $usuarios]);
            
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al obtener la lista de usuarios.'], 500);
        }
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $data['Rol_ID'] = Rol::USUARIO->value; 

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado con éxito',
            'data' => Usuario::create($data)
        ], 201);
    }

    public function update(UpdateUsuarioRequest $request, $id)
    {
        $usuario = Usuario::where('Rol_ID', Rol::USUARIO->value)->findOrFail($id);
        
        $data = $request->validated();
        unset($data['Rol_ID']);

        $usuario->update($data);

        return response()->json(['success' => true, 'data' => $usuario], 200);
    }
    public function destroy(Request $request, $id)
    {
        if (!$request->user() || $request->user()->Rol_ID !== Rol::ADMIN) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado.'], 403);
        }

        try {
            // BLOQUEO DE SEGURIDAD: Solo permite borrar usuarios
            Usuario::where('Rol_ID', Rol::USUARIO->value)->findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar porque tiene préstamos o sanciones asociadas.'], 500);
        }
    }

    // ---> NUEVO MÉTODO PARA CARGAR EL PERSONAL EN PRESTAMOS <---
    public function getPersonal(Request $request)
    {
        try {
            $personal = DB::table('usuarios')
                ->select('Usuario_ID as Personal_ID', 'NombreUsuario as NombrePersonal', 'ApellidoPaterno')
                ->where('Rol_ID', '=', Rol::ADMIN->value)
                ->get();

            return response()->json(['success' => true, 'data' => $personal]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al consultar el personal administrativo.'], 500);
        }
    }

    public function registrarAdmin(Request $request)
    {
        try {
            $data = $request->validate([
                'NombreUsuario'     => ['required', 'string', 'max:50'],
                'ApellidoPaterno'   => ['required', 'string', 'max:50'],
                'ApellidoMaterno'   => ['nullable', 'string', 'max:50'],
                'CorreoElectronico' => ['required', 'email', 'max:100', 'unique:usuarios,CorreoElectronico'],
                'Telefono'          => ['nullable', 'string', 'max:20'],
            ]);

            $data['Rol_ID'] = Rol::ADMIN->value;
            $data['EstadoCuenta'] = 'Activo';

            $admin = Usuario::create($data);

            return response()->json([
                'success' => true, 
                'message' => 'Administrador registrado con éxito.', 
                'data' => $admin
            ], 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al registrar el administrador.'], 500);
        }
    }
}