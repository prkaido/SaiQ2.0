@extends('layouts.institucional')

@section('title', 'SaiQ - Usuarios')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA</h2>
                <h3>USUARIOS</h3>
                @include('admin.shared.flash')

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead><tr><th>Id.</th><th>Programa</th><th>Firma</th><th>Estado</th></tr></thead>
                        <tbody>
                        @foreach($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario->id }}</td>
                                <td>{{ $usuario->programa_nombre }}</td>
                                <td>
                                    @if($usuario->firma)
                                        <img src="{{ asset(ltrim($usuario->firma, './')) }}" height="20" alt="">
                                    @endif
                                </td>
                                <td>{{ $usuario->activo }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="pca-hr">
                <h3>Nuevo usuario</h3>
                <form method="post" action="{{ route('admin.usuarios.store') }}" enctype="multipart/form-data" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Id:</label>
                        <div class="col-sm-8"><input type="text" name="no" class="form-control pca-input" required value="{{ old('no') }}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Contrase&ntilde;a:</label>
                        <div class="col-sm-8"><input type="password" name="co" class="form-control pca-input" required minlength="6"></div>
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
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Firma:</label>
                        <div class="col-sm-8"><input type="file" name="fi" class="form-control pca-input" accept=".jpg,.jpeg,.png"></div>
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
