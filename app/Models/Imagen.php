<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    protected $table = "imagenes_";
    protected $fillable = ['nombre', 'producto_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
