<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServEmpFac extends Model
{
    protected $table = 'serv_emp_fac';

    public function getDataByIDFactura($ultimo_id, $id_factura) {
    	try {
    		$result = DB::table('serv_emp_fac as s')
            ->where([
                ['s.id', '>=', $ultimo_id],
                ['s.id_factura', '=', $id_factura]
            ])
            ->leftJoin('servicios as se', 'se.id', '=', 's.cod_serv')
            ->select(DB::raw('s.*, se.cod'))->get();

    		return $result;
    	} catch (Exception $e) {
    		echo $e;
    		return null;
    	}
    }
}
