@extends('layouts.institucional')

@section('title', 'SaiQ - Equivalencias')

@section('content')
@php
    $asignaturasPcaAgrupadas = collect($asignaturasPca)->groupBy(function ($asignatura) {
        return ($asignatura->programa_nombre ?: $asignatura->programa) . ' - Plan ' . ($asignatura->plan_num ?: 'N/A');
    });
    $asignaturasOrigenAgrupadas = collect($asignaturasPensumOrigen)->groupBy(function ($asignatura) {
        return ($asignatura->programa_nombre ?: $asignatura->programa) . ' - Plan ' . ($asignatura->plan_num ?: 'N/A');
    });
    $asignaturasExternasAgrupadas = collect($asignaturasExternas)->groupBy(function ($asignatura) {
        return ($asignatura->programa_nombre ?: $asignatura->programa) . ' - ' . ($asignatura->institucion_nombre ?: 'Sin institucion');
    });
@endphp
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA</h2>
                <h3>EQUIVALENCIAS</h3>
                @include('admin.shared.flash')

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead><tr><th>No.</th><th>Tipo</th><th>Asignatura destino</th><th>Asignatura origen &rarr; Programa</th></tr></thead>
                        <tbody>
                        @foreach($equivalencias as $equivalencia)
                            @php
                                $esReintegro = (int) ($equivalencia->programa_ext_enpca ?? 0) === 1;
                            @endphp
                            <tr>
                                <td>{{ $equivalencia->id }}</td>
                                <td>{{ $esReintegro ? 'Reintegro' : 'Externa' }}</td>
                                <td>{{ $equivalencia->asignatura_pca }} &rarr; {{ $equivalencia->programa_pca }} ({{ $equivalencia->plan_pca_num ?? 'N/A' }})</td>
                                <td>
                                    {{ $equivalencia->asignatura_ext ?? $equivalencia->asg_ext }} - {{ $equivalencia->programa_ext ?? 'Sin programa' }}
                                    @if($equivalencia->plan_ext_num)
                                        ({{ $equivalencia->plan_ext_num }})
                                    @endif
                                    &rarr; {{ $esReintegro ? 'Pensum PCA' : ($equivalencia->institucion_nombre ?? 'Sin institucion') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="pca-hr">
                <h3>Nueva equivalencia externa</h3>
                <form method="post" action="{{ route('admin.equivalencias.store') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <input type="hidden" name="tipo" value="externa">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Asignatura PCA:</label>
                        <div class="col-sm-8">
                            <select name="mp" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($asignaturasPcaAgrupadas as $grupo => $asignaturas)
                                    <optgroup label="{{ $grupo }}">
                                        @foreach($asignaturas as $asignatura)
                                            <option value="{{ $asignatura->id }}" @selected(old('tipo', 'externa') === 'externa' && (string) old('mp') === (string) $asignatura->id)>{{ $asignatura->cod }} - {{ $asignatura->nombre }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Asignatura de Programas de Origen:</label>
                        <div class="col-sm-8">
                            <select name="me" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($asignaturasExternasAgrupadas as $grupo => $asignaturas)
                                    <optgroup label="{{ $grupo }}">
                                        @foreach($asignaturas as $asignatura)
                                            <option value="{{ $asignatura->cod }}" @selected(old('tipo', 'externa') === 'externa' && old('me') === $asignatura->cod)>{{ $asignatura->cod }} - {{ $asignatura->nombre }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-actions text-center">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>

                <hr class="pca-hr">
                <h3>Nueva equivalencia por reintegro</h3>
                <form method="post" action="{{ route('admin.equivalencias.store') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <input type="hidden" name="tipo" value="reintegro">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Asignatura pensum nuevo:</label>
                        <div class="col-sm-8">
                            <select name="mp" id="reintegro_destino" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($asignaturasPcaAgrupadas as $grupo => $asignaturas)
                                    <optgroup label="{{ $grupo }}">
                                        @foreach($asignaturas as $asignatura)
                                            <option
                                                value="{{ $asignatura->id }}"
                                                data-program="{{ $asignatura->programa }}"
                                                data-plan="{{ $asignatura->plan }}"
                                                @selected(old('tipo') === 'reintegro' && (string) old('mp') === (string) $asignatura->id)
                                            >{{ $asignatura->cod }} - {{ $asignatura->nombre }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Asignatura pensum anterior:</label>
                        <div class="col-sm-8">
                            <select name="me" id="reintegro_origen" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($asignaturasOrigenAgrupadas as $grupo => $asignaturas)
                                    <optgroup label="{{ $grupo }}">
                                        @foreach($asignaturas as $asignatura)
                                            <option
                                                value="{{ $asignatura->id }}"
                                                data-program="{{ $asignatura->programa }}"
                                                data-plan="{{ $asignatura->plan }}"
                                                @selected(old('tipo') === 'reintegro' && (string) old('me') === (string) $asignatura->id)
                                            >{{ $asignatura->cod }} - {{ $asignatura->nombre }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-actions text-center">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function() {
    var destino = document.getElementById('reintegro_destino');
    var origen = document.getElementById('reintegro_origen');

    function filtrarOrigen() {
        if (!destino || !origen) {
            return;
        }

        var selected = destino.selectedOptions.length ? destino.selectedOptions[0] : null;
        var programa = selected ? selected.getAttribute('data-program') : '';
        var plan = selected ? selected.getAttribute('data-plan') : '';

        Array.prototype.forEach.call(origen.options, function(option) {
            var visible = option.value === '0'
                || !programa
                || (option.getAttribute('data-program') === programa && option.getAttribute('data-plan') !== plan);

            option.hidden = !visible;
            option.disabled = !visible;
        });

        Array.prototype.forEach.call(origen.querySelectorAll('optgroup'), function(group) {
            var hasVisibleOption = Array.prototype.some.call(group.querySelectorAll('option'), function(option) {
                return !option.hidden;
            });

            group.hidden = !hasVisibleOption;
            group.disabled = !hasVisibleOption;
        });

        if (origen.selectedOptions.length && origen.selectedOptions[0].disabled) {
            origen.value = '0';
        }
    }

    if (destino) {
        destino.addEventListener('change', filtrarOrigen);
    }

    filtrarOrigen();
}());
</script>
@endpush
