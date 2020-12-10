<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FeCbtesAsoc extends Model
{
    protected $table = 'fe_cbtes_asoc';

    public function getDataByID($id_factura) {
    	try {
    		$result = DB::table('fe_cbtes_asoc as f')->where('f.id_factura', $id_factura)
    			->leftJoin('comprobantes as c', 'c.id', '=', 'f.tipo')
    			->select(DB::raw('CONCAT_WS(\'-\',LPAD(f.ptovta, 4, \'0\'),LPAD(f.nro, 8, 0)) as comprobante, c.descrip'))->get();

    		return $result;
    	} catch (Exception $e) {
    		echo $e;
    		return null;
    	}
    }
}
