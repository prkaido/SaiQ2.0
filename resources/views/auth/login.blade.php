@extends('layouts.institucional')

@section('title', 'SaiQ - Ingreso')

@section('content')
<section class="login-page">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h1 class="page-title">HOMOLOGACIONES</h1>
                <div class="login-panel">
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form id="loginForm" action="{{ route('login.store') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="usuario">Usuario:</label>
                            <input type="text" id="usuario" name="us" class="form-control" required maxlength="50"
                                   pattern="[a-zA-Z0-9_@.]{3,50}" placeholder="Ingrese su usuario"
                                   autocomplete="username" value="{{ old('us') }}">
                        </div>

                        <div class="form-group">
                            <label for="password">Contrase&ntilde;a:</label>
                            <input type="password" id="password" name="pa" class="form-control" required
                                   minlength="3" maxlength="100" placeholder="Ingrese su contrase&ntilde;a"
                                   autocomplete="current-password">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Ingresar</button>
                        </div>
                    </form>

                    <div class="support-text">
                        <p>&iquest;Necesita ayuda? Contacte a la C.R.I.</p>
                        <p>+57 (605) 336 18 00 Ext. 188<br>
                            <a href="mailto:jtorresm@pca.edu.co">jtorresm@pca.edu.co</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
