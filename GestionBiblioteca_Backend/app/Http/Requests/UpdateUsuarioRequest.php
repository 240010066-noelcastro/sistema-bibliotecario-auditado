<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtiene el ID del usuario directamente de la ruta (/usuarios/{id})
        $id = $this->route('id') ?? $this->route('usuario');

        return [
            'NombreUsuario'     => 'required|string|max:50',
            'ApellidoPaterno'   => 'required|string|max:50',
            'ApellidoMaterno'   => 'nullable|string|max:50',
            'CorreoElectronico' => 'required|email|max:100|unique:usuarios,CorreoElectronico,' . $id . ',Usuario_ID',
            'Matricula'         => 'nullable|string|max:30|unique:usuarios,Matricula,' . $id . ',Usuario_ID',
            'Telefono'          => 'nullable|string|max:20',
            'Direccion'         => 'nullable|string',
            'Grupo_ID'          => 'nullable|exists:grupos,Grupo_ID',
            'Rol_ID'            => 'nullable|exists:roles,Rol_ID',
            'EstadoCuenta'      => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'CorreoElectronico.unique' => 'Este correo electrónico ya pertenece a otro usuario.',
            'Matricula.unique'         => 'Esta matrícula ya está asignada a otro usuario.',
            'CorreoElectronico.email'  => 'El formato del correo electrónico no es válido.',
            'Grupo_ID.exists'          => 'El grupo seleccionado no existe.',
        ];
    }
}