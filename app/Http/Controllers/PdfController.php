<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Extensions\ImpreEncab;
use App\Extensions\fpdf\FPDF;

use App\Factura;
use App\FeOpcionales;
use App\FeCbtesAsoc;
use App\ServEmpFac;
use App\Monedas;

class PdfController extends Controller
{
	public function pdfGenerator(Request $request) {
		$arRet = array('error' => false, 'data' => array());

		try {
			if (is_array($request['id_doc'])) {
				foreach ($request['id_doc'] as $id) {
					array_push($arRet['data'], strval($this->generarPDF($id)));
				}

				if (count($arRet['data']) > 1) {
					$zipname = storage_path('app/pdfs/').'comprobantes.zip';
					$zip = new \ZipArchive();
					$zip->open($zipname, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
					foreach ($arRet['data'] as $file) {
						$nom_arch = $file;
					  	$zip->addFile(storage_path().'/app/pdfs/'.$file, $file);
					}
					$zip->close();

					$arRet['data'] = array();

					array_push($arRet['data'], 'comprobantes.zip');
				}
			}
		} catch (Exception $e) {
			$arRet['error'] = true;
			array_push($arRet['data'], $e);
		}

		return response()->json($arRet);
	}

    public function generarPDF($idsDoc)
	{
		try {
			$ret_nombres = "";

			$fac = new Factura();

			$result = $fac->getPdfCompAImprimir($idsDoc);
			
			if ($result != null)
			{
				$id_emp = $result[0]->cod_emp;
				//Leer la configuración de las fuentes
				$fuentes = array();

				if (!file_exists('../../empresas/sistema/configFac_files/configFac_'.$id_emp.'.txt'))
				{
					$archivo = file('../../empresas/sistema/configFac_files/configFac.txt');
				}
				else
				{
					$archivo = file('../../empresas/sistema/configFac_files/configFac_'.$id_emp.'.txt');
				}

				$cad = "";
				
				for($k=0;$k<count($archivo);$k++)
				{
					$cad = explode("|",$archivo[$k]);
					$fuentes[$cad[0]] = $cad[1];
				}
					
				$rows = count($result);

				for ($m=0;$m<$rows;$m++)
				{

					$idFac = $result[$m]->id;
					$nroFac = sprintf("%04s", $result[$m]->pto_venta)."-".sprintf("%08s", $result[$m]->nro_fac);
					$letra = $result[$m]->letra;
					$tipo = "";
					switch ($result[$m]->cod_tipo)
					{
						case 1:
							$tipo = "Factura";
							break;
						case 2:
							$tipo = "Nota de débito";
							break;
						case 3:
							$tipo = "Nota de crédito";
							break;
						case 6:
							$tipo = "Factura";
							break;
						case 7:
							$tipo = "Nota de débito";
							break;
						case 8:
							$tipo = "Nota de crédito";
							break;
						case 11:
							$tipo = "Factura";
							break;
						case 12:
							$tipo = "Nota de débito";
							break;
						case 13:
							$tipo = "Nota de crédito";
							break;
						case 201: case 206: case 211:
						    $tipo = "Factura de Crédito Electrónica";
						    break;
						case 202: case 207: case 212:
						    $tipo = "Nota de Débito Electrónica";
						    break;
						case 203: case 208: case 213:
						    $tipo = "Nota de Crédito Electrónica";
						    break;
						default:
							$tipo = "";
							break;
					}
					$razonClie = auth()->user()->razon;
					
					//Crear PDFs**********************************************
					$pdf = new FPDF('P','mm','A4');
					$pdf->AliasNbPages();
					$pdf->SetLeftMargin(20);
					$pdf->SetRightMargin(10);
					$pdf->SetTopMargin(10);
					$pdf->SetAutoPageBreak(false);
					$pdf->AddFont('PF-I2OF5','','i2of5NT.php');
					
					$suma_siva = 0;
					$suma_civa = 0;
					$suma_iva = 0;
					$suma_apagar = 0;
					$num_pag = 0;
					$bHayDetalles = true;
					$ultimo_id = 0;
					$pagina = 1;
					$paginas_id = "";
					$cotizacion = 1.00;
					
					//Obtener los datos del documento
					
					$result_fac = $fac->getComprobanteByID($idsDoc);

					$result_1 = $fac->getPdfDatosEmpresa($idsDoc);

					$result_clie = $fac->getPdfDatosCliente($idsDoc);
					
					$encabezado = new ImpreEncab();
					$encabezado->letra = $result_fac[0]['letra'];
					$encabezado->cod_tipo = $result_fac[0]['cod_tipo'];
					$encabezado->nro_fac = $result_fac[0]['nro_fac'];
					$encabezado->pto_venta = $result_fac[0]['pto_venta'];
					$encabezado->nro_doc = sprintf("%04s-%08s",$encabezado->pto_venta,$encabezado->nro_fac);
					$encabezado->fec_fac = date("d/m/Y",strtotime($result_fac[0]['fec_fac']));
					$encabezado->cae = $result_fac[0]['cae'];
					$encabezado->fec_vto_cae = date("d/m/Y",strtotime($result_fac[0]['fec_vto_cae']));
					$encabezado->fecha_desde = $result_fac[0]['fec_desde'];
					$encabezado->fecha_hasta = $result_fac[0]['fec_hasta'];
					$fec_vto_codBar = date("Ymd",strtotime($result_fac[0]['fec_vto_cae']));
					$encabezado->otros = $result_1[0]->otros;
					$encabezado->condVenta = $result_fac[0]['cond_venta'];
					$encabezado->formaPago = $result_fac[0]['forma_pago'];
					if (($result_fac[0]['fec_vto'] != NULL) && ($result_fac[0]['fec_vto'] != '0000-00-00'))
					{
						$encabezado->fec_vto = date("d/m/Y",strtotime($result_fac[0]['fec_vto']));
					}
					$encabezado->nroOC = $result_fac[0]['nro_oc'];
					$encabezado->razonEmp = $result_1[0]->razon;
					$encabezado->cuitEmp = $result_1[0]->cuit;
					$cuit_codBar = $result_1[0]->cuit;
					$encabezado->ing_br = $result_1[0]->ing_br;
					$encabezado->fec_inicio = date("d/m/Y",strtotime($result_1[0]->fec_inicio));
					$encabezado->calle = $result_1[0]->calle;
					$encabezado->nro = $result_1[0]->nro;
					$encabezado->piso = $result_1[0]->piso;
					$encabezado->depto = $result_1[0]->depto;
					$encabezado->sector = $result_1[0]->sector;
					$encabezado->torre = $result_1[0]->torre;
					$encabezado->manzana = $result_1[0]->manzana;
					$encabezado->provi = $result_1[0]->desc_prov;
					$encabezado->cp = $result_1[0]->cp;
					$encabezado->localidad = $result_1[0]->localidad;
					$encabezado->tel = $result_1[0]->telefono;
					$encabezado->web = $result_1[0]->web;
					$encabezado->email = $result_1[0]->mail;
					$encabezado->cond_iva = $result_1[0]->cond_iva;
					$encabezado->print_public = $result_1[0]->print_public;
					$encabezado->CLrazon = $result_clie[0]->razon;
					$encabezado->CLcalle = $result_clie[0]->domicilio;
					$encabezado->CLnro = $result_clie[0]->nro;
					$encabezado->CLpiso = $result_clie[0]->piso;
					$encabezado->CLdepto = $result_clie[0]->depto;
					$encabezado->CLsector = $result_clie[0]->sector;
					$encabezado->CLtorre = $result_clie[0]->torre;
					$encabezado->CLmanzana = $result_clie[0]->manzana;
					$encabezado->CLprefijo = $result_clie[0]->prefijo;
					$encabezado->CLcp = $result_clie[0]->cp;
					$encabezado->CLsufijo = $result_clie[0]->sufijo;
					$encabezado->CLlocalidad = $result_clie[0]->localidad;
					$encabezado->CLprovincia = $result_clie[0]->desc_provclie;
					$encabezado->CLcond_iva = $result_clie[0]->desc_iva;
					$encabezado->CLcuit = $result_clie[0]->cuit;
					$encabezado->tipo_doc = "";
					$encabezado->leyenda_fac = $result_1[0]->leyenda_fac;
					$encabezado->domiSucursal = $result_1[0]->direc_sucursal;
					
					$cotizacion = $result_fac[0]['cotiza'];
					$moneda = $result_fac[0]['id_moneda'];
					
					if (isset($fuentes["cbu_info"]))
					{
						if ($fuentes["cbu_info"] == "1")
							$encabezado->cbu_informada = "Pago en C.B.U. informada";
						else
							$encabezado->cbu_informada = "";
					}
					
					if (strlen($encabezado->cuitEmp) == 11)
						$encabezado->cuitEmp = substr($encabezado->cuitEmp,0,2)."-".substr($encabezado->cuitEmp,2,8)."-".substr($encabezado->cuitEmp,10,1);
					
					switch ($encabezado->cod_tipo)
					{
						case 1:
							$encabezado->tipo_doc = "Factura";
							break;
						case 2:
							$encabezado->tipo_doc = "Nota de Débito";
							break;
						case 3:
							$encabezado->tipo_doc = "Nota de Crédito";
							break;
						case 6:
							$encabezado->tipo_doc = "Factura";
							break;
						case 7:
							$encabezado->tipo_doc = "Nota de Débito";
							break;
						case 8:
							$encabezado->tipo_doc = "Nota de Crédito";
							break;
						case 11:
							$encabezado->tipo_doc = "Factura";
							break;
						case 12:
							$encabezado->tipo_doc = "Nota de Débito";
							break;
						case 13:
							$encabezado->tipo_doc = "Nota de Crédito";
							break;
						case 201: case 206: case 211:
						    $encabezado->tipo_doc = "Factura de Crédito Electrónica";
						    break;
						case 202: case 207: case 212:
						    $encabezado->tipo_doc = "Nota de Débito Electrónica";
						    break;
						case 203: case 208: case 213:
						    $encabezado->tipo_doc = "Nota de Crédito Electrónica";
						    break;
						default:
							$encabezado->tipo_doc = "Desconocido";
							break;
					}
					
					$encabezado->direccion = $encabezado->calle." ".$encabezado->nro;
					if ($encabezado->piso != "")
						$encabezado->direccion .= " Piso ".$encabezado->piso;
					if ($encabezado->depto != "")
						$encabezado->direccion .= " Dpto ".$encabezado->depto;
					if ($encabezado->sector != "")
						$encabezado->direccion .= " Sector ".$encabezado->sector;
					if ($encabezado->torre != "")
						$encabezado->direccion .= " Torre ".$encabezado->torre;
					if ($encabezado->manzana != "")
						$encabezado->direccion .= " Manzana ".$encabezado->manzana;
						
					$encabezado->locali = "";
					if ($encabezado->cp != "")
						$encabezado->locali = "(".$encabezado->cp.") ";
					$encabezado->locali .= $encabezado->localidad;
				
					if ((is_numeric($encabezado->CLcuit)) && (strlen($encabezado->CLcuit) == 11))
						$encabezado->CLcuit = substr($encabezado->CLcuit,0,2)."-".substr($encabezado->CLcuit,2,8)."-".substr($encabezado->CLcuit,10,1);
						
					$encabezado->CLdireccion = $encabezado->CLcalle." ".$encabezado->CLnro;
					if ($encabezado->CLpiso != "")
						$encabezado->CLdireccion .= " Piso ".$encabezado->CLpiso;
					if ($encabezado->CLdepto != "")
						$encabezado->CLdireccion .= " Dpto ".$encabezado->CLdepto;
					if ($encabezado->CLsector != "")
						$encabezado->CLdireccion .= " Sector ".$encabezado->CLsector;
					if ($encabezado->CLtorre != "")
						$encabezado->CLdireccion .= " Torre ".$encabezado->CLtorre;
					if ($encabezado->CLmanzana != "")
						$encabezado->CLdireccion .= " Manzana ".$encabezado->CLmanzana;
						
					$encabezado->CLcodpos = "";
					if ($encabezado->CLprefijo != "")
						$encabezado->CLcodpos = $encabezado->CLprefijo;
					if ($encabezado->CLcp != "")
						$encabezado->CLcodpos .= $encabezado->CLcp;
					if ($encabezado->CLsufijo != "")
						$encabezado->CLcodpos .= $encabezado->CLsufijo;
					
					if (file_exists("../../empresas/facturacion/logos/".$id_emp.".jpg"))
						$encabezado->logo_path = "../../empresas/facturacion/logos/".$id_emp.".jpg";
					else
						$encabezado->logo_path = "../../empresas/facturacion/logos/blanco.jpg";
					
					$encabezado->fuentes = $fuentes;
					
					/*
					 * DATOS FCE
					 */
					$feOp = new FeOpcionales();

					$result_opcionales = $feOp->getData($idsDoc);
					if ($result_opcionales != null) {
						foreach ($result_opcionales as $key => $value) {
							$encabezado->opcionales_fce = $value['valor'];
						}
					}
					
					$fe_asoc = new FeCbtesAsoc();
					$comprobantes_asociados = "";
					
					$result_cbtes_asoc = $fe_asoc->getDataByID($idsDoc);

					if ($result_cbtes_asoc != null) {
					    $rows_cbtes_asoc = count($result_cbtes_asoc);
					    for ($indice_cbtes_asoc=0;$indice_cbtes_asoc<$rows_cbtes_asoc;$indice_cbtes_asoc++) {
					        $comprobantes_asociados[$indice_cbtes_asoc] = $result_cbtes_asoc[$indice_cbtes_asoc]->descrip.' '.$result_cbtes_asoc[$indice_cbtes_asoc]->comprobante;
					    }
					}
					/*
					 * FIN DATOS FCE
					 */
					
					$alicu_iva = "21";
					
					//Armar Cadena para Codigo de Barras
					$cadCodigo = $cuit_codBar.str_pad($encabezado->cod_tipo,2,"0",STR_PAD_LEFT).str_pad($encabezado->pto_venta,4,"0",STR_PAD_LEFT).$encabezado->cae.$fec_vto_codBar;
					$descrip = "";
					
					while ($bHayDetalles)
					{
						$X = 0;
						$Y = 0;
						$Xini = 0;
						$sumIVA_0 = 0;
						$sumIVA_105 = 0; //10.5
						$sumIVA_21 = 0; //21.00
						$sumIVA_27 = 0; //27.00
						$sumIVA_25 = 0; //2.50
						$sumIVA_5 = 0; //5.00
						$sumIVA_nograv = 0;
						$sumIVA_exento = 0;
						$suma_percepcion = 0;
						$suma_netograv = 0;
						$descuento = 0;
						$leyenda_percep = 'Percep IIBB';

						$encabezado->Imprimir($pdf, $X, $Y, $Xini);
						
						$anchoImporte = $pdf->GetStringWidth("  0.000.000.000,00");
						$Xfin = $X - $anchoImporte;
						
						$anchoLinea = $Xfin-$Xini;
						
						$serv = new ServEmpFac();

						$result_2 = $serv->getDataByIDFactura($ultimo_id, $idsDoc);

						$rows_2 = count($result_2);

						if ($rows_2 > 0) {
							for ($i=0;$i<$rows_2;$i++)
							{
								$ultimo_id = $result_2[$i]->id;
								if ($id_emp == 120) //Pinceles Delfín
									$cod_serv = $result_2[$i]->cod;
								else
									$cod_serv = $result_2[$i]->cod_serv;
								$descuento = number_format((($result_2[$i]->cant * $result_2[$i]->prec_unit) * $result_2[$i]->descuento) / 100, 2, '.', '');
								$cant_number = number_format($result_2[$i]->cant,2,".","");
								$cant = number_format($result_2[$i]->cant,2,",",".");
								$prec_unit_number = number_format($result_2[$i]->prec_unit,2,".","");
								$prec_unit = number_format($result_2[$i]->prec_unit,2,",",".");
								$total_siva = $result_2[$i]->total_siva + $result_2[$i]->imp_exento + $result_2[$i]->imp_nogravado;
								if ($id_emp == 120)
									$total_siva += $descuento;
								$total_civa = $result_2[$i]->total_civa;
								//$suma_netograv += $result_2[$i]->total_siva");
								if ($encabezado->letra == "C")
									$suma_netograv += $result_2[$i]->total_civa;
								else
									$suma_netograv += $result_2[$i]->total_siva;
								$iva = $result_2[$i]->iva;
								$prec_unit_civa_number = 0;
								if ($cant == 0)
									$prec_unit_civa = 0;
								else
								{
									$prec_unit_civa = number_format($total_civa/$cant_number,4,",",".");
									$prec_unit_civa_number = number_format($total_civa/$cant_number,4,".","");
								}
								
								$descrip = utf8_decode($result_2[$i]->serv_descrip);
								$descrip .= "\n".utf8_decode($result_2[$i]->descrip);
								$descrip = explode("\n", wordwrap($descrip,68,"\n",1));
								$suma_siva += $total_siva;
								$suma_civa += $total_civa;
								if ($result_2[$i]->imp_exento != 0)
									$sumIVA_exento += $result_2[$i]->imp_exento;
								if ($result_2[$i]->imp_nogravado != 0)
									$sumIVA_nograv += $result_2[$i]->imp_nogravado;
								if ($result_2[$i]->imp_percepcion != 0)
								{
									$suma_percepcion += $result_2[$i]->imp_percepcion;
									if ($result_2[$i]->percepcion == '')
										$leyenda_percep = 'Percep IIBB';
								}
								/*else
								{*/
									switch ($result_2[$i]->id_alicuiva)
									{
										case 3:
											$sumIVA_0 += $iva;
											break;
										case 4:
											$sumIVA_105 += $iva;
											break;
										case 5:
											$sumIVA_21 += $iva;
											break;
										case 6:
											$sumIVA_27 += $iva;
											break;
										case 6:
											$sumIVA_5 += $iva;
											break;
										case 9:
											$sumIVA_25 += $iva;
											break;
									}
								/*}*/
								//$suma_iva += $iva;
								$total_siva = number_format($total_siva,2,",",".");
								$total_civa = number_format($total_civa,2,",",".");
								
								$paginas_id[$pagina-1] = $ultimo_id;
								
								//Código
								if ($id_emp == 120) //Pinceles Delfín
									$X = 26;
								else
									$X = 30;
								$pdf->Text($X,$Y,sprintf("%04s",$cod_serv));
								//Total
								$X = 195;
								if ($encabezado->letra == "A") //Comprobantes A
								{
									$anchoCad = $pdf->GetStringWidth($total_siva);
									$pdf->Text($X-$anchoCad,$Y,$total_siva);
								}
								else //Comprobantes B
								{
									$anchoCad = $pdf->GetStringWidth($total_civa);
									$pdf->Text($X-$anchoCad,$Y,$total_civa);
								}
								//Precio unitario
								$X -= $anchoImporte;
								if ($encabezado->letra == "A") //Comprobantes A
								{
									if ($prec_unit_number != 0)
									{
										$anchoCad = $pdf->GetStringWidth($prec_unit);
										$pdf->Text($X-$anchoCad,$Y,$prec_unit);
									}
								}
								else //Comprobantes B
								{
									if ($prec_unit_civa_number != 0)
									{
										$anchoCad = $pdf->GetStringWidth($prec_unit_civa);
										$pdf->Text($X-$anchoCad,$Y,$prec_unit_civa);
									}
								}
								//Cantidad
								$X -= $anchoImporte;
								if ($cant != 0)
								{
									$anchoCad = $pdf->GetStringWidth($cant);
									$pdf->Text($X-$anchoCad,$Y,$cant);
								}
								
								for ($j=0;$j<count($descrip);$j++)
								{
									$pdf->Text($Xini,$Y,$descrip[$j]);
									$Y +=4;
									if ($Y > 250)
									{
										//Linea
										$Y = 260;
										$pdf->Line(20,$Y,200,$Y);
										$Y += 4;
										$pdf->Text(25,$Y,"Continua página ".($encabezado->num_pag+1));
								
										$pagina++;
										$X = 0;
										$Y = 0;
										$Xini = 0;
										
										$encabezado->Imprimir($pdf, $X, $Y, $Xini);
									}
								}
								
								//Imprimir el descuento para pinceles delfin
								if ($id_emp == 120)
								{
									if ($descuento > 0)
									{
										$pdf->Text($Xini,$Y-4,'Bonificación ('.$result_2[$i]->descuento.'%)');
										$X = 195;
										if ($encabezado->letra == "A") //Comprobantes A
										{
											$anchoCad = $pdf->GetStringWidth(number_format(-$descuento, 2, ',', '.'));
										$pdf->Text($X-$anchoCad,$Y-4,number_format(-$descuento, 2, ',', '.'));
										}
									}
								}
								
								if ($i == ($rows_2-1))
									$bHayDetalles = false;
								else
									$bHayDetalles = true;
								$alicu_iva = $result_2[$i]->alicu_iva;
							}
						}
						else {
							$bHayDetalles = false;
						}
						
						//Suma Total del comprobante
						//Linea
						if ($id_emp == 102) {
							$Y = 245;
							$pdf->Line(20,$Y,200,$Y);
							$Y += 4;
							$texto_usz_nacion = 'Cta. Cte. Bco. Nación Suc. 3850 nº 4300199/70 a nombre de';
							$pdf->Text(20,$Y,$texto_usz_nacion);
							$Y += 4;
							$texto_usz_nacion = 'Un Sentimientp Zarateño S.R.L. - cuit 30-70884909-6';
							$pdf->Text(20,$Y,$texto_usz_nacion);
							$Y += 4;
							$texto_usz_nacion = 'CBU 0110043320004300199701';
							$pdf->Text(20,$Y,$texto_usz_nacion);

							$Y = 249;
							$texto_usz_provincia = 'Cta. Cte. Bco. Provincia Suc. 7103 nº 050526/0 a nombre de';
							$pdf->Text(120,$Y,$texto_usz_provincia);
							$Y += 4;
							$texto_usz_provincia = 'Un Sentimientp Zarateño S.R.L. - cuit 30-70884909-6';
							$pdf->Text(120,$Y,$texto_usz_provincia);
							$Y += 4;
							$texto_usz_provincia = 'CBU 0140036601710305052604';
							$pdf->Text(120,$Y,$texto_usz_provincia);
						}
						else if ($encabezado->cod_tipo > 200) { //ES FCE
						    if (is_array($comprobantes_asociados)) {
						        $Y = 240;
						        $pdf->Line(20,$Y,200,$Y);
						        $Y += 4;
						        $texto_titulo_cbtes_asoc = 'Comprobantes asociados:';
						        $pdf->Text(20,$Y,$texto_titulo_cbtes_asoc);
						        $Y += 4;
						        
						        for($indice_cbtes_asoc=0;$indice_cbtes_asoc<count($comprobantes_asociados);$indice_cbtes_asoc++) {
						            $texto_cbtes_asoc = $comprobantes_asociados[$indice_cbtes_asoc];
						            $pdf->Text(20,$Y,$texto_cbtes_asoc);
						            $Y += 4;
						        }
						        $Y = 260;
						    }
						}

						$Y = 260;
						$pdf->Line(20,$Y,200,$Y);
					}


					$suma_apagar = number_format($suma_netograv + $sumIVA_0 + $sumIVA_105 + $sumIVA_21 + $sumIVA_27 + $sumIVA_5 + $sumIVA_25 + $sumIVA_exento + $suma_percepcion + $sumIVA_nograv,2,",",".");
				
					$Y += 2;
					$X = 195;
						
					if ($encabezado->letra == "B")
					{
						$pdf->SetFont('Arial','B',6);
						$pdf->Text(25,$Y,"Este comprobante incluye $ ".number_format($sumIVA_0 + $sumIVA_105 + $sumIVA_21 + $sumIVA_27 + $sumIVA_5 + $sumIVA_25,2,",",".")." de IVA");
						
						$pdf->SetFont('Arial','B',10);
						$Y += 1;
						$pdf->Text($X-$anchoImporte-19,$Y,"TOTAL");
						$anchoCad = $pdf->GetStringWidth($suma_apagar);
						$pdf->Text($X-$anchoCad,$Y,$suma_apagar);
						$Y += 6;
					}
					else if ($encabezado->letra == "C")
					{
						$pdf->SetFont('Arial','B',10);
						$Y += 1;
						$pdf->Text($X-$anchoImporte-19,$Y,"TOTAL");
						$anchoCad = $pdf->GetStringWidth($suma_apagar);
						$pdf->Text($X-$anchoCad,$Y,$suma_apagar);
						$Y += 6;
					}
					else
						$pdf->SetFont('Arial','B',8);
					
					$Y += 2;

					$pdf->SetFont('Arial','B',intval($fuentes['total']));
					
					if ($encabezado->letra == "A")
					{
						$pdf->SetFont('Arial','',7.5);
						//Cuadro
						$Y -= 1;
						$XiniCuad = 16;
						$XfinCuad = 204;
						$AnchoNum = $pdf->GetStringWidth("999.999.999,99");
						$pdf->Line($XiniCuad,$Y,$XfinCuad,$Y); //Superior
						$pdf->Line($XiniCuad,$Y,$XiniCuad,$Y+9); //Izquierda
						$pdf->Line($XfinCuad,$Y,$XfinCuad,$Y+9); //Derecha
						$pdf->Line($XiniCuad,$Y+9,$XfinCuad,$Y+9); //Inferior
						$pdf->Line($XiniCuad,$Y+4,$XfinCuad,$Y+4); //Medio
						$pdf->Line($XiniCuad+$AnchoNum,$Y,$XiniCuad+$AnchoNum,$Y+9); //Primero
						$pdf->Line($XiniCuad+($AnchoNum*2),$Y,$XiniCuad+($AnchoNum*2),$Y+9); //Segundo
						$pdf->Line($XiniCuad+($AnchoNum*3),$Y,$XiniCuad+($AnchoNum*3),$Y+9); //Tercero
						$pdf->Line($XiniCuad+($AnchoNum*4),$Y,$XiniCuad+($AnchoNum*4),$Y+9); //Cuarto
						$pdf->Line($XiniCuad+($AnchoNum*5),$Y,$XiniCuad+($AnchoNum*5),$Y+9); //Quinto
						$pdf->Line($XiniCuad+($AnchoNum*6),$Y,$XiniCuad+($AnchoNum*6),$Y+9); //Sexto
						$pdf->Line($XiniCuad+($AnchoNum*7),$Y,$XiniCuad+($AnchoNum*7),$Y+9); //Septimo
						$pdf->Line($XiniCuad+($AnchoNum*8),$Y,$XiniCuad+($AnchoNum*8),$Y+9); //Octavo
						$pdf->Line($XiniCuad+($AnchoNum*9),$Y,$XiniCuad+($AnchoNum*9),$Y+9); //Noveno
						
						$Y += 2.5;
						//Neto
						$anchoCad = $pdf->GetStringWidth("Neto Gravado");
						$pdf->Text($XiniCuad+($AnchoNum/2)-($anchoCad/2),$Y,"Neto Gravado");
						//No gravado
						$anchoCad = $pdf->GetStringWidth("No Gravado");
						$pdf->Text($XiniCuad+$AnchoNum+($AnchoNum/2)-($anchoCad/2),$Y,"No Gravado");
						//IVA 2.5
						$anchoCad = $pdf->GetStringWidth("IVA 2,50%");
						$pdf->Text($XiniCuad+($AnchoNum*2)+($AnchoNum/2)-($anchoCad/2),$Y,"IVA 2,50%");
						//IVA 5
						$anchoCad = $pdf->GetStringWidth("IVA 5,00%");
						$pdf->Text($XiniCuad+($AnchoNum*3)+($AnchoNum/2)-($anchoCad/2),$Y,"IVA 5%");
						//IVA 10.5
						$anchoCad = $pdf->GetStringWidth("IVA 10,50%");
						$pdf->Text($XiniCuad+($AnchoNum*4)+($AnchoNum/2)-($anchoCad/2),$Y,"IVA 10,50%");
						//IVA 21
						$anchoCad = $pdf->GetStringWidth("IVA 21,00%");
						$pdf->Text($XiniCuad+($AnchoNum*5)+($AnchoNum/2)-($anchoCad/2),$Y,"IVA 21,00%");
						//IVA 27
						$anchoCad = $pdf->GetStringWidth("IVA 27,00%");
						$pdf->Text($XiniCuad+($AnchoNum*6)+($AnchoNum/2)-($anchoCad/2),$Y,"IVA 27,00%");
						//IVA Exento
						$anchoCad = $pdf->GetStringWidth("Exento");
						$pdf->Text($XiniCuad+($AnchoNum*7)+($AnchoNum/2)-($anchoCad/2),$Y,"Exento");
						//Percepción
						$anchoCad = $pdf->GetStringWidth($leyenda_percep);
						$pdf->Text($XiniCuad+($AnchoNum*8)+($AnchoNum/2)-($anchoCad/2),$Y,$leyenda_percep);
						
						//A PAGAR
						$anchoCad = $pdf->GetStringWidth("TOTAL");
						$pdf->Text($XfinCuad-2-$anchoCad,$Y,"TOTAL");
						
						$Y += 4.5;		
						//Neto
						$suma_netograv = number_format($suma_netograv,2,",",".");
						$anchoCad = $pdf->GetStringWidth($suma_netograv);
						$pdf->Text($XiniCuad+($AnchoNum/2)-($anchoCad/2),$Y,$suma_netograv);
						
						if ($sumIVA_nograv > 0)
						{
							$sumIVA_nograv = number_format($sumIVA_nograv,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_nograv);
							$pdf->Text($XiniCuad+$AnchoNum+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_nograv);
						}
						if ($sumIVA_25 > 0)
						{
							$sumIVA_25 = number_format($sumIVA_25,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_25);
							$pdf->Text($XiniCuad+($AnchoNum*2)+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_25);
						}
						if ($sumIVA_5 > 0)
						{
							$sumIVA_5 = number_format($sumIVA_5,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_5);
							$pdf->Text($XiniCuad+($AnchoNum*3)+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_5);
						}
						if ($sumIVA_105 > 0)
						{
							$sumIVA_105 = number_format($sumIVA_105,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_105);
							$pdf->Text($XiniCuad+($AnchoNum*4)+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_105);
						}
						if ($sumIVA_21 > 0)
						{
							$sumIVA_21 = number_format($sumIVA_21,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_21);
							$pdf->Text($XiniCuad+($AnchoNum*5)+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_21);
						}
						if ($sumIVA_27 > 0)
						{
							$sumIVA_27 = number_format($sumIVA_27,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_27);
							$pdf->Text($XiniCuad+($AnchoNum*6)+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_27);
						}
						if ($sumIVA_exento > 0)
						{
							$sumIVA_exento = number_format($sumIVA_exento,2,",",".");
							$anchoCad = $pdf->GetStringWidth($sumIVA_exento);
							$pdf->Text($XiniCuad+($AnchoNum*7)+($AnchoNum/2)-($anchoCad/2),$Y,$sumIVA_exento);
						}
						if ($suma_percepcion > 0)
						{
							$suma_percepcion = number_format($suma_percepcion,2,",",".");
							$anchoCad = $pdf->GetStringWidth($suma_percepcion);
							$pdf->Text($XiniCuad+($AnchoNum*8)+($AnchoNum/2)-($anchoCad/2),$Y,$suma_percepcion);
						}
				
						$pdf->SetFont('Arial','B',10);
						$Y += 0.5;
						$anchoCad = $pdf->GetStringWidth($suma_apagar);
						$pdf->Text($XfinCuad-2-$anchoCad,$Y,$suma_apagar);
						$Y += 6;
					}
					
					//En letras
					$cad = $this->numeros_a_letras($suma_apagar, $moneda);

					$cad = explode("\n",wordwrap($cad,70,"\n",1));
					
					$pdf->SetFont('Arial','B',intval($fuentes['en_letras']));
					
					for ($j=0;$j<count($cad);$j++)
					{
						if ($j == 0) {
							//$pdf->Text(25,$Y,"A pagar: ".$cad[$j]);
							if ($encabezado->letra == "A")
								$pdf->SetXY(100, 274);
							else
								$pdf->SetXY(100, 268);
							$pdf->MultiCell(102, 5, "A pagar: ".$cad[$j]);
						}
						else
							$pdf->Text(25,$Y,$cad[$j]);
						$Y +=4;
					}
					if ($cotizacion != 1)
					{
						$pdf->SetFont('Arial','B',7);
						$pdf->Text(25,$Y,"Tipo de cambio: $".number_format($cotizacion,4,",",".").".-");
					}
					if ($id_emp == 112) //Imprime el CAE al pie - Solamente para universidad CAECE
					{
						$Y += 6;
						$pdf->Line(15,$Y,205,$Y);
						$Y += 4;
						$pdf->SetFont('Arial','B',7);
						$pdf->Text(18,$Y,"Comprobante autorizado - C.A.E.: ".$encabezado->cae." - Vto. C.A.E.: ".$encabezado->fec_vto_cae);
						
						//Código de barras
						$codAux = "";
						$cod = $this->CodigoI2OF5($cadCodigo,$codAux);
						//$pdf->AddFont('PF-I2OF5','','i2of5NT.php');
						$pdf->SetFont('PF-I2OF5','',24);
						$pdf->Text(20,$Y-10,$cod);
						$anchoCod = $pdf->GetStringWidth($cod);
						$pdf->SetFont('Arial','',7);
						$anchoCodAux = $pdf->GetStringWidth($codAux);
						$pdf->Text(20+($anchoCod/2)-($anchoCodAux/2),$Y-6,$codAux);
					}
					else { //Para las empresas en general
						//Código de barras
						$codAux = "";
						$cod = $this->CodigoI2OF5($cadCodigo,$codAux);
						//$pdf->AddFont('PF-I2OF5','','i2of5NT.php');
						$pdf->SetFont('PF-I2OF5','',24);
						$pdf->Text(20,$Y+1,$cod);
						$anchoCod = $pdf->GetStringWidth($cod);
						$pdf->SetFont('Arial','',7);
						$anchoCodAux = $pdf->GetStringWidth($codAux);
						$pdf->Text(20+($anchoCod/2)-($anchoCodAux/2),$Y+3,$codAux);
					}
					
					$r = getenv("DOCUMENT_ROOT");
					$nom_arch = $idsDoc."_".$nroFac.".pdf";
					//$pdf->Output('F', storage_path().'/app/pdfs/'.$nom_arch);
					\Storage::disk('local')->put('/pdfs/'.$nom_arch, $pdf->Output('S'));
					$pdf->Close();
					
					if ($ret_nombres == "")
						$ret_nombres = $result[$m]->id;
					else
						$ret_nombres .= ",".$result[$m]->id;
					//Fin crear PDFs******************************************
				}
			}

			return $nom_arch;
		} catch (Exception $e) {
			throw new Exception("Error Processing Request ".$e->getMessage());
			
			return $e->getMessage();
		}
	}

	public function downloadPDF($file_name) {
		if (strpos($file_name, '.pdf') !== false) { //un único PDF
			return response()->download(storage_path().'/app/pdfs/'.$file_name, $file_name, ['Content-Type' => 'application/pdf']);
		}
		else { //descargar ZIP
			return response()->download(storage_path().'/app/pdfs/'.$file_name, $file_name, ['Content-Type' => 'application/octet-stream']);
		}
	}

	private function numeros_a_letras($numero, $cod_moneda)
	{
		$vUnidades = array("UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE", "DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE", "VEINTE", "VEINTIUN", "VEINTIDÓS", "VEINTITRÉS", "VEINTICUATRO", "VEINTICINCO", "VEINTISÉIS", "VEINTISIETE", "VEINTIOCHO", "VEINTINUEVE");
		$vDecenas = array("DIEZ", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA");
		$vCentenas = array("CIEN", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS");
		$TextoFinal = "";
		
		$partes = explode(",",$numero); //Separar en subindice 0 parte entera y subindice 1 parte decimal
		$partes[0] = str_replace(".","",$partes[0]); //sacar las comas
		$partes[0] = sprintf("%09s",$partes[0]);
		
		//MANEJO DE LOS MILLARES
		$num = substr($partes[0], 0, 1);
		if ($num != 0)
			$TextoFinal = $vCentenas[$num - 1];
		$num = substr($partes[0], 1, 1);
		if ($num > 0)
		{
			if (substr($TextoFinal,strlen($TextoFinal)-4, 4) == "CIEN")
				$TextoFinal .= "TO ";
			if ($num > 2)
			{
				$TextoFinal .= " ".$vDecenas[$num - 1]." Y";
				$num = substr($partes[0], 1, 2);
				if ($num != "00")
				{
					if ($num <= 10)
						$TextoFinal .= " ".$vUnidades[$num - 1];
					else
					{
						$num = substr($partes[0], 2, 1);
						if ($num == 0)
							$TextoFinal = substr($TextoFinal, 1, strlen($TextoFinal) - 2);
						else
							$TextoFinal .= " ".$vUnidades[$num - 1];
					 }
				 }
			}
			else
				$TextoFinal .= " ".$vUnidades[substr($partes[0], 1, 2) - 1];

		}
		else
		{
			$num = substr($partes[0], 2, 1);
			if ($num != 0)
			{
				if (trim($TextoFinal) == "CIEN")
					$TextoFinal .= "TO ";
				$TextoFinal .= $vUnidades[$num - 1];
			 }
		}
		if (substr($partes[0], 0, 3) != "000")
		{
			if (substr($partes[0], 0, 3) == "001")
				$TextoFinal .= " MILLON";
			else
				$TextoFinal .= " MILLONES";
			if (substr($partes[0], 3) == "000000")
					$TextoFinal .= " DE";
		}
		
		//MANEJO DE LAS CENTENAS
		$num = substr($partes[0], 3, 1);
		If ($num != 0)
			$TextoFinal .= " ".$vCentenas[$num - 1];
		$num = substr($partes[0], 4, 1);
		if ($num > 0)
		{
			if (substr($TextoFinal,strlen($TextoFinal)-4, 4) == "CIEN")
				$TextoFinal .= "TO";
			if ($num > 2)
			{
				$TextoFinal .= " ".$vDecenas[$num - 1]." Y";
				$num = substr($partes[0], 4, 2);
				if ($num != "00")
				{
					if ($num <= 10)
						$TextoFinal .= " ".$vUnidades[$num - 1];
					else
					{
						$num = substr($partes[0], 5, 1);
						if ($num == 0)
							$TextoFinal = substr($TextoFinal, 1, strlen($TextoFinal) - 2);
						else
							$TextoFinal .= " ".$vUnidades[$num - 1];
					}
				}
			}
			else
				$TextoFinal .= " ".$vUnidades[substr($partes[0], 4, 2) - 1];
		}
		else
		{
			$num = substr($partes[0], 5, 1);
			if ($num != 0)
			{
				if (trim($TextoFinal) == "CIEN")
					$TextoFinal .= "TO";
				$TextoFinal .= " ".$vUnidades[$num - 1];
			}
		}
		if (substr($partes[0], 3, 3) != "000")
			$TextoFinal .= " MIL";

		//MANEJO DE LAS UNIDADES
		$num = substr($partes[0], 6, 1);
		if ($num != 0)
		{
			if (($num == 1) && (substr($partes[0], 7, 2) != "00"))
				$TextoFinal .= " ".$vCentenas[$num - 1]."TO";
			else
				$TextoFinal .= " ".$vCentenas[$num - 1];
		}
		$num = substr($partes[0], 7, 1);
		if ($num > 0)
		{
			if ($num > 2)
			{
				$TextoFinal .= " ".$vDecenas[$num - 1]." Y";
				$num = substr($partes[0], 7, 2);
				if ($num != "00")
				{
					if ($num <= 10)
						$TextoFinal .= " ".$vUnidades[$num - 1];
					else
					{
						$num = substr($partes[0], 8, 1);
						if ($num == 0)
							$TextoFinal = substr($TextoFinal, 1, strlen($TextoFinal) - 2);
						else
							$TextoFinal .= " ".$vUnidades[$num - 1];
					}
				}
			}
			else
				$TextoFinal .= " ".$vUnidades[substr($partes[0], 7, 2) - 1];
		}
		else
		{
			$num = substr($partes[0], 8, 1);
			if ($num != 0)
				$TextoFinal .= " ".$vUnidades[$num - 1];
		}
		
		$moneda = " PESOS";

		$mon = new Monedas();

		$result = $mon->getEnLetras($cod_moneda);
		
		if ($result != null)
		{
			if (count($result) > 0)
			{
				$moneda = " ".$result[0];
			}
		}

		$TextoFinal .= $moneda;
		
		//MANEJO DE LOS DECIMALES
		$num = substr($partes[1], 0, 1);
		if ($num > 2)
		{
			$TextoFinal .= " CON ".$vDecenas[$num - 1];
			$num = substr($partes[1], 1, 1);
			if ($num != 0)
				$TextoFinal .= " Y ".$vUnidades[$num - 1];
			$TextoFinal .= " CENTAVOS";
		}
		else
		{
			$num = substr($partes[1], 0, 2);
			if ($num != "00")
				$TextoFinal .= " CON ".$vUnidades[$num - 1]." CENTAVOS";
		}
		
		return $TextoFinal."---";
	}
	//Fin función nros a letras******************************
	
	private function CodigoI2OF5($cad, &$cod_aux)
	{
		//Algoritmo para codigo interleaved 2 of 5
		$start = "(";//chr(40);
		$stop = ")"; //chr(41);
		$ret = trim($cad);
		$long = strlen($ret);
		$sum = 0;
		$cont = 1;
		for ($i=($long-1);$i>=0;$i--)
		{
			if (($cont % 2) == 0)
				$corrector = 1;
			else
				$corrector = 3;
			$sum += substr($ret,$i,1)*$corrector;
			$cont++;
		}
		$aux = $sum % 10;
		if ($aux == 0)
			$ret .= "0";
		else
			$ret .= 10-$aux;
		$long = strlen($ret);
		if (($long % 2) != 0)
		{
			$ret = "0".$ret;
			$long = strlen($ret);
		}
		$cadAux = "";
		for ($i=0;$i<$long;$i+=2)
		{
			if (substr($ret,$i,2)<50)
				$cadAux .= chr(substr($ret,$i,2)+48);
			else
				$cadAux .= chr(substr($ret,$i,2)+142);
		}
		$cod_aux = $ret;
		$ret = $start.$cadAux.$stop;
		return $ret;
	}
}