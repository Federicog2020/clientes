<?php
session_start();
if (!isset($_SESSION["id_emp"]))
{
	echo '<script language="JavaScript">location.href="../index.html";</script>';
}
else
{
	define('FPDF_FONTPATH','../../lib/font/');
	require("../../lib/fpdf.php");
	
	/*class PDF extends FPDF
	{
	//Cabecera de página
	function Header()
	{
		//Arial bold 15
		$this->SetFont('Arial','B',8);
		//Movernos a la derecha
		$this->Text(20,20,$usuario);
		//Título
		$this->Text(20,24,date("d/m/Y"));
		//Salto de línea
		//$this->Ln(20);
		echo "Cabecera";
	}
	}*/
	
	function ImprimirEncabezado($pdfEnc, $usuario, $pagina, $posX, $ancho, $col, $periodo)
	{
		$anchoImprimible = $ancho[1] + $ancho[2] + $ancho[3] + $ancho[4] + $ancho[5] + $ancho[6] + $ancho[7] + $ancho[8] + $ancho[9] + $ancho["Espacio"]*8;
		$pdfEnc->SetFont('Arial','B',8);
		//Movernos a la derecha
		$pdfEnc->Text(20,20,$usuario);
		$pdfEnc->Text(297 - 15 - $pdfEnc->GetStringWidth("Página: 0000"),20,"Página: ".$pagina);
		$pdfEnc->Text(20,24,date("d/m/Y"));
		//Título
		$pdfEnc->SetFont('Arial','BU',12);
		$pdfEnc->Text($posX + ($anchoImprimible/2) - ($pdfEnc->GetStringWidth("Listado de Ventas")/2),30,"Listado de Ventas");
		$pdfEnc->SetFont('Arial','',10);
		if ($periodo == 0)
			$pdfEnc->Text($posX + ($anchoImprimible/2) - ($pdfEnc->GetStringWidth("Periodo: 00/0000")/2),35,"Periodo: Todos");
		else
			$pdfEnc->Text($posX + ($anchoImprimible/2) - ($pdfEnc->GetStringWidth("Periodo: 00/0000")/2),35,"Periodo: ".$periodo);
		
		//Encabezado de lista
		$posY = 44;
		$pdfEnc->Line($posX,40,$posX + $anchoImprimible,40);
		$pdfEnc->Text($col[1] + 5,$posY,"Razón Social");
		$pdfEnc->Text($col[2],$posY,"Sucursal");
		$pdfEnc->Text($col[3] + ($ancho[3]/2) - ($pdfEnc->GetStringWidth("CUIT")/2),$posY,"CUIT");
		$pdfEnc->Text($col[4],$posY,"Tipo");
		$pdfEnc->Text($col[5] + ($ancho[5]/2) - ($pdfEnc->GetStringWidth("Fecha")/2),$posY,"Fecha");
		$pdfEnc->Text($col[6] + ($ancho[6]/2) - ($pdfEnc->GetStringWidth("Comprobante")/2),$posY,"Comprobante");
		$pdfEnc->Text($col[7] + $ancho[7] - $pdfEnc->GetStringWidth("Total S/IVA"),$posY,"Total S/IVA");
		$pdfEnc->Text($col[8] + $ancho[8] - $pdfEnc->GetStringWidth("Total IVA"),$posY,"Total IVA");
		$pdfEnc->Text($col[9] + $ancho[9] - $pdfEnc->GetStringWidth("Total C/IVA"),$posY,"Total C/IVA");
		$pdfEnc->Line($posX,46,$posX + $anchoImprimible,46);
	}

	include_once("../../conec.php");
	$id_emp = $_SESSION["id_emp"];
	$usuario = $_SESSION["usuario"];
	$periodo = $_POST["periodo"];
	$consulta = stripslashes(stripslashes($_POST["consulta"]));
	Conectar();
	//$consulta = "SELECT f.id, f.nro_fac, f.pto_venta, f.letra, f.fec_fac, f.tipo, f.cae, SUM(d.total_siva) as total_siva, SUM(d.iva) as total_iva, SUM(d.total_civa) as total, c.razon, c.cuit, c.sucursal, comp.abrev FROM serv_facturas f, serv_emp_fac d, clientes c, comprobantes comp WHERE f.cod_emp='2' AND f.cae<>'' AND d.id_factura=f.id AND c.id=f.cod_clie AND comp.id=f.cod_tipo AND ((f.fec_alta>=f.fec_baja) OR (f.fec_baja IS NULL)) GROUP BY f.id ORDER BY f.cod_tipo, f.fec_fac, f.nro_fac";//$_POST["consulta"];

	$result = mysql_query($consulta);
	mysql_close();
	$rows = mysql_num_rows($result);
	if ($rows > 0)
	{
		$pdf= new FPDF('L','mm','A4');
		$pdf->AliasNbPages();
		$pdf->SetLeftMargin(20);
		$pdf->SetRightMargin(10);
		$pdf->SetTopMargin(10);
		$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$posX = 20;
		$posY = 50;
		$altoImprimible = 279 - 10 - $posY;
		
		$ancho[1] = $pdf->GetStringWidth("MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM");
		$ancho[2] = $pdf->GetStringWidth("MMMMMMMMMM");
		$ancho[3] = $pdf->GetStringWidth("00000000000");
		$ancho[4] = $pdf->GetStringWidth("MMMM");
		$ancho[5] = $pdf->GetStringWidth("99/99/9999");
		$ancho[6] = $pdf->GetStringWidth("MM9999-99999999");
		$ancho[7] = $pdf->GetStringWidth("99.999.999.99");
		$ancho[8] = $pdf->GetStringWidth("99.999.999.99");
		$ancho[9] = $pdf->GetStringWidth("99.999.999.99");
		$ancho["Espacio"] = $pdf->GetStringWidth("M");
		
		$anchoImprimible = $ancho[1] + $ancho[2] + $ancho[3] + $ancho[4] + $ancho[5] + $ancho[6] + $ancho[7] + $ancho[8] + $ancho[9] + $ancho["Espacio"]*8;
		
		$col[1] = $posX;
		$col[2] = $col[1] + $ancho[1] + $ancho["Espacio"];
		$col[3] = $col[2] + $ancho[2] + $ancho["Espacio"];
		$col[4] = $col[3] + $ancho[3] + $ancho["Espacio"];
		$col[5] = $col[4] + $ancho[4] + $ancho["Espacio"];
		$col[6] = $col[5] + $ancho[5] + $ancho["Espacio"];
		$col[7] = $col[6] + $ancho[6] + $ancho["Espacio"];
		$col[8] = $col[7] + $ancho[7] + $ancho["Espacio"];
		$col[9] = $col[8] + $ancho[8] + $ancho["Espacio"];
		
		$sum_total_siva = 0;
		$sum_total_iva = 0;
		$sum_total_civa = 0;
		$contador = 0;
		
		$pagina = 1;
		
		ImprimirEncabezado($pdf, $usuario, $pagina, $posX, $ancho, $col, $periodo);
	}
	for ($i=0;$i<$rows;$i++)
	{
		$idFac = mysql_result($result,$i,"id");
		$razon = mysql_result($result,$i,"razon");
		$sucursal = mysql_result($result,$i,"sucursal");
		$cuit = mysql_result($result,$i,"cuit");
		$tipo = mysql_result($result,$i,"abrev");
		$fecha = date("d/m/Y",strtotime(mysql_result($result,$i,"fec_fac")));
		$total_siva = number_format(mysql_result($result,$i,"total_siva"),2,",",".");
		$total_iva = number_format(mysql_result($result,$i,"total_iva"),2,",",".");
		$total = number_format(mysql_result($result,$i,"total"),2,",",".");
		$sum_total_siva += mysql_result($result,$i,"total_siva");
		$sum_total_iva += mysql_result($result,$i,"total_iva");
		$sum_total_civa += mysql_result($result,$i,"total");
		$pto_venta = mysql_result($result,$i,"pto_venta");
		$nro_fac = mysql_result($result,$i,"nro_fac");
		$letra = mysql_result($result,$i,"letra");
		//$pagina = 1;
		//$paginas_id = "";
		//$pdf->AddPage();
		//$num_pag++;
		
		if ($posY >= $altoImprimible)
		{
			$posY = 50;
			$pdf->AddPage();
			$pagina++;
			ImprimirEncabezado($pdf, $usuario, $pagina, $posX, $ancho, $col, $periodo);
		}
		
		$pdf->SetFont('Arial','',8);
		//Razón Social
		if (strlen($razon) > 50)
			$razon = substr($razon,0,50);
		$pdf->Text($col[1],$posY,$razon);
		
		//Sucursal
		$pdf->Text($col[2],$posY,$sucursal);
		
		//CUIT
		$pdf->Text($col[3],$posY,$cuit);
		
		//Tipo
		$pdf->Text($col[4],$posY,$tipo);
		
		//Fecha
		$pdf->Text($col[5],$posY,$fecha);
		
		//Comprobante
		$pdf->Text($col[6],$posY,$letra." ".sprintf("%04s",$pto_venta)."-".sprintf("%08s",$nro_fac));
		
		//Total Sin IVA
		$pdf->Text($col[7] + $ancho[7] - $pdf->GetStringWidth($total_siva),$posY,$total_siva);
		
		//Total IVA
		$pdf->Text($col[8] + $ancho[8] - $pdf->GetStringWidth($total_iva),$posY,$total_iva);
		
		//Total C/IVA
		$pdf->Text($col[9] + $ancho[9] - $pdf->GetStringWidth($total),$posY,$total);
		
		$posY += 4;
		
		$contador++;
	}
	mysql_free_result($result);
	
	//Imprimir el pie de informe - cantidad y totales
	$posY -= 2;
	$pdf->Line($posX,$posY,$posX + $anchoImprimible,$posY);
	$posY += 4;
	$pdf->Text($posX + 5, $posY, "Cantidad:");
	$pdf->Text($col[5] + $ancho[5] - $pdf->GetStringWidth("Totales"), $posY, "Totales");
	$pdf->SetFont('Arial','B',8);
	$pdf->Text($posX + 5 + $pdf->GetStringWidth("Cantidad: "), $posY, $contador);
	$pdf->Text($col[7] + $ancho[7] - $pdf->GetStringWidth(number_format($sum_total_siva,2,",",".")), $posY, number_format($sum_total_siva,2,",","."));
	$pdf->Text($col[8] + $ancho[8] - $pdf->GetStringWidth(number_format($sum_total_iva,2,",",".")), $posY, number_format($sum_total_iva,2,",","."));
	$pdf->Text($col[9] + $ancho[9] - $pdf->GetStringWidth(number_format($sum_total_civa,2,",",".")), $posY, number_format($sum_total_civa,2,",","."));
	
	$pdf->Output('pdfs/'.$id_emp.'listado.pdf');
	//$pdf->Output();
	echo '<script language="JavaScript">location.href="pdfs/'.$id_emp.'listado.pdf";</script>';
}
?>
