<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    protected $table = 'devoluciones';
    protected $primaryKey = 'Devolucion_ID';
    public $incrementing = true;
    
    protected $fillable = [
        'DetallesPrestamo_ID', 
        'Personal_ID', 
        'FechaDevolucionReal', 
        'EstadoFisicoDevolucion'
    ]; 

    // Relación con la unidad/detalle del préstamo
    public function detallePrestamo()
    {
        return $this->belongsTo(DetallePrestamo::class, 'DetallesPrestamo_ID', 'DetallesPrestamo_ID');
    }

    // Relación con el usuario/personal bibliotecario que recibe la devolución
    public function personal()
    {
        return $this->belongsTo(Usuario::class, 'Personal_ID', 'Usuario_ID');
    }
}