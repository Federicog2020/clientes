<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Factura;
use App\ServEmpFac;

use App\Http\Controllers\PdfController;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $fac = new Factura();
        $periodos = $fac->getPeriodos();

        /*$pdf = new PdfController();
        $file_pdf_name = $pdf->generarPDF('571071');*/

        //$pdf->downloadPDF($file_pdf_name);

        return view('home', array('periodos' => $periodos));
    }
}
