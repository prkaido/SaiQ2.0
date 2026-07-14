@extends('layouts.institucional')

@section('title', 'SaiQ - Relaciones')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA</h2>
                <h3>PROGRAMAS RELACIONADOS</h3>
                @include('admin.shared.flash')

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead><tr><th>No.</th><th>Direcci&oacute;n</th><th>Programa</th><th>Activo</th></tr></thead>
                        <tbody>
                        @foreach($relaciones as $relacion)
                            <tr>
                                <td>{{ $relacion->id }}</td>
                                <td>{{ $relacion->dir }}</td>
                                <td>{{ $relacion->programa_nombre }}</td>
                                <td>{{ $relacion->activo }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="pca-hr">
                <h3>Agregar relaci&oacute;n Director - Programa</h3>
                <form method="post" action="{{ route('admin.relaciones.store') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Direcci&oacute;n:</label>
                        <div class="col-sm-8">
                            <select name="di" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($directores as $director)
                                    <option value="{{ $director->programa }}" @selected(old('di') === $director->programa)>{{ $director->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Programa:</label>
                        <div class="col-sm-8">
                            <select name="pr" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($programas as $programa)
                                    <option value="{{ $programa->cod }}" @selected(old('pr') === $programa->cod)>{{ $programa->nombre }}</option>
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
