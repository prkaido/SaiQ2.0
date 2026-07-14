@extends('layouts.institucional')

@section('title', 'SaiQ - Trazabilidad')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px; color: #333;">Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 25px; color: #555;">Trazabilidad de homologaci&oacute;n No. {{ $homologacion->id }}</h3>

                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Datos principales</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-4"><strong>Estado:</strong> {{ strtoupper($homologacion->estado ?? 'borrador') }}</div>
                            <div class="col-sm-4"><strong>Tipo:</strong> {{ ucfirst($homologacion->tipo) }}</div>
                            <div class="col-sm-4"><strong>Usuario:</strong> {{ $homologacion->user_id }}</div>
                        </div>
                        @if((int) ($homologacion->ciclo_universitario ?? 0) === 1)
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Ciclo:</strong> Universitario</div>
                                <div class="col-sm-4"><strong>Homologaci&oacute;n origen:</strong> {{ $homologacion->homologacion_origen_id ?? 'N/A' }}</div>
                            </div>
                        @endif
                        <hr>
                        <div class="row">
                            <div class="col-sm-4"><strong>Estudiante:</strong> {{ $homologacion->estudiante_ape }} {{ $homologacion->estudiante_nom }}</div>
                            <div class="col-sm-4"><strong>Documento:</strong> {{ $homologacion->estudiante_id }}</div>
                            <div class="col-sm-4"><strong>Periodo:</strong> {{ $homologacion->periodo }}</div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-4"><strong>Programa PCA:</strong> {{ $homologacion->programa_pca_nombre ?? $homologacion->programa_pca_cod }}</div>
                            <div class="col-sm-4"><strong>Procedencia:</strong> {{ $homologacion->programa_ext_nombre ?? $homologacion->programa_ext_cod }}</div>
                            <div class="col-sm-4"><strong>Instituci&oacute;n:</strong> {{ $homologacion->institucion_nombre ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Detalle de asignaturas</strong></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                            <thead>
                            <tr>
                                <th>C&oacute;digo PCA</th>
                                <th>Asignatura PCA</th>
                                <th>Asignatura procedencia</th>
                                <th>Semestre</th>
                                <th>Cr&eacute;ditos</th>
                                <th>Nota</th>
                                <th>Equivalencia</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($detalles as $detalle)
                                <tr>
                                    <td>{{ $detalle->asignatura_pca_cod }}</td>
                                    <td>{{ $detalle->asignatura_pca_nombre }}</td>
                                    <td>{{ $detalle->asignatura_ext_nombre }}</td>
                                    <td>{{ $detalle->semestre }}</td>
                                    <td>{{ $detalle->creditos }}</td>
                                    <td>{{ $detalle->nota ?? 'N/A' }}</td>
                                    <td>{{ (int) $detalle->tiene_equivalencia === 1 ? 'Si' : 'No' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No hay detalle registrado.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Auditor&iacute;a de cambios</strong></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                            <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acci&oacute;n</th>
                                <th>Antes</th>
                                <th>Despu&eacute;s</th>
                                <th>Detalle</th>
                                <th>IP</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($auditoria as $log)
                                @php
                                    $details = json_decode($log->details ?? '', true) ?: [];
                                @endphp
                                <tr>
                                    <td>{{ $log->created_at ?? $log->timestamp ?? '' }}</td>
                                    <td>{{ $log->user_id }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td><small>{{ $log->campo_anterior }}</small></td>
                                    <td><small>{{ $log->campo_nuevo }}</small></td>
                                    <td>
                                        @if($details)
                                            @foreach($details as $key => $value)
                                                <small><strong>{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</small><br>
                                            @endforeach
                                        @else
                                            <small>N/A</small>
                                        @endif
                                    </td>
                                    <td>{{ $log->ip_address ?? $log->ip ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No hay eventos de auditor&iacute;a para esta homologaci&oacute;n.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if(in_array(($homologacion->estado ?? 'borrador'), ['borrador', 'completado'], true))
                    <a class="btn btn-success" href="{{ route('homologaciones.borradores.edit', $homologacion->id) }}">
                        {{ ($homologacion->estado ?? 'borrador') === 'completado' ? 'Editar completada' : 'Editar borrador' }}
                    </a>
                @endif
                <a class="btn btn-default" href="{{ route('homologaciones.borradores.index') }}">Volver</a>
            </div>
        </div>
    </div>
</section>
@endsection
