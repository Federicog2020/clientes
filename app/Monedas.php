<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Monedas extends Model
{
    protected $table = 'monedas';

    public function getEnLetras($cod) {
    	try {
    		$result = Monedas::where('cod', $cod)->pluck('en_letras')->toArray();

    		return $result;
    	} catch (Exception $e) {
    		echo $e;
    		return null;
    	}
    }
}
