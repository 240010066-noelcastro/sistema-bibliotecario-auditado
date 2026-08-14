<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePrestamo extends Model
{
    protected $table = 'detalles_prestamo';
    protected $primaryKey = 'DetallesPrestamo_ID';
    public $incrementing = true;
    
    protected $fillable = ['Prestamo_ID', 'Unidad_ID']; 

    // Relación con el préstamo maestro
    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class, 'Prestamo_ID', 'Prestamo_ID');
    }

    // Relación con la unidad física en inventario
    public function unidad()
    {
        return $this->belongsTo(InventarioUnidad::class, 'Unidad_ID', 'Unidad_ID');
    }
}