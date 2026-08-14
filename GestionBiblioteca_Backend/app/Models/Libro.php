<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libro extends Model 
{
    protected $table = 'libros';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['Recurso_ID', 'EdicionVolumen', 'ClasificacionISBN'];

    public function recursoCatalogo()
    {
        return $this->belongsTo(RecursoCatalogo::class, 'Recurso_ID', 'Recurso_ID');
    }
}