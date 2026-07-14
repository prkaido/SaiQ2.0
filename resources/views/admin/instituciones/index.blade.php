@extends('layouts.institucional')

@section('title', 'SaiQ - Instituciones')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>CORPORACI&Oacute;N UNIVERSITARIA POLIT&Eacute;CNICO COSTA ATL&Aacute;NTICA</h2>
                <h3>INSTITUCIONES EDUCATIVAS</h3>
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" width="100%">
                        <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nombre</th>
                            <th>Abreviatura</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($instituciones as $institucion)
                            <tr>
                                <td>{{ $institucion->id }}</td>
                                <td>{{ $institucion->nombre }}</td>
                                <td>{{ $institucion->abrev }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <hr class="pca-hr">
                <h3>Nueva instituci&oacute;n</h3>
                <form method="post" action="{{ route('admin.instituciones.store') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Instituci&oacute;n:</label>
                        <div class="col-sm-8">
                            <input type="text" name="no" class="form-control pca-input" required maxlength="200" value="{{ old('no') }}">
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
