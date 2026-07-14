@extends('layouts.institucional')

@section('title', 'SaiQ - Reconocimiento de titulo')

@section('content')
<section id="about" style="padding-top: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3>Reconocimiento de t&iacute;tulo</h3>

                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('reconocimiento.generate') }}" method="post" name="fff" target="_blank" class="form-horizontal homologacion-form">
                    @csrf
                    <div class="form-group">
                        <label for="per" class="col-sm-4 control-label">Periodo Acad&eacute;mico:</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control pca-input" id="per" name="per" value="{{ old('per', $periodo->id ?? '') }}" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nom" class="col-sm-4 control-label">Nombre:</label>
                        <div class="col-sm-8">
                            <div class="inline-field-group">
                                <select name="tra" class="form-control inline-field-prefix">
                                    <option value="1">Sr.</option>
                                    <option value="2">Sra.</option>
                                </select>
                                <input type="text" class="form-control pca-input" id="nom" name="nom" value="{{ old('nom') }}" placeholder="Nombre completo">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="num" class="col-sm-4 control-label">Documento:</label>
                        <div class="col-sm-8">
                            <div class="inline-field-group">
                                <select name="tdo" class="form-control inline-field-prefix">
                                    <option value="0">C.C.</option>
                                    <option value="1">C.E.</option>
                                    <option value="2">PEP</option>
                                    <option value="3">P.P.</option>
                                    <option value="4">T.I.</option>
                                </select>
                                <input type="text" class="form-control pca-input" id="num" name="num" value="{{ old('num') }}" placeholder="Numero de identificacion">
                            </div>
                        </div>
                    </div>

                    <hr class="pca-hr">

                    <div class="form-group">
                        <label for="pca" class="col-sm-4 control-label">Programa Profesional:</label>
                        <div class="col-sm-8">
                            <select name="pca" id="pca" class="form-control pca-input">
                                <option value="0">[Seleccione]</option>
                                @foreach($programasPca as $programa)
                                    <option value="{{ $programa->cod }}" @selected(old('pca') === $programa->cod)>{{ $programa->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ins" class="col-sm-4 control-label">Instituci&oacute;n de procedencia:</label>
                        <div class="col-sm-8">
                            <select name="ins" id="ins" class="form-control pca-input">
                                <option value="0">[Seleccione]</option>
                                @foreach($instituciones as $institucion)
                                    <option value="{{ $institucion->id }}" @selected((string) old('ins') === (string) $institucion->id)>{{ $institucion->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ext" class="col-sm-4 control-label">Programa acad&eacute;mico de procedencia:</label>
                        <div class="col-sm-8">
                            <select name="ext" id="ext" class="form-control pca-input">
                                <option value="0">[Seleccione]</option>
                                @foreach($programasReconocimiento as $programa)
                                    <option value="{{ $programa->cod }}" @selected(old('ext') === $programa->cod)>{{ $programa->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-actions text-center">
                        <input type="button" class="btn btn-primary pca-btn-submit" value="Generar" onclick="guardar();">
                        <input type="reset" class="btn btn-default" value="Limpiar">
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function guardar() {
    if (document.fff.nom.value.length === 0) {
        alert('Debe escribir un nombre de estudiante');
        document.fff.nom.focus();
        return false;
    }
    if (document.fff.per.value.length === 0) {
        alert('Debe escribir un periodo para la admision');
        document.fff.per.focus();
        return false;
    }
    if (document.fff.num.value.length === 0) {
        alert('Debe escribir la ID del estudiante');
        document.fff.num.focus();
        return false;
    }
    if (document.fff.pca.value === '0') {
        alert('Elija un programa Politecnico');
        document.fff.pca.focus();
        return false;
    }
    if (document.fff.ext.value === '0') {
        alert('Escriba un programa de procedencia');
        document.fff.ext.focus();
        return false;
    }
    if (document.fff.ins.value === '0') {
        alert('Escriba una institucion de origen');
        document.fff.ins.focus();
        return false;
    }
    document.fff.submit();
}
</script>
@endpush
