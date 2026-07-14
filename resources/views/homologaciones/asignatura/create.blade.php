@extends('layouts.institucional')

@section('title', 'SaiQ - Homologacion por Asignaturas')

@section('content')
@php
    $draft = $draft ?? null;
    $selectedPca = old('pca', $draft->programa_pca_cod ?? '');
    $selectedPlan = old('pla', $draft->plan_id ?? '');
    $draftCiclo = $draftCicloUniversitario ?? null;
    $homologarCiclo = old('homologar_ciclo_universitario', $draftCiclo ? '1' : '');
    $selectedPcaCiclo = old('pca_ciclo_universitario', $draftCiclo->programa_pca_cod ?? '');
    $selectedPlanCiclo = old('pla_ciclo_universitario', $draftCiclo->plan_id ?? '');
    $editingCompleted = ($draft->estado ?? '') === 'completado';
    $cambioPensum = old('cambio_pensum', ($draft->tipo_estudio ?? '') === 'r' ? '1' : '');
    $institucionPcaId = $institucionPca->id ?? '';
@endphp
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px; color: #333;">Corporaci&oacute;n Universitaria Polit&eacute;cnico Costa Atl&aacute;ntica</h2>
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 35px; color: #555;">Homologaci&oacute;n por Asignaturas</h3>

                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                @if($draft)
                    <div class="alert alert-info">Editando homologaci&oacute;n No. {{ $draft->id }} en estado {{ strtoupper($draft->estado ?? 'borrador') }}. Al guardar o continuar se actualizar&aacute; este mismo consecutivo.</div>
                @endif

                <form action="{{ route('homologaciones.asignatura.review') }}" method="post" name="fff" style="max-width: 1000px;">
                    @csrf
                    <input type="hidden" name="_accion" value="continuar">
                    @if($draft)
                        <input type="hidden" name="homologacion_id" value="{{ old('homologacion_id', $draft->id) }}">
                    @endif
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #f5f5f5;">
                        <thead>
                            <tr style="background-color: #333; color: white;">
                                <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px;">Nombre</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px;">Apellido</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px;">Identificaci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background-color: #f9f9f9; border-bottom: 1px solid #ddd;">
                                <td style="padding: 12px;"><input type="text" class="form-control" name="nom" id="nom" value="{{ old('nom', $draft->estudiante_nom ?? '') }}" style="padding: 8px 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;"></td>
                                <td style="padding: 12px;"><input type="text" class="form-control" name="ape" id="ape" value="{{ old('ape', $draft->estudiante_ape ?? '') }}" style="padding: 8px 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;"></td>
                                <td style="padding: 12px;"><input type="text" class="form-control" name="ide" id="ide" value="{{ old('ide', $draft->estudiante_id ?? '') }}" style="padding: 8px 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;"></td>
                            </tr>
                        </tbody>
                    </table>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <tr style="background-color: #f5f5f5;">
                            <td style="padding: 14px 12px; font-weight: 600; font-size: 13px; width: 70%; border: 1px solid #ddd;"></td>
                            <td style="padding: 14px 12px; text-align: left; border: 1px solid #ddd;">
                                <label style="font-weight: 600; font-size: 12px; color: #333; display: block; margin-bottom: 6px;">Per&iacute;odo acad&eacute;mico:</label>
                                <input type="text" class="form-control" name="per" id="per" value="{{ old('per', $draft->periodo ?? ($periodo->id ?? '')) }}" readonly style="padding: 8px 10px; font-size: 14px; background-color: #e9ecef; border: 1px solid #ddd; border-radius: 4px;">
                            </td>
                        </tr>
                    </table>

                    <div style="background-color: #f5f5f5; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="prog_pca" style="font-weight: 600; color: #333; font-size: 14px; display: block; margin-bottom: 8px;">Programa PCA:</label>
                            <select name="pca" id="prog_pca" class="form-control" style="padding: 10px 12px; font-size: 14px; border: 1px solid #d0d0d0; border-radius: 4px; background-color: white;">
                                <option value="0">[Seleccione Programa]</option>
                                @foreach($programasPca as $programa)
                                    <option value="{{ $programa->cod }}" @selected($selectedPca === $programa->cod)>{{ $programa->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="pla" style="font-weight: 600; color: #333; font-size: 14px; display: block; margin-bottom: 8px;">Plan:</label>
                            <select name="pla" id="pla" class="form-control" style="padding: 10px 12px; font-size: 14px; border: 1px solid #d0d0d0; border-radius: 4px; background-color: white;">
                                <option value="0">[Seleccione Plan]</option>
                                @foreach($planes as $plan)
                                    <option value="{{ $plan->id }}" data-ref="{{ substr($plan->num, 0, 3) }}" data-program="{{ $plan->programa }}" @selected((string) $selectedPlan === (string) $plan->id)>{{ $plan->num }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="margin-bottom: 16px; padding: 12px; border: 1px solid #d8d8d8; background-color: #fff; border-radius: 4px;">
                            <label style="font-weight: 600; color: #333; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 0;">
                                <input type="checkbox" name="cambio_pensum" id="cambio_pensum" value="1" @checked((string) $cambioPensum === '1')>
                                Cambio de pensum / reintegro
                            </label>
                        </div>

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="ext" style="font-weight: 600; color: #333; font-size: 14px; display: block; margin-bottom: 8px;">Programa de Procedencia:</label>
                            <input type="text" name="ext" id="ext" class="form-control" value="{{ old('ext', $draft->programa_ext_nombre ?? $draft->programa_ext_cod ?? '') }}" maxlength="150" placeholder="Escriba el programa de procedencia" style="padding: 10px 12px; font-size: 14px; border: 1px solid #d0d0d0; border-radius: 4px; background-color: white;">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="ins" style="font-weight: 600; color: #333; font-size: 14px; display: block; margin-bottom: 8px;">Instituci&oacute;n de Procedencia:</label>
                            <select name="ins" id="ins" class="form-control" style="padding: 10px 12px; font-size: 14px; border: 1px solid #d0d0d0; border-radius: 4px; background-color: white;">
                                <option value="0">[Seleccione]</option>
                                @foreach($instituciones as $institucion)
                                    <option value="{{ $institucion->id }}" @selected((string) old('ins', $draft->institucion_id ?? '') === (string) $institucion->id)>{{ $institucion->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #d8d8d8;">
                            <label style="font-weight: 600; color: #333; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                <input type="checkbox" name="homologar_ciclo_universitario" id="homologar_ciclo_universitario" value="1" @checked((string) $homologarCiclo === '1')>
                                Homologar tambi&eacute;n para ciclo universitario
                            </label>

                            <div id="ciclo_universitario_panel" style="display: none;">
                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label for="pca_ciclo_universitario" style="font-weight: 600; color: #333; font-size: 14px; display: block; margin-bottom: 8px;">Programa ciclo universitario:</label>
                                    <select name="pca_ciclo_universitario" id="pca_ciclo_universitario" class="form-control" style="padding: 10px 12px; font-size: 14px; border: 1px solid #d0d0d0; border-radius: 4px; background-color: white;">
                                        <option value="0">[Seleccione Programa]</option>
                                        @foreach($programasUniversitarios as $programa)
                                            <option value="{{ $programa->cod }}" @selected($selectedPcaCiclo === $programa->cod)>{{ $programa->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="pla_ciclo_universitario" style="font-weight: 600; color: #333; font-size: 14px; display: block; margin-bottom: 8px;">Plan ciclo universitario:</label>
                                    <select name="pla_ciclo_universitario" id="pla_ciclo_universitario" class="form-control" style="padding: 10px 12px; font-size: 14px; border: 1px solid #d0d0d0; border-radius: 4px; background-color: white;">
                                        <option value="0">[Seleccione Plan]</option>
                                        @foreach($planes as $plan)
                                            <option value="{{ $plan->id }}" data-program="{{ $plan->programa }}" @selected((string) $selectedPlanCiclo === (string) $plan->id)>{{ $plan->num }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <button type="button" class="btn btn-default" onclick="guardarBorrador();" style="padding: 12px 30px; font-size: 14px; font-weight: 600; background-color: #e9e9e9; color: #333; border: 1px solid #d0d0d0; border-radius: 4px; cursor: pointer; margin-right: 10px;">{{ $editingCompleted ? 'Guardar cambios' : 'Guardar borrador' }}</button>
                        <button type="button" class="btn btn-primary" onclick="guardar();" style="padding: 12px 30px; font-size: 14px; font-weight: 600; background-color: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Continuar</button>
                        <button type="reset" class="btn btn-default" style="padding: 12px 30px; font-size: 14px; font-weight: 600; background-color: #e9e9e9; color: #333; border: 1px solid #d0d0d0; border-radius: 4px; cursor: pointer;">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function validarHomologacion() {
    if (document.fff.nom.value.length === 0) {
        alert('Debe escribir un nombre de estudiante');
        document.fff.nom.focus();
        return false;
    }
    if (document.fff.ape.value.length === 0) {
        alert('Debe escribir un apellido para el estudiante');
        document.fff.ape.focus();
        return false;
    }
    if (document.fff.ide.value.length === 0) {
        alert('Debe escribir la ID del estudiante');
        document.fff.ide.focus();
        return false;
    }
    if (document.fff.pca.value === '0') {
        alert('Elija un programa Politecnico');
        document.fff.pca.focus();
        return false;
    }
    if (document.fff.pla.value === '0') {
        alert('Debe elegir un plan');
        document.fff.pla.focus();
        return false;
    }
    if (!document.fff.cambio_pensum.checked && document.fff.ext.value.trim().length === 0) {
        alert('Escriba un programa de origen');
        document.fff.ext.focus();
        return false;
    }
    if (!document.fff.cambio_pensum.checked && document.fff.ins.value === '0') {
        alert('Escriba una institucion de origen');
        document.fff.ins.focus();
        return false;
    }
    if (document.fff.homologar_ciclo_universitario.checked) {
        if (document.fff.pca_ciclo_universitario.value === '0') {
            alert('Elija un programa para el ciclo universitario');
            document.fff.pca_ciclo_universitario.focus();
            return false;
        }
        if (document.fff.pla_ciclo_universitario.value === '0') {
            alert('Debe elegir un plan para el ciclo universitario');
            document.fff.pla_ciclo_universitario.focus();
            return false;
        }
    }
    return true;
}

function guardar() {
    if (!validarHomologacion()) {
        return false;
    }
    document.fff._accion.value = 'continuar';
    document.fff.target = '_self';
    document.fff.submit();
}

function guardarBorrador() {
    if (!validarHomologacion()) {
        return false;
    }
    document.fff._accion.value = 'borrador';
    document.fff.target = '_self';
    document.fff.submit();
}

(function() {
    var relacionesCiclo = @json($relacionesCiclo->map(fn ($relacion) => ['dir' => $relacion->dir, 'prog' => $relacion->prog])->values());
    var checkbox = document.getElementById('homologar_ciclo_universitario');
    var cambioPensum = document.getElementById('cambio_pensum');
    var panel = document.getElementById('ciclo_universitario_panel');
    var programaPca = document.getElementById('prog_pca');
    var programaExt = document.getElementById('ext');
    var institucion = document.getElementById('ins');
    var programaCiclo = document.getElementById('pca_ciclo_universitario');
    var planCiclo = document.getElementById('pla_ciclo_universitario');
    var institucionPcaId = @json((string) $institucionPcaId);

    function filtrarProgramasCiclo() {
        if (!programaPca || !programaCiclo) {
            return;
        }

        var relacionados = relacionesCiclo
            .filter(function(relacion) { return relacion.dir === programaPca.value; })
            .map(function(relacion) { return relacion.prog; });

        Array.prototype.forEach.call(programaCiclo.options, function(option) {
            var visible = option.value === '0' || relacionados.length === 0 || relacionados.indexOf(option.value) !== -1;

            option.hidden = !visible;
            option.disabled = !visible;
        });

        if (programaCiclo.selectedOptions.length && programaCiclo.selectedOptions[0].disabled) {
            programaCiclo.value = '0';
        }
    }

    function filtrarPlanesCiclo() {
        if (!programaCiclo || !planCiclo) {
            return;
        }

        Array.prototype.forEach.call(planCiclo.options, function(option) {
            var visible = option.value === '0' || option.getAttribute('data-program') === programaCiclo.value;

            option.hidden = !visible;
            option.disabled = !visible;
        });

        if (planCiclo.selectedOptions.length && planCiclo.selectedOptions[0].disabled) {
            planCiclo.value = '0';
        }
    }

    function actualizarPanelCiclo() {
        if (!checkbox || !panel) {
            return;
        }

        panel.style.display = checkbox.checked ? 'block' : 'none';
        if (programaCiclo) {
            programaCiclo.disabled = !checkbox.checked;
        }
        if (planCiclo) {
            planCiclo.disabled = !checkbox.checked;
        }

        if (checkbox.checked) {
            filtrarProgramasCiclo();
            filtrarPlanesCiclo();
        }
    }

    function actualizarCambioPensum() {
        if (!cambioPensum) {
            return;
        }

        var activo = cambioPensum.checked;

        if (programaExt) {
            programaExt.readOnly = activo;
            if (activo) {
                programaExt.value = 'Cambio de pensum';
                programaExt.placeholder = 'Cambio de pensum';
            } else if (programaExt.value === 'Cambio de pensum') {
                programaExt.value = '';
                programaExt.placeholder = 'Escriba el programa de procedencia';
            }
        }

        if (institucion && activo && institucionPcaId) {
            institucion.value = institucionPcaId;
        }
    }

    if (checkbox) {
        checkbox.addEventListener('change', actualizarPanelCiclo);
    }
    if (cambioPensum) {
        cambioPensum.addEventListener('change', actualizarCambioPensum);
    }
    if (programaPca) {
        programaPca.addEventListener('change', function() {
            filtrarProgramasCiclo();
            filtrarPlanesCiclo();
        });
    }
    if (programaCiclo) {
        programaCiclo.addEventListener('change', filtrarPlanesCiclo);
    }

    actualizarPanelCiclo();
    actualizarCambioPensum();
}());
</script>
@endpush
