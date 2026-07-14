@extends('layouts.institucional')

@section('title', 'SaiQ - Borradores y trazabilidad')

@push('styles')
<style>
    .saiq-actions-cell {
        min-width: 165px;
    }

    .saiq-action-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: flex-start;
    }

    .saiq-action-button,
    .saiq-action-button:focus,
    .saiq-action-button:hover {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 150px;
        min-height: 34px;
        padding: 8px 10px !important;
        border: 0;
        border-radius: 4px !important;
        color: #fff !important;
        box-shadow: none !important;
        font-size: 12px !important;
        font-weight: 700;
        letter-spacing: 0 !important;
        line-height: 1.2;
        text-align: center;
        text-transform: none !important;
        transform: none !important;
        white-space: normal;
    }

    .saiq-action-edit {
        background-color: #5cb85c;
    }

    .saiq-action-trace {
        background-color: #337ab7;
    }

    .saiq-action-edit:hover {
        background-color: #449d44;
    }

    .saiq-action-trace:hover {
        background-color: #286090;
    }
</style>
@endpush

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px; color: #333;">Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 25px; color: #555;">Borradores y trazabilidad de homologaciones</h3>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div style="margin-bottom: 18px;">
                    <a class="btn btn-default" href="{{ route('homologaciones.borradores.index', ['estado' => 'borrador']) }}">Borradores</a>
                    <a class="btn btn-default" href="{{ route('homologaciones.borradores.index', ['estado' => 'completado']) }}">Completadas</a>
                    <a class="btn btn-default" href="{{ route('homologaciones.borradores.index', ['estado' => 'todos']) }}">Todas</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="background: white;">
                        <thead style="background-color: #333; color: white;">
                        <tr>
                            <th>Consecutivo</th>
                            <th>Estado</th>
                            <th>Tipo</th>
                            <th>Estudiante</th>
                            <th>Documento</th>
                            <th>Programa PCA</th>
                            <th>Programa procedencia</th>
                            <th>Actualizado</th>
                            <th>Acci&oacute;n</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($homologaciones as $homologacion)
                            <tr>
                                <td>{{ $homologacion->id }}</td>
                                <td>
                                    <span class="label {{ $homologacion->estado === 'completado' ? 'label-success' : 'label-warning' }}">
                                        {{ strtoupper($homologacion->estado ?? 'borrador') }}
                                    </span>
                                </td>
                                <td>
                                    {{ ucfirst($homologacion->tipo) }}
                                    @if((int) ($homologacion->ciclo_universitario ?? 0) === 1)
                                        <br><small>Ciclo universitario</small>
                                    @endif
                                </td>
                                <td>{{ $homologacion->estudiante_ape }} {{ $homologacion->estudiante_nom }}</td>
                                <td>{{ $homologacion->estudiante_id }}</td>
                                <td>{{ $homologacion->programa_pca_nombre ?? $homologacion->programa_pca_cod }}</td>
                                <td>{{ $homologacion->programa_ext_nombre ?? $homologacion->programa_ext_cod }}</td>
                                <td>{{ $homologacion->updated_at }}</td>
                                <td class="saiq-actions-cell">
                                    <div class="saiq-action-list">
                                    @if(in_array(($homologacion->estado ?? 'borrador'), ['borrador', 'completado'], true))
                                        <a class="saiq-action-button saiq-action-edit" href="{{ route('homologaciones.borradores.edit', $homologacion->id) }}">
                                            {{ ($homologacion->estado ?? 'borrador') === 'completado' ? 'Editar completada' : 'Editar borrador' }}
                                        </a>
                                    @endif
                                    <a class="saiq-action-button saiq-action-trace" href="{{ route('homologaciones.trazabilidad.show', $homologacion->id) }}">
                                        Ver trazabilidad
                                    </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No hay registros para el filtro seleccionado.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
