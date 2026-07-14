@extends('layouts.institucional')

@section('title', 'SaiQ - Asignaturas')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA</h2>
                <h3>ASIGNATURAS</h3>
                @include('admin.shared.flash')

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr><th>C&oacute;d.</th><th>Asignatura</th><th>Programa - Plan</th><th>Semestre</th><th>Instituci&oacute;n</th></tr>
                        </thead>
                        <tbody>
                        @foreach($asignaturas as $asignatura)
                            <tr>
                                <td>{{ $asignatura->cod }}</td>
                                <td>{{ $asignatura->nombre }}</td>
                                <td>{{ $asignatura->programa_nombre }} - {{ $asignatura->plan_num }}</td>
                                <td>{{ $asignatura->nivel }}</td>
                                <td>{{ $asignatura->institucion_nombre }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="pca-hr">
                <h3>Nueva asignatura</h3>
                <form method="post" action="{{ route('admin.asignaturas.store') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label class="col-sm-4 control-label">C&oacute;digo:</label>
                        <div class="col-sm-8"><input type="text" name="co" class="form-control pca-input" value="{{ old('co') }}" placeholder="Opcional"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Nombre:</label>
                        <div class="col-sm-8"><input type="text" name="no" class="form-control pca-input" required value="{{ old('no') }}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Programa:</label>
                        <div class="col-sm-8">
                            <select name="pr" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($programas as $programa)
                                    <option value="{{ $programa->cod }}" @selected(old('pr') === $programa->cod)>{{ $programa->nombre }} - {{ $programa->institucion_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Plan:</label>
                        <div class="col-sm-8">
                            <select name="pl" class="form-control pca-input">
                                <option value="">[Seleccionar]</option>
                                @foreach($planes as $plan)
                                    <option value="{{ $plan->id }}" @selected((string) old('pl') === (string) $plan->id)>{{ $plan->num }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Nivel:</label>
                        <div class="col-sm-8"><input type="number" name="ni" class="form-control pca-input" value="{{ old('ni') }}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Cr&eacute;ditos:</label>
                        <div class="col-sm-8"><input type="number" step="0.1" name="cr" class="form-control pca-input" value="{{ old('cr') }}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Intensidad semanal:</label>
                        <div class="col-sm-8"><input type="number" name="is" class="form-control pca-input" value="{{ old('is') }}"></div>
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
