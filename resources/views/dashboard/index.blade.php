@extends('layouts.institucional')

@section('title', 'SaiQ - Inicio')

@section('content')
<section id="about" class="saiq-dashboard">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3>Men&uacute; principal</h3>

                @if($userType === 1)
                    <div class="saiq-menu-grid">
                        <a class="saiq-menu-item" href="{{ route('admin.instituciones.index') }}">
                            <i class="ion-university"></i>
                            <span>Instituciones</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('admin.programas.index') }}">
                            <i class="ion-ios-list"></i>
                            <span>Programas</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('admin.asignaturas.index') }}">
                            <i class="ion-document-text"></i>
                            <span>Asignaturas</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('admin.equivalencias.index') }}">
                            <i class="ion-arrow-swap"></i>
                            <span>Equivalencias</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('admin.relaciones.index') }}">
                            <i class="ion-merge"></i>
                            <span>Relaciones</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('admin.usuarios.index') }}">
                            <i class="ion-person-stalker"></i>
                            <span>Usuarios</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('homologaciones.borradores.index', ['estado' => 'todos']) }}">
                            <i class="ion-clipboard"></i>
                            <span>Auditor&iacute;a homologaciones</span>
                        </a>
                    </div>
                @else
                    <div class="saiq-menu-grid">
                        <a class="saiq-menu-item" href="{{ route('homologaciones.programa.create') }}">
                            <i class="ion-ios-paper"></i>
                            <span>Homologaci&oacute;n por programa</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('homologaciones.asignatura.create') }}">
                            <i class="ion-compose"></i>
                            <span>Homologaci&oacute;n por asignatura</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('reconocimiento.create') }}">
                            <i class="ion-ribbon-a"></i>
                            <span>Reconocimiento de t&iacute;tulo</span>
                        </a>
                        <a class="saiq-menu-item" href="{{ route('homologaciones.borradores.index') }}">
                            <i class="ion-clipboard"></i>
                            <span>Borradores y trazabilidad</span>
                        </a>
                    </div>
                @endif

                <div class="saiq-menu-secondary">
                    <a class="btn btn-default" href="{{ route('password.edit') }}">
                        <i class="ion-key"></i> Cambio de clave
                    </a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="ion-log-out"></i> Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
