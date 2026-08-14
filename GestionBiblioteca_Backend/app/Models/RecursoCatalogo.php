<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecursoCatalogo extends Model
{
    protected $table = 'recursos_catalogo';
    protected $primaryKey = 'Recurso_ID';
    public $incrementing = true;
    
    protected $fillable = [
        'Titulo', 'Tema_ID', 'AnioPublicacion', 'Imagen_path', 'Observaciones', 
        'URL_Externa', 'Mensaje_Legal', 'Archivo_PDF', 'Autor_ID', 'Editorial_ID', 
        'TipoRecurso_ID', 'TipoRecurso',
        'Formato', 'Cantidad_Paginas', 'Idioma', 'Genero', 'Resumen'
    ];

    public function autor()
    {
        return $this->belongsTo(Autor::class, 'Autor_ID', 'Autor_ID');
    }

    public function editorial()
    {
        return $this->belongsTo(Editorial::class, 'Editorial_ID', 'Editorial_ID');
    }

    public function tema()
    {
        return $this->belongsTo(TemaCatalogo::class, 'Tema_ID', 'Tema_ID');
    }

    public function tipoRecurso()
    {
        return $this->belongsTo(TipoRecurso::class, 'TipoRecurso_ID', 'TipoRecurso_ID');
    }
}