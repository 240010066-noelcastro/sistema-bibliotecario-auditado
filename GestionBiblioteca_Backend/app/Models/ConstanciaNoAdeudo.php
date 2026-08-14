<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaNoAdeudo extends Model
{
    protected $table = 'constancias_no_adeudo';
    protected $primaryKey = 'ConstanciaID';
    public $incrementing = true;

    protected $fillable = ['Usuario_ID', 'Personal_ID', 'FechaEmision', 'FolioDigital'];

    // Relación con el alumno/usuario solicitante
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'Usuario_ID', 'Usuario_ID');
    }

    // Relación con el administrativo/encargado firmante (almacenado en la tabla 'usuarios')
    public function personal()
    {
        return $this->belongsTo(Usuario::class, 'Personal_ID', 'Usuario_ID');
    }
}