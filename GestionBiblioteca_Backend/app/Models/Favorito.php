<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorito extends Model
{
    protected $table = 'favoritos';
    public $incrementing = false;

    protected $fillable = [
        'Usuario_ID',
        'Recurso_ID'
    ];

    // Relación con el alumno que guardó el favorito
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'Usuario_ID', 'Usuario_ID');
    }

    // Relación con el recurso del catálogo
    public function recurso()
    {
        return $this->belongsTo(RecursoCatalogo::class, 'Recurso_ID', 'Recurso_ID');
    }
}