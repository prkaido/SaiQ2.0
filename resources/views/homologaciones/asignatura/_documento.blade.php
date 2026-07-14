@php
    $esCorreccion = !empty($esCorreccion);
@endphp

<table border="0">
    <tr><td rowspan="3" colspan="4"><img src="{{ asset('img/upca-horiz.png') }}" width="200" alt=""></td><th colspan="6">ADMISIONES, CONTROL Y REGISTRO ACADEMICO</th></tr>
    <tr><th colspan="6">FORMATO 12{{ $esCorreccion ? ' - CORRECCION' : '' }}</th></tr>
    <tr><td colspan="6">&nbsp;</td></tr>
</table>

<table border="1">
    @if($esCorreccion)
        <tr><th class="sombra" colspan="10">FORMATO DE CORRECCION</th></tr>
    @endif
    <tr><th class="sombra" width="10%">PROGRAMA DE:</th><td colspan="9">{{ $programaPca->nombre }}{{ !empty($esCicloUniversitario) ? ' - CICLO UNIVERSITARIO' : '' }}</td></tr>
    <tr><th class="sombra" width="10%">PERIODO:</th><td colspan="9">{{ $data['per'] }}</td></tr>
    <tr>
        <td colspan="10">
            <table border="0" width="100%">
                <tr>
                    <th width="33%"><label><input type="radio" @checked((int) $data['tip'] === 0)> EXTERNA</label></th>
                    <th width="34%"><label><input type="radio" @checked((int) $data['tip'] === 1)> INTERNA</label></th>
                    <th width="33%"><label><input type="radio" @checked((int) $data['tip'] === 2)> REINTEGRO</label></th>
                </tr>
            </table>
        </td>
    </tr>
    <tr class="sombra"><th colspan="8">Apellidos y nombres del estudiante:</th><th colspan="2">No.</th></tr>
    <tr><td colspan="8">{{ strtoupper($data['ape'] . ' ' . $data['nom']) }}</td><td colspan="2">{{ $data['ide'] }}</td></tr>
    <tr class="sombra"><th colspan="10">Programa de procedencia:</th></tr>
    <tr><td colspan="10">{{ \App\Support\AcademicText::upper($data['pex']) }}</td></tr>
    <tr class="sombra"><th colspan="10">Programa a ingresar:</th></tr>
    <tr><td colspan="10">{{ $programaPca->nombre }}</td></tr>
    <tr class="sombra"><th colspan="5">Plan actual</th><th colspan="5">Plan nuevo</th></tr>
    <tr><td colspan="5">N/A</td><td colspan="5">{{ $plan->num }}</td></tr>
    <tr class="sombra"><th colspan="5">Semestre a cursar:</th><td colspan="5"><input type="text" class="sinborde" name="semestre" value="{{ $data['semestre'] ?? '' }}" size="15"></td></tr>
    <tr class="sombra"><th colspan="10">ASIGNATURAS A HOMOLOGAR</th></tr>
    <tr><th colspan="5">{{ $institucion->nombre }}</th><th colspan="5">CORPORACION UNIVERSITARIA POLITECNICO COSTA ATLANTICA</th></tr>
    <tr class="sombra">
        <th colspan="4" width="40%">ASIGNATURA</th>
        <th width="10%">SEMESTRE</th>
        <th width="10%">CODIGO</th>
        <th width="30%" colspan="3">ASIGNATURA</th>
        <th width="10%">NOTA</th>
    </tr>
    @foreach($asignaturasAprobadas as $asignatura)
        @php
            $nota = $approved[$asignatura->id] ?? 0;
            $origen = trim($originNames[$asignatura->id] ?? '');
            if ($origen === '') {
                $origen = $asignatura->nombre;
            }
        @endphp
        <tr>
            <td colspan="4">{{ strtoupper($origen) }}</td>
            <td>{{ $asignatura->romano ?? 'N/A' }}</td>
            <td>{{ $asignatura->cod }}</td>
            <td colspan="3">{{ $asignatura->nombre }}</td>
            <td><input type="text" class="sinborde" size="3" value="{{ round($nota, 1) }}"></td>
        </tr>
    @endforeach
    <tr class="sombra"><th colspan="10">ASIGNATURAS PENDIENTES</th></tr>
    <tr class="sombra"><th>CODIGO</th><th colspan="4">SEMESTRE</th><th colspan="5">NOMBRE</th></tr>
    @foreach($asignaturasPendientes as $asignatura)
        <tr>
            <td>{{ $asignatura->cod }}</td>
            <td colspan="4">{{ $asignatura->romano ?? 'N/A' }}</td>
            <td colspan="5">{{ $asignatura->nombre }}</td>
        </tr>
    @endforeach
</table>

<table border="0">
    <tr><th colspan="10">* REINTEGRO solo cambio pensum.</th></tr>
    <tr>
        <td colspan="5"><strong>Observaciones:</strong><br><textarea name="obs" cols="60" rows="5">{{ $data['obs'] ?? '' }}</textarea></td>
        <td>&nbsp;</td>
        <th colspan="2">Fecha:</th>
        <td colspan="2"><input type="text" class="sinborde" value="{{ $fecha }}" readonly></td>
    </tr>
    <tr><td colspan="8">&nbsp;</td><td colspan="2"><img src="{{ asset(ltrim($firma, './')) }}" width="150" alt=""></td></tr>
</table>
