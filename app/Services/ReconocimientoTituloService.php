<?php

namespace App\Services;

use App\Support\AcademicText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ReconocimientoTituloService
{
    public function formOptions(): array
    {
        return [
            'periodo' => DB::table('periodo')->where('activo', 1)->first(),
            'programasPca' => AcademicText::cleanCollection(DB::table('programa')
                ->where('enpca', 1)
                ->where('activo', 1)
                ->where('nivel', 3)
                ->orderBy('nombre')
                ->get(), ['nombre']),
            'programasReconocimiento' => $this->programasReconocimiento(),
            'instituciones' => $this->institucionesReconocimiento(),
        ];
    }

    public function generate(array $data, string $userId): array
    {
        if ($this->esProgramaProcedencia($data['ext'])) {
            throw new RuntimeException('El programa seleccionado pertenece a homologacion de procedencia. Seleccione un programa para reconocimiento de titulo.');
        }

        $programaPca = DB::table('programa')->where('cod', $data['pca'])->first();
        $institucion = DB::table('institucion')->where('id', $data['ins'])->first();
        $programaExt = DB::table('programa')->where('cod', $data['ext'])->first();
        $firma = DB::table('usuario')->where('id', $userId)->value('firma') ?: '';

        if (!$programaPca || !$institucion || !$programaExt) {
            throw new RuntimeException('No fue posible encontrar los datos academicos seleccionados.');
        }

        AcademicText::cleanObject($programaPca, ['nombre']);
        AcademicText::cleanObject($institucion, ['nombre']);
        AcademicText::cleanObject($programaExt, ['nombre']);

        return [
            'data' => $data,
            'programaPca' => $programaPca,
            'institucion' => $institucion,
            'programaExt' => $programaExt,
            'firma' => $firma,
            'fecha' => now()->format('Y-m-d'),
        ];
    }

    public function tratamiento(int $value): string
    {
        return $value === 1 ? 'El Se&ntilde;or:' : 'La Se&ntilde;ora (ita):';
    }

    public function documento(int $value): string
    {
        return match ($value) {
            0 => 'C&eacute;dula de Ciudadan&iacute;a',
            1 => 'C&eacute;dula de Extranjer&iacute;a',
            2 => 'PEP',
            3 => 'Pasaporte',
            default => 'Tarjeta de Identidad',
        };
    }

    private function programasReconocimiento()
    {
        $query = DB::table('programa')
            ->where('programa.enpca', 0)
            ->where('programa.cod', 'not like', 'SE%')
            ->orderBy('programa.nombre')
            ->select('programa.*');

        if (Schema::hasTable('programa_procedencia_relacion')) {
            $query->leftJoin('programa_procedencia_relacion as rel', function ($join) {
                $join->on('rel.programa_ext_cod', '=', 'programa.cod')
                    ->where('rel.activo', 1);
            })
                ->whereNull('rel.programa_ext_cod');
        }

        return AcademicText::cleanCollection($query->get(), ['nombre']);
    }

    private function institucionesReconocimiento()
    {
        return AcademicText::cleanCollection(DB::table('institucion')
            ->orderBy('nombre')
            ->get(), ['nombre']);
    }

    private function esProgramaProcedencia(string $codigo): bool
    {
        if (str_starts_with(strtoupper($codigo), 'SE')) {
            return true;
        }

        if (!Schema::hasTable('programa_procedencia_relacion')) {
            return false;
        }

        return DB::table('programa_procedencia_relacion')
            ->where('programa_ext_cod', $codigo)
            ->where('activo', 1)
            ->exists();
    }
}
