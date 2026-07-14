@extends('layouts.institucional')

@section('title', 'SaiQ - Programas')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA</h2>
                <h3>PROGRAMAS ACAD&Eacute;MICOS</h3>
                @include('admin.shared.flash')

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr><th>C&oacute;d.</th><th>Nombre</th><th>Nivel</th><th>Instituci&oacute;n</th><th>Activo</th></tr>
                        </thead>
                        <tbody>
                        @foreach($programas as $programa)
                            <tr>
                                <td>{{ $programa->cod }}</td>
                                <td>{{ $programa->nombre }}</td>
                                <td>{{ $programa->nivel_nombre }}</td>
                                <td>{{ $programa->institucion_nombre }}</td>
                                <td>{{ $programa->activo }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="pca-hr">
                <h3>Nuevo programa</h3>
                <form method="post" action="{{ route('admin.programas.store') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label class="col-sm-4 control-label">C&oacute;digo:</label>
                        <div class="col-sm-8"><input type="text" name="co" class="form-control pca-input" value="{{ old('co') }}" placeholder="Solo para PCA"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Nombre:</label>
                        <div class="col-sm-8"><input type="text" name="no" class="form-control pca-input" required value="{{ old('no') }}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Instituci&oacute;n:</label>
                        <div class="col-sm-8">
                            <select name="pr" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($instituciones as $institucion)
                                    <option value="{{ $institucion->id }}" @selected((string) old('pr') === (string) $institucion->id)>{{ $institucion->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Nivel:</label>
                        <div class="col-sm-8">
                            <select name="ni" class="form-control pca-input" required>
                                <option value="0">[Seleccionar]</option>
                                @foreach($niveles as $nivel)
                                    <option value="{{ $nivel->id }}" @selected((string) old('ni') === (string) $nivel->id)>{{ $nivel->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Activo:</label>
                        <div class="col-sm-8">
                            <label><input type="radio" name="ac" value="1" @checked(old('ac') === '1')> S&iacute;</label>
                            <label style="margin-left: 15px;"><input type="radio" name="ac" value="0" @checked(old('ac', '0') === '0')> No</label>
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
