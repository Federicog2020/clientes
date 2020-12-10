<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FeOpcionales extends Model
{
    protected $table = 'fe_opcionales';

    public function getData($id_factura) {
    	try {
    		$result = FeOpcionales::where('id_factura', '=', $id_factura)->get();

    		return $result;
    	} catch (Exception $e) {
    		echo $e;
    		return null;
    	}
    }
}
