@extends('layouts.institucional')

@section('title', 'SaiQ - Modulo pendiente')

@section('content')
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h2>Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3>M&oacute;dulo pendiente de migraci&oacute;n</h3>
                <div class="alert alert-info">
                    El m&oacute;dulo <strong>{{ $module }}</strong> sigue disponible en la copia PHP dentro de
                    <code>legacy_php</code>. Esta pantalla evita romper el men&uacute; mientras se migra el resto por fases.
                </div>
                <a class="btn btn-primary" href="{{ route('homologaciones.programa.create') }}">Volver</a>
            </div>
        </div>
    </div>
</section>
@endsection
