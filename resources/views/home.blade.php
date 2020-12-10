@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4 text-center mb-2">
            <h3><u>Descarga de Comprobantes</u></h3>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <form>
                <div class="form-group row justify-content-center">
                    <label for="periodos" class="col-md-2 col-form-label text-md-right">Periodos</label>
                    <div class="col-md-4">
                        <select id="periodos" class="form-control form-control-sm">
                            <option value="0">Seleccione periodo</option>
                            @if($periodos != null)
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->periodo }}">{{ $periodo->periodo }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary btn-sm text-md-left" id="btn-buscar">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped table-sm" id="tab-comprobantes">
                    <thead class="bg-primary text-light text-center">
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Letra</th>
                        <th class="text-center">Comprobante</th>
                        <th class="text-center"><input type="checkbox" name="top-selec" id="top-selec"></th>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-3">
            <button type="button" class="btn btn-primary btn-sm btn-block" id="btn-exportar"><i class="fa fa-file-o"></i>  Exportar a PDF</button>
        </div>
    </div>
</div>
@endsection

@include('doc_info')

<script type="text/javascript">
    window.addEventListener ('load', function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#btn-buscar").click(function() {
            $("#tab-comprobantes tbody").empty();

            if ($("#periodos").val() == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text:'Seleccione un periodo válido.'
                });
                
                return;
            }

            var periodo = $("#periodos").val();

            $.ajax({
               type:'POST',
               url:"{{ route('ajaxRequest.post') }}",
               data:{anio: periodo}
            })
            .done(function(data) {
                var i = 0;

                for (i=0;i<data.length;i++) {
                    var fila = '<tr><td class="text-center">' + data[i]['fecha'] + '</td><td class="text-center">' + data[i]['tipo'] + '</td><td class="text-center">' + data[i]['letra'] + '</td><td class="text-center">' + data[i]['nro_doc'] + ' <a href="#" class="comp-info" data-id="' + data[i]['id'] + '"><i class="fa fa-eye"></i></a></td><td class="text-center"><input type="checkbox" class="comp-selec" name="comp-selec[]" id="' + data[i]['id'] + '"></td></tr>';

                    $("#tab-comprobantes tbody").append(fila);
                }
            });
        });

        $("#top-selec").change(function() {
            var checkboxes = $(this).closest('#tab-comprobantes').find(':checkbox');
            checkboxes.prop('checked', $(this).is(':checked'));
        });

        $('#btn-exportar').click(function() {
            var ids = [];
            $.each($("input[name='comp-selec[]']:checked"), function() {
                ids.push($(this).attr('id'));
            });

            if (ids.length == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text:'No se seleccionó nungún comprobante.'
                });
                
                return;
            }

            $("#btn-exportar").attr('disabled', true);
            $("#btn-exportar").html("<span class='spinner-border spinner-border-sm' role='status' aria-hidden='true'></span> Procesando...");

            $.ajax({
               type:'POST',
               url:"{{ route('pdfController.generator') }}",
               data:{id_doc: ids}
            })
            .done(function(data) {
                if(!data['error']) {

                    $("#btn-exportar").attr('disabled', false);
                    $("#btn-exportar").html('<i class="fa fa-file-o"></i> Exportar a PDF');
                    
                    location.href = "pdfControllerDownload/"+data['data'][0];
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text:'Se ha producido un error.'
                    });
                }
            });
        });

        $('body').delegate('.comp-info', 'click', function() {
            $("#tab-detalles tbody").empty();

            var _id_factura = $(this).data('id');

            $.ajax({
               type:'POST',
               url:"{{ route('ajaxRequest.detalles') }}",
               data:{id_factura: _id_factura}
            })
            .done(function(data) {
                var i = 0;

                for (i=0;i<data.length;i++) {
                    var fila = '<tr><td>' + data[i]['descrip'] + '</td><td class="text-right">' + parseFloat(data[i]['prec_unit']).toFixed(2) + '</td><td class="text-right">' + parseFloat(data[i]['imp_nogravado']).toFixed(2) + '</td><td class="text-right">' + parseFloat(data[i]['imp_exento']).toFixed(2) + '</td><td class="text-right">' + parseFloat(data[i]['total_siva']).toFixed(2) + '</td><td class="text-right">' + parseFloat(data[i]['iva']).toFixed(2) + '</td><td class="text-right">' + parseFloat(data[i]['total_civa']).toFixed(2) + '</td></tr>';

                    $("#tab-detalles tbody").append(fila);
                }

                $("#infoModal").modal('show');
            });
        });
    })
</script>
