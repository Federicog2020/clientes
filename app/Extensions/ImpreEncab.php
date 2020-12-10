<?php
namespace App\Extensions;

class ImpreEncab
{
	var $letra;
	var $cod_tipo;
	var $nro_fac;
	var $pto_venta;
	var $nro_doc;
	var $fec_fac;
	var $cae;
	var $fec_vto_cae;
	var $condVenta;
	var $formaPago;
	var $fec_vto;
	var $nroOC;
	var $razonEmp;
	var $cuitEmp;
	var $ing_br;
	var $fec_inicio;
	var $calle;
	var $nro;
	var $piso;
	var $depto;
	var $sector;
	var $torre;
	var $manzana;
	var $provi;
	var $cp;
	var $localidad;
	var $tel;
	var $web;
	var $email;
	var $cond_iva;
	var $CLrazon;
	var $CLcalle;
	var $CLnro;
	var $CLpiso;
	var $CLdepto;
	var $CLsector;
	var $CLtorre;
	var $CLmanzana;
	var $CLprefijo;
	var $CLcp;
	var $CLsufijo;
	var $CLlocalidad;
	var $CLprovincia;
	var $CLcond_iva;
	var $CLcuit;
	var $tipo_doc;
	var $direccion;
	var $locali;
	var $CLdireccion;
	var $CLcodpos;
	var $num_pag;
	var $logo_path;
	var $print_public;
	var $fuentes;
	var $leyenda_fac;
	var $otros;
	var $domiSucursal;
	var $print_alicu_iva;
	var $proforma;
	var $cbu_informada;
	var $opcionales_fce;
	var $documentos_asociados;
	var $fecha_desde;
	var $fecha_hasta;
	var $fecha_vto;
	
	function ImpreEncab()
	{
		$this->letra = "";
	}
	
