<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    public function facturas() {
    	return $this->hasMany('App\Factura');
    }

    public function empresa() {
    	return $this->BelongsTo('App\Empresa', 'id_emp');
    }
}
