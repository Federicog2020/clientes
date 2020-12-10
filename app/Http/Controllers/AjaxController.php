<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Factura;

class AjaxController extends Controller
{
    public function ajaxRequestPost(Request $request) {
    	$param = array('mes' => $request['mes'], 'anio' => $request['anio']);
    	
    	$fac = new Factura();

    	$result = $fac->getComprobantes($param);

    	return response()->json($result);
    }

    public function getDetallesComprobante(Request $request) {
    	$fac = new Factura();

    	$result = $fac->getDetalles($request['id_factura']);

    	return response()->json($result);
    }
}
