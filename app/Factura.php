<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\ServEmpFac;

class Factura extends Model
{
    protected $table = 'serv_facturas';

    public function getPeriodos() {
    	$result = DB::table('serv_facturas')
            ->where('cod_clie', auth()->user()->id)
            ->select(DB::raw('anio as periodo'))
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->get();

    	return $result;
    }

    public function getComprobanteByID($id_doc) {
        try {
            $result = Factura::where('id', $id_doc)->get();

            return $result->toArray();
        } catch (Exception $e) {
            echo $e;
            return null;
        }
    }

    public function getComprobantes($param) {
    	try {
    		$result = DB::table('serv_facturas')->where([
    			['cod_clie', '=', auth()->user()->id],
    			['anio', '=', $param['anio']]
    		])->select(DB::raw('id, date_format(fec_fac, \'%d/%m/%Y\') as fecha, tipo, letra, CONCAT_WS(\'-\', LPAD(pto_venta, 4, \'0\'), LPAD(nro_fac, 8, \'0\')) as nro_doc'))->get();

    		return $result;
    	} catch (Exception $e) {
    		echo $e;
    		return null;
    	}
    }

    public function getPdfCompAImprimir($id_doc) {
        try {
            $result = DB::table('serv_facturas')->where('id', $id_doc)->select(DB::raw('id, nro_fac, pto_venta, letra, cod_tipo, cod_emp'))->get();

            return $result;
        } catch (Exception $e) {
            echo $e;
            return null;
        }
    }

    public function getPdfDatosEmpresa($id_doc) {
        try {
            $result = DB::table('serv_facturas as f')->where('f.id', $id_doc)
                ->leftJoin('empresas as e', 'e.id', '=', 'f.cod_emp')
                ->leftJoin('provincias as p', 'p.codigo', '=', 'e.provincia')
                ->leftJoin('sucursales as suc', 'suc.id' , '=' , 'f.sucursal')
                ->select(DB::raw('f.*, e.*, p.descripcion as desc_prov, IF(suc.direccion IS NULL,\'\',suc.direccion) as direc_sucursal'))->get();

            return $result;
        } catch (Exception $e) {
            echo $e;
            return null;
        }
    }

    public function getPdfDatosCliente($id_doc) {
        try {
            $result = DB::table('serv_facturas as f')->where('f.id', $id_doc)
                ->leftJoin('clientes as cl', 'cl.id', '=', 'f.cod_clie')
                ->leftJoin('provincias as p1',  'p1.codigo', '=', 'cl.provincia')
                ->leftJoin('condiva as ci', 'ci.codigo', '=', 'cl.condiva')
                ->select(DB::raw('cl.*, p1.descripcion as desc_provclie, ci.descripcion as desc_iva'))
                ->get();

            return $result;
        } catch (Exception $e) {
            echo $e;
            return null;
        }
    }

    public function getDetalles($id_factura) {
        try {
            $result = ServEmpFac::where('id_factura', $id_factura)->get();

            return $result;
        } catch (Exception $e) {
            echo $e;
            return null;
        }
    }
}
