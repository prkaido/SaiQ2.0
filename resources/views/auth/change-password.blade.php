@extends('layouts.institucional')

@section('title', 'SaiQ - Cambio de clave')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h2>Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3>Cambio de clave</h3>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('password.update') }}" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Clave anterior:</label>
                        <div class="col-sm-8"><input type="password" name="ca" class="form-control pca-input" required></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Nueva clave:</label>
                        <div class="col-sm-8"><input type="password" name="nc" class="form-control pca-input" required minlength="6"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Repetir clave:</label>
                        <div class="col-sm-8"><input type="password" name="rc" class="form-control pca-input" required minlength="6"></div>
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