	function Imprimir(&$pdf, &$X, &$Y, &$Xini)
	{
		$pdf->AddPage();
		$this->num_pag++;
		//rectangulo de encuadre
		//Superior
		$pdf->Line(15,7,205,7);
		//Izquierdo
		$pdf->Line(15,7,15,287);
		//Inferior
		$pdf->Line(15,287,205,287);
		//Derecho
		$pdf->Line(205,7,205,287);
		
		if ($this->print_public == "S")
		{
			$pdf->SetFont('Arial','',6);
			$pdf->Text(15,289,"Sistema de facturación electrónica desarrollado por Estudio López Freyre. www.estudiolopezfreyre.com.ar");
		}
		
		//Logo
		//$pdf->Rect(20,10,80,30);
		if ($this->logo_path != "")
			$pdf->Image($this->logo_path,20,10);
		
		//Proforma
		$pdf->SetFont('Arial','B',20);
		$pdf->Text(120,15,utf8_decode($this->proforma));
		
		//Letra
		$pdf->SetFont('Arial','B',intval($this->fuentes['letra']));
		$anchoCad = $pdf->GetStringWidth($this->letra);
		$pdf->SetXY(110 - ($anchoCad/2),7);
		$pdf->Cell(8,8,$this->letra,1,0,"C");
		
		//Código de comprobante
		$pdf->SetFont('Arial','',intval($this->fuentes['codigo']));
		$anchoCad = $pdf->GetStringWidth("Código 00");
		$pdf->SetXY(110 - ($anchoCad/2),15);
		$pdf->Cell(11,4,'Código '.sprintf("%02s",$this->cod_tipo));
		
		//Nro de página
		$pdf->SetFont('Arial','',6);
		$anchoCad = $pdf->GetStringWidth("Página 0000");
		//$pdf->Text(200 - $anchoCad,10,'Página '.$pdf->PageNo()." de {nb}");
		$pdf->Text(200 - $anchoCad,10,'Página '.$this->num_pag);
		
		//Tipo de comprobante
		$pdf->SetFont('Arial','B',intval($this->fuentes['tipo_doc']));
		//$anchoCad = $pdf->GetStringWidth("Nº 0000-00000000");
		if (strlen($this->tipo_doc) > 11) {
		    $anchoCad = $pdf->GetStringWidth($this->tipo_doc);
		}
		else {
		    $anchoCad = $pdf->GetStringWidth("MMMMMMMMMMM");
		}

		$pdf->SetXY(200-$anchoCad,22);
		$pdf->Cell(26,5,$this->tipo_doc);

		//Nro comprobante
		$pdf->SetFont('Arial','B',intval($this->fuentes['nro_doc']));
		$pdf->SetXY(200-$anchoCad,26);
		$pdf->Cell(26,5,"Nº ".$this->nro_doc);
		//Fecha del comprobante
		$pdf->SetFont('Arial','',intval($this->fuentes['fec_doc']));
		$pdf->SetXY(200-$anchoCad,30);
		$pdf->Cell(26,5,"Fecha: ".$this->fec_fac);
		//CBU informada
		$pdf->SetFont('Arial','',8);
		$pdf->SetXY(200-$anchoCad,34);
		$pdf->Cell(26,5,utf8_decode($this->cbu_informada));
		//Cuit empresa
		$pdf->SetFont('Arial','',intval($this->fuentes['cuit']));
		$anchoCad = $pdf->GetStringWidth("Inicio de actividades: 00/00/0000MMMMMMMMMMMMMMM");
		$pdf->SetXY(200-$anchoCad,45);
		$pdf->Cell(43,5,"C.U.I.T. Nº ".$this->cuitEmp);
		//Ingresos brutos
		$pdf->SetFont('Arial','',intval($this->fuentes['ingre_bruto']));
		$anchoCad = $pdf->GetStringWidth("Inicio de actividades: 00/00/0000MMMMMMMMMMMMMMM");
		$pdf->SetXY(200-$anchoCad,49);
		$pdf->Cell(43,5,"Ingresos brutos: ".$this->ing_br);
		//Inicio de actividades
		$pdf->SetFont('Arial','',intval($this->fuentes['fec_ini']));
		$anchoCad = $pdf->GetStringWidth("Inicio de actividades: 00/00/0000MMMMMMMMMMMMMMM");
		$pdf->SetXY(200-$anchoCad,53);
		$pdf->Cell(43,5,"Inicio de actividades: ".$this->fec_inicio);

		//Otros
		if ($this->otros != "")
		{
			$cadOtros = split("\n",wordwrap($this->otros,50,"\n",1));
			$AuxOtros = 61;
			$pdf->SetFont('Arial','',8);
			$anchoCad = $pdf->GetStringWidth("Inicio de actividades: 00/00/0000MMMMMMMMMMMMMMM");
			//$cadOtros = explode("\r\n",$this->otros);
			for ($iOtros=0; $iOtros < count($cadOtros); $iOtros++)
			{
				//$pdf->SetXY(200-$anchoCad,$AuxY);
				$pdf->Text(200-$anchoCad,$AuxOtros,$cadOtros[$iOtros]);
				$AuxOtros += 3.5;
			}
		}
		//Razón social
		$Y = 45;
		$pdf->SetFont('Arial','',intval($this->fuentes['razon']));
		$pdf->Text(20,$Y,$this->razonEmp);
		$Y += 4;
		//Dirección
		$pdf->SetFont('Arial','',intval($this->fuentes['domi']));
		$pdf->Text(20,$Y,$this->direccion);
		$Y += 4;
		//cp y localidad
		$pdf->Text(20,$Y,$this->locali);
		$Y += 4;
		//provincia
		if ($this->cp < 1600)
		{
			if (!eregi("ciudad aut",$this->provi))
			{
				$pdf->Text(20,$Y,$this->provi);
				$Y += 4;
			}
		}
		else
		{
			$pdf->Text(20,$Y,$this->provi);
			$Y += 4;
		}
		//Teléfono
		$pdf->SetFont('Arial','',intval($this->fuentes['tel']));
		$pdf->Text(20,$Y,$this->tel);
		$Y += 4;
		//Domicilio comercial
		if ($this->domiSucursal != "")
		{
			$pdf->SetFont('Arial','',intval($this->fuentes['domi']));
			$pdf->Text(20,$Y,"Domicilio comercial: ".$this->domiSucursal);
			$Y += 4;
		}
		//Mail
		if ($this->email != "")
		{
			$pdf->SetFont('Arial','',intval($this->fuentes['mail']));
			$pdf->Text(20,$Y,"E-mail: ".$this->email);
			$Y += 4;
		}
		//Web
		if ($this->web != "")
		{
			$pdf->SetFont('Arial','',intval($this->fuentes['web']));
			$pdf->Text(20,$Y,"Web: ".$this->web);
			$Y += 4;
		}
		//Condición de IVA
		$Y += 4;
		$pdf->SetFont('Arial','',intval($this->fuentes['cond_iva']));
		$pdf->Text(20,$Y,$this->cond_iva);
		$Y += 4;
		
		//Linea
		$pdf->SetFont('Arial','',8);
		$pdf->Line(20,$Y,200,$Y);
		$Y +=4;
		//CAE
		$anchoCad = $pdf->GetStringWidth("Comprobante Autorizado - C.A.E.: 99999999999999 - Vto. C.A.E.: 99/99/9999MMMM");
		$pdf->Text(200-$anchoCad,$Y,"Comprobante Autorizado - C.A.E.: ".$this->cae." - Vto. C.A.E.: ".$this->fec_vto_cae);
		$Y +=2;
		//Linea
		$pdf->Line(20,$Y,200,$Y);
		$Y +=4;
		//CBU FCE
		if ($this->opcionales_fce != "S") {
		    $pdf->Text(25,$Y,"CBU DEL EMISOR: ".$this->opcionales_fce);
		    $Y +=2;
		    //Linea
		    $pdf->Line(20,$Y,200,$Y);
		    $Y +=4;
		}
		
		//Datos de cond de venta, forma de pago, Nro. OC
		//Datos de texto
		$datosSalida = "";
		if ($this->condVenta != 0)
			$datosSalida = "Cond. venta: ".$this->condVenta." días        ";
		if ($this->formaPago != "")
			$datosSalida .= "Forma de pago: ".$this->formaPago."        ";
		if ($this->nroOC != "")
			$datosSalida .= "Nro ord. compra: ".$this->nroOC."        ";
		if (isset($this->fuentes['fec_venci'])) {
			if ($this->fuentes['fec_venci'] == 1) {
				if (($this->fec_vto != "") && ($this->fec_vto != NULL)) {
					$datosSalida .= " - Vencimiento: ".$this->fec_vto;
				}
			}
		}
		elseif (($this->fec_vto != "") && ($this->fec_vto != NULL)) {
		  $datosSalida .= "Vencimiento: ".$this->fec_vto;
		}
		
		if ($datosSalida != "")
		{
			//Línea
			$pdf->Text(25,$Y,$datosSalida);
			$Y +=2;
			$pdf->Line(20,$Y,200,$Y);
			$Y +=4;
		}
		
		//Datos del cliente
		//Razón Social
		$pdf->SetFont('Arial','',intval($this->fuentes['razon_clie']));
		$pdf->Text(25,$Y,"Razón Social: ");
		$pdf->SetFont('Arial','B',intval($this->fuentes['razon_clie']));
		$pdf->Text(25+$pdf->GetStringWidth("Razón Social: "),$Y,$this->CLrazon);
		$Y += 4;
		//Dirección
		$pdf->SetFont('Arial','',intval($this->fuentes['domi_clie']));
		$pdf->Text(25,$Y,"Dirección: ".$this->CLdireccion);
		$Y += 4;
		//CP - localidad - provincia
		if ($this->CLcp < 1600)
		{
			if (eregi("Ciudad de",$this->CLlocalidad) || eregi("Ciudad Aut",$this->CLlocalidad) || eregi("capital fed",$this->CLlocalidad))
			{
				$pdf->Text(25,$Y,"C.P.: ".$this->CLcodpos." - ".$this->CLlocalidad);
			}
			else
			{
				if (eregi("CIUDAD AUTO",$this->CLprovincia))
					$pdf->Text(25,$Y,"C.P.: ".$this->CLcodpos." - Localidad: ".$this->CLlocalidad." - ".$this->CLprovincia);
				else
					$pdf->Text(25,$Y,"C.P.: ".$this->CLcodpos." - Localidad: ".$this->CLlocalidad." - ".$this->CLprovincia);
			}
		}
		else
		{
			$pdf->Text(25,$Y,"C.P.: ".$this->CLcodpos." - Localidad: ".$this->CLlocalidad." - Provincia: ".$this->CLprovincia);
		}
		$Y += 4;
		//Cond Iva - cuit
		$pdf->SetFont('Arial','',intval($this->fuentes['cond_ivaclie']));
		$pdf->Text(25,$Y,"Cond. I.V.A.: ".$this->CLcond_iva." - C.U.I.T.: ".$this->CLcuit);
		$Y += 4;
		if ($this->letra== "B")
		{
			//Leyenda fac
			$Y +=2;
			$pdf->SetFont('Arial','B',8);
			$pdf->Text(25,$Y,$this->leyenda_fac);
			$Y += 4;
		}
		//Linea
		$pdf->Line(20,$Y,200,$Y);
		$Y +=2;
		
		//Detalles
		
		$pdf->SetFont('Arial','',8);
		//Código
		$X = 30;
		$Y += 2;
		$pdf->Text($X,$Y,"Cód.");
		//Descripción
		$X = $X + $pdf->GetStringWidth("0000   ");
		$Xini = $X;
		$pdf->Text($X,$Y,"Descripción");
		//Total
		$anchoImporte = $pdf->GetStringWidth("  0.000.000.000,00");
		$X = 195;
		$anchoCad = $pdf->GetStringWidth("Total");
		$pdf->Text($X-$anchoCad,$Y,"Total");
		//Precio unitario
		$X -= $anchoImporte;
		$anchoCad = $pdf->GetStringWidth("Prec. Unit.");
		$pdf->Text($X-$anchoCad,$Y,"Prec. Unit.");
		//Cantidad
		if ($this->print_alicu_iva)
			$X -= ($anchoImporte + $pdf->GetStringWidth("(00,00)"));
		else
			$X -= $anchoImporte;
		$anchoCad = $pdf->GetStringWidth("Cantidad");
		$pdf->Text($X-$anchoCad,$Y,"Cantidad");
		$Y +=1;
		//Linea
		$pdf->Line(20,$Y,200,$Y);
		$Y +=5;
	}
}

function CodigoI2OF5($cad,&$cod_aux)
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
?>