<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>Reconocimiento de titulo</title>
    <style>
        body { font-family: Roboto, Arial, sans-serif; }
        table { border-color: #000; border-collapse: collapse; font-family: Roboto, Arial, sans-serif; font-size: 12px; }
        .sombra { background-color: #ccc; text-align: left; }
        .sinborde { border: none; text-align: center; font-size: 10px; }
        h4 { text-align: center; }
    </style>
</head>
<body>
<section id="about">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <img src="{{ asset('img/upca-horiz.png') }}" height="40" alt="">
                <h4>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA. DEPARTAMENTO DE ADMISIONES, REGISTRO Y CONTROL ACAD&Eacute;MICO. FORMATO &Uacute;NICO DE CARTA DE AUTORIZACI&Oacute;N DE INGRESO CICLO PROFESIONAL / TRANSFERENCIA INTERNA</h4>

                <table border="1" style="width:100%;">
                    <tr><th class="sombra" style="width: 25%">Fecha de elaboraci&oacute;n</th><td style="width: 75%">{{ $fecha }}</td></tr>
                    <tr><th class="sombra">Periodo acad&eacute;mico</th><td>{{ $data['per'] }}</td></tr>
                    <tr><td class="sombra" colspan="2"><b>SE&Ntilde;ORES:<br>ADMISIONES, REGISTRO Y CONTROL ACAD&Eacute;MICO.<br>CORDIAL SALUDO;</b></td></tr>
                </table><br>

                <table border="1" style="width:100%;">
                    <tr>
                        <th class="sombra" style="width:25%;">{!! app(\App\Services\ReconocimientoTituloService::class)->tratamiento((int) $data['tra']) !!}</th>
                        <td style="width:75%;">{{ strtoupper($data['nom']) }}</td>
                    </tr>
                    <tr>
                        <th class="sombra">{!! app(\App\Services\ReconocimientoTituloService::class)->documento((int) $data['tdo']) !!}</th>
                        <td>{{ $data['num'] }}</td>
                    </tr>
                    <tr><th class="sombra">Semestre que cursa:</th><td><input type="text" class="sinborde" placeholder="Escriba semestre"></td></tr>
                    <tr><th class="sombra">De la carrera profesional:</th><td>{{ $programaPca->nombre }}</td></tr>
                </table><br>

                <table border="1" style="width:100%;border-collapse: collapse;">
                    <tr><td colspan="2">&nbsp;</td><th colspan="2">Marque la casilla</th></tr>
                    <tr class="sombra" style="text-align: center;">
                        <th style="width:25%;">INSTITUCI&Oacute;N DE PROCEDENCIA</th>
                        <th style="width:45%;">PROGRAMA DE PROCEDENCIA</th>
                        <th style="width:15%;">NUEVO PROFESIONAL</th>
                        <th style="width:15%;">TRANSFERENCIA INTERNA</th>
                    </tr>
                    <tr style="text-align: center;">
                        <td>{{ $institucion->nombre }}</td>
                        <td>{{ $programaExt->nombre }}</td>
                        <td><input type="checkbox" checked></td>
                        <td><input type="checkbox"></td>
                    </tr>
                </table><br>

                <table width="100%" border="0">
                    <tr><td><strong>Observaciones:</strong><br><textarea name="obs" style="border:none; width: 100%;" placeholder="No hay observaciones..." cols="60" rows="5"></textarea></td></tr>
                    <tr><th>*Esta casilla se utiliza en el caso de presentarse alguna aclaraci&oacute;n adicional, con respecto al ingreso o transferencia del estudiante.</th></tr>
                    <tr>
                        <td>
                            <b>Cordialmente,</b><br>
                            @if($firma)
                                <img src="{{ asset(ltrim($firma, './')) }}" width="150px"><br>
                            @endif
                            <b>Firma del director de programa</b>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
</body>
</html>
