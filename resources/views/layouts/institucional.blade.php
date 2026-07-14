<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>@yield('title', 'SaiQ - Homologaciones')</title>
    <link rel="icon" href="{{ asset('img/favicon-pca.png') }}">
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/ionicons/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/animate-css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/slider/slider.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/owl-carousel/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/owl-carousel/owl.theme.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fancybox/jquery.fancybox.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/saiq-responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pca-colores.css') }}">
    @stack('styles')
</head>
<body>
<header id="top-bar" class="navbar-fixed-top animated-header">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <div class="navbar-brand">
                <a href="https://pca.edu.co/">
                    <img src="{{ asset('img/upca-horiz.png') }}" height="40" alt="PCA">
                </a>
            </div>
        </div>
        <div class="main-menu collapse navbar-collapse">
            <ul class="nav navbar-nav navbar-right">
                @if(session('x'))
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Inicio</a></li>
                    @if((int) session('tus') === 1)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Admin</a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a class="dropdown-item" href="{{ route('admin.programas.index') }}">CRUD Programas</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.asignaturas.index') }}">Asignaturas</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.instituciones.index') }}">Instituciones</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.equivalencias.index') }}">Equivalencias</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.relaciones.index') }}">Relaciones</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
                                <li><a class="dropdown-item" href="{{ route('homologaciones.borradores.index', ['estado' => 'todos']) }}">Auditor&iacute;a homologaciones</a></li>
                                <li><a class="dropdown-item" href="{{ route('password.edit') }}">Clave</a></li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Homologaci&oacute;n</a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a class="dropdown-item" href="{{ route('homologaciones.programa.create') }}">Por Programa</a></li>
                                <li><a class="dropdown-item" href="{{ route('homologaciones.asignatura.create') }}">Por Asignatura</a></li>
                                <li><a class="dropdown-item" href="{{ route('homologaciones.borradores.index') }}">Borradores</a></li>
                                <li><a class="dropdown-item" href="{{ route('reconocimiento.create') }}">T&iacute;tulo</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('password.edit') }}">Cambio de clave</a></li>
                    @endif
                    <li class="nav-item">
                        <form method="post" action="{{ route('logout') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="nav-link" style="background:transparent;border:0;color:white;padding:8px 15px;">Salir</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="https://pca.edu.co/">PCA</a></li>
                @endif
            </ul>
        </div>
    </div>
</header>

@yield('content')

<section class="info-section">
    <div class="container">
        <div class="row section-inner">
            <div class="col-md-4 info-logo">
                <a href="https://pca.edu.co" title="Portal PCA">
                    <img src="{{ asset('img/upca-verti.png') }}" alt="Logo PCA">
                </a>
            </div>
            <div class="col-md-8 info-content">
                <h3>Informes</h3>
                <p>
                    C.R.I.: +57 (605) 336 18 00 Ext. 188<br>
                    <a href="mailto:jtorresm@pca.edu.co">jtorresm@pca.edu.co</a><br>
                    Barranquilla - Atl&aacute;ntico - Colombia
                </p>
            </div>
        </div>
    </div>
</section>

<section id="footer-section" style="padding: 40px 0; background-color: white;">
    <footer id="footer">
        <div class="container">
            <div class="col-md-8">
                <p class="copyright">
                    Copyright &copy; <span id="year"></span>
                    <strong>Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</strong>
                </p>
            </div>
            <div class="col-md-4">
                <ul class="social">
                    <li><a href="https://instagram.com/pca.edu.co" class="Instagram"><i class="ion-social-instagram"></i></a></li>
                    <li><a href="https://facebook.com/policostagdlca" class="Facebook"><i class="ion-social-facebook"></i></a></li>
                    <li><a href="https://twitter.com/polcomagdalena" class="Twitter"><i class="ion-social-twitter"></i></a></li>
                </ul>
            </div>
        </div>
    </footer>
</section>

<script src="{{ asset('plugins/jQuery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/owl-carousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('plugins/wow-js/wow.min.js') }}"></script>
<script src="{{ asset('plugins/slider/slider.js') }}"></script>
<script src="{{ asset('plugins/fancybox/jquery.fancybox.js') }}"></script>
<script src="{{ asset('js/index.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script>document.getElementById('year').textContent = new Date().getFullYear();</script>
@stack('scripts')
</body>
</html>
