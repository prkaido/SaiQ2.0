@extends('layouts.institucional')

@section('title', 'SaiQ - Datos de la Homologacion')

@section('content')
@php
    $equivalenciasInternas = collect($equivalenciasInternas ?? []);
@endphp
<section id="about" style="padding-top: 80px; padding-bottom: 80px;">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h3 style="font-size: 24px; margin-bottom: 25px; color: #ff0202; font-weight: 700; border-bottom: 3px solid #009ee3; padding-bottom: 15px;">Datos de la Homologaci&oacute;n</h3>

                <form method="post" action="{{ route('homologaciones.asignatura.result') }}" target="_blank">
                    @csrf
                    <input type="hidden" name="nom" value="{{ $data['nom'] }}">
                    <input type="hidden" name="ape" value="{{ $data['ape'] }}">
                    <input type="hidden" name="ide" value="{{ $data['ide'] }}">
                    <input type="hidden" name="per" value="{{ $data['per'] }}">
                    <input type="hidden" name="pca" value="{{ $data['pca'] }}">
                    <input type="hidden" name="pla" value="{{ $data['pla'] }}">
                    <input type="hidden" name="ins" value="{{ $data['ins'] }}">
                    <input type="hidden" name="pex" value="{{ $data['ext'] }}">
                    @if(!empty($data['cambio_pensum']))
                        <input type="hidden" name="cambio_pensum" value="1">
                    @endif
                    @if(!empty($data['homologar_ciclo_universitario']))
                        <input type="hidden" name="homologar_ciclo_universitario" value="1">
                        <input type="hidden" name="pca_ciclo_universitario" value="{{ $data['pca_ciclo_universitario'] }}">
                        <input type="hidden" name="pla_ciclo_universitario" value="{{ $data['pla_ciclo_universitario'] }}">
                    @endif
                    @if($homologacionId)
                        <input type="hidden" name="homologacion_id" value="{{ $homologacionId }}">
                    @endif
                    @if(!empty($cicloUniversitario['homologacionId']))
                        <input type="hidden" name="homologacion_ciclo_universitario_id" value="{{ $cicloUniversitario['homologacionId'] }}">
                    @endif

                    <div class="panel panel-default" style="margin-bottom: 30px; border: 2px solid #009ee3; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 158, 227, 0.1);">
                        <div class="panel-heading" style="background-color: #009ee3; padding: 15px; border-bottom: none; color: white; border-radius: 3px 3px 0 0;">
                            <h4 style="margin: 0; color: white; font-weight: 700; font-size: 16px;">Informaci&oacute;n del Estudiante</h4>
                        </div>
                        <div class="panel-body" style="padding: 20px;">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Nombre:</strong> {{ $data['nom'] }}</p>
                                    <p><strong>Per&iacute;odo:</strong> {{ $data['per'] }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Apellido:</strong> {{ $data['ape'] }}</p>
                                    <p><strong>Identificaci&oacute;n:</strong> {{ $data['ide'] }}</p>
                                </div>
                            </div>
                            <hr style="margin: 20px 0;">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Programa PCA:</strong> {{ $programaPca->nombre }}</p>
                                    <p><strong>Plan de Estudios:</strong> {{ $plan->num }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Instituci&oacute;n de Procedencia:</strong> {{ $institucion->nombre }}</p>
                                    <p><strong>Programa de Procedencia:</strong> {{ \App\Support\AcademicText::upper($data['ext']) }}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Tipo de Estudio:</strong></p>
                                    <select name="tip" class="form-control" style="max-width: 300px; border: 2px solid #ddd;">
                                        <option value="0" @selected(empty($data['cambio_pensum']))>Externa</option>
                                        <option value="1">Interna</option>
                                        <option value="2" @selected(!empty($data['cambio_pensum']))>Reintegro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default" style="border: 2px solid #ff0202; border-radius: 5px; box-shadow: 0 2px 4px rgba(255, 2, 2, 0.1);">
                        <div class="panel-heading" style="background-color: #ff0202; padding: 15px; border-bottom: none; color: white; border-radius: 3px 3px 0 0;">
                            <h4 style="margin: 0; color: white; font-weight: 700; font-size: 16px;">Equivalencias de Asignaturas</h4>
                        </div>
                        <div class="table-responsive" style="padding: 15px;">
                            <table class="table table-striped table-bordered" style="margin: 0; background-color: white;">
                                <thead style="background-color: #009ee3; color: white; font-weight: 700;">
                                <tr>
                                    <th style="text-align: center; width: 10%;">C&oacute;digo</th>
                                    <th style="text-align: center; width: 8%;">Sem.</th>
                                    <th style="text-align: center; width: 30%;">Asignatura PCA</th>
                                    <th style="text-align: center; width: 35%;">Asignatura de Procedencia</th>
                                    <th style="text-align: center; width: 12%;">Calificaci&oacute;n</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($asignaturas as $index => $asignatura)
                                    @php($equivalenciaInterna = $equivalenciasInternas->get($asignatura->id))
                                    <tr style="background-color: {{ $index % 2 === 0 ? '#f9f9f9' : '#ffffff' }};">
                                        <td style="text-align: center; font-weight: 600; padding: 10px;">{{ $asignatura->cod }}</td>
                                        <td style="text-align: center; padding: 10px;">{{ $asignatura->romano }}</td>
                                        <td style="padding: 10px;"><small>{{ $asignatura->nombre }}</small></td>
                                        <td style="padding: 10px;">
                                            <textarea name="t{{ $asignatura->id }}" class="form-control" rows="2" style="font-size: 12px; width: 100%; border: 2px solid #ddd; border-radius: 4px; padding: 8px;" placeholder="Ingrese asignatura">{{ $equivalenciaInterna ? $equivalenciaInterna->nombre . ' (' . $equivalenciaInterna->cod . ' - ' . ($equivalenciaInterna->plan_num ?? 'Plan anterior') . ')' : '' }}</textarea>
                                        </td>
                                        <td style="text-align: center; padding: 10px;">
                                            <input type="text" class="form-control" name="n{{ $asignatura->id }}" value="{{ $equivalenciaInterna ? $equivalenciaInterna->nota : '' }}" style="text-align: center; font-size: 12px; border: 2px solid #ddd; border-radius: 4px; padding: 8px;" placeholder="0.0" maxlength="5">
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(!empty($cicloUniversitario))
                        @php($equivalenciasInternasCiclo = collect($cicloUniversitario['equivalenciasInternas'] ?? []))
                        <div class="panel panel-default" style="border: 2px solid #555; border-radius: 5px; box-shadow: 0 2px 4px rgba(85, 85, 85, 0.1); margin-top: 30px;">
                            <div class="panel-heading" style="background-color: #555; padding: 15px; border-bottom: none; color: white; border-radius: 3px 3px 0 0;">
                                <h4 style="margin: 0; color: white; font-weight: 700; font-size: 16px;">Equivalencias para Ciclo Universitario - {{ $cicloUniversitario['programaPca']->nombre }}</h4>
                            </div>
                            <div class="table-responsive" style="padding: 15px;">
                                <table class="table table-striped table-bordered" style="margin: 0; background-color: white;">
                                    <thead style="background-color: #555; color: white; font-weight: 700;">
                                    <tr>
                                        <th style="text-align: center; width: 10%;">C&oacute;digo</th>
                                        <th style="text-align: center; width: 8%;">Sem.</th>
                                        <th style="text-align: center; width: 30%;">Asignatura PCA</th>
                                        <th style="text-align: center; width: 35%;">Asignatura de Procedencia</th>
                                        <th style="text-align: center; width: 12%;">Calificaci&oacute;n</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($cicloUniversitario['asignaturas'] as $index => $asignatura)
                                        @php($equivalenciaInternaCiclo = $equivalenciasInternasCiclo->get($asignatura->id))
                                        <tr style="background-color: {{ $index % 2 === 0 ? '#f9f9f9' : '#ffffff' }};">
                                            <td style="text-align: center; font-weight: 600; padding: 10px;">{{ $asignatura->cod }}</td>
                                            <td style="text-align: center; padding: 10px;">{{ $asignatura->romano }}</td>
                                            <td style="padding: 10px;"><small>{{ $asignatura->nombre }}</small></td>
                                            <td style="padding: 10px;">
                                                <textarea name="ct{{ $asignatura->id }}" class="form-control" rows="2" style="font-size: 12px; width: 100%; border: 2px solid #ddd; border-radius: 4px; padding: 8px;" placeholder="Ingrese asignatura">{{ $equivalenciaInternaCiclo ? $equivalenciaInternaCiclo->nombre . ' (' . $equivalenciaInternaCiclo->cod . ' - ' . ($equivalenciaInternaCiclo->plan_num ?? 'Plan anterior') . ')' : '' }}</textarea>
                                            </td>
                                            <td style="text-align: center; padding: 10px;">
                                                <input type="text" class="form-control" name="cn{{ $asignatura->id }}" value="{{ $equivalenciaInternaCiclo ? $equivalenciaInternaCiclo->nota : '' }}" style="text-align: center; font-size: 12px; border: 2px solid #ddd; border-radius: 4px; padding: 8px;" placeholder="0.0" maxlength="5">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div style="text-align: center; margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #f0f8ff 0%, #e8f4f8 100%); border-radius: 5px; border-top: 3px solid #009ee3;">
                        <button type="submit" class="btn btn-lg" style="padding: 15px 50px; font-size: 16px; font-weight: 700; background-color: #ff0202; color: white; border: 2px solid #ff0202; border-radius: 5px; cursor: pointer;">
                            Aceptar Equivalencias
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
