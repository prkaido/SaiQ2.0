<?php

namespace App\Services;

use App\Support\AcademicText;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class HomologacionBorradorService
{
    public function list(string $userId, int $userType, string $estado = 'borrador')
    {
        $query = $this->baseQuery();

        if ($userType !== 1) {
            $query->where('h.user_id', $userId);
        }

        if (in_array($estado, ['borrador', 'completado'], true)) {
            $query->where('h.estado', $estado);
        }

        return $this->hydrateNames($query
            ->orderByDesc('h.updated_at')
            ->orderByDesc('h.id')
            ->get());
    }

    public function findForUser(int $id, string $userId, int $userType): object
    {
        $query = $this->baseQuery()->where('h.id', $id);

        if ($userType !== 1) {
            $query->where('h.user_id', $userId);
        }

        $homologacion = $query->first();

        if (!$homologacion) {
            throw new RuntimeException('Homologacion no encontrada.');
        }

        return $this->hydrateNames(collect([$homologacion]))->first();
    }

    public function detalles(int $homologacionId)
    {
        if (!Schema::hasTable('homologacion_detalle')) {
            return collect();
        }

        return DB::table('homologacion_detalle')
            ->where('homologacion_id', $homologacionId)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    public function auditoria(int $homologacionId)
    {
        if (!Schema::hasTable('audit_log')) {
            return collect();
        }

        return DB::table('audit_log')
            ->where('homologacion_id', $homologacionId)
            ->orderByDesc(Schema::hasColumn('audit_log', 'created_at') ? 'created_at' : 'timestamp')
            ->get();
    }

    private function baseQuery(): Builder
    {
        return DB::table('homologacion as h')
            ->select('h.*');
    }

    private function hydrateNames($homologaciones)
    {
        $programCodes = $homologaciones
            ->flatMap(fn ($item) => [$item->programa_pca_cod ?? null, $item->programa_ext_cod ?? null])
            ->filter()
            ->unique()
            ->values();

        $institutionIds = $homologaciones
            ->pluck('institucion_id')
            ->filter()
            ->unique()
            ->values();

        $programas = $programCodes->isEmpty()
            ? collect()
            : DB::table('programa')
                ->whereIn('cod', $programCodes)
                ->pluck('nombre', 'cod')
                ->map(fn ($nombre) => AcademicText::nullableName($nombre));

        $instituciones = $institutionIds->isEmpty()
            ? collect()
            : DB::table('institucion')
                ->whereIn('id', $institutionIds)
                ->pluck('nombre', 'id')
                ->map(fn ($nombre) => AcademicText::nullableName($nombre));

        return $homologaciones->map(function ($item) use ($programas, $instituciones) {
            $programaExtNombre = AcademicText::nullableName($item->programa_ext_nombre ?? null);

            $item->programa_pca_nombre = AcademicText::nullableName($programas[$item->programa_pca_cod] ?? null);
            $item->programa_ext_nombre = $programaExtNombre ?: ($programas[$item->programa_ext_cod] ?? null);
            $item->institucion_nombre = AcademicText::nullableName($instituciones[$item->institucion_id] ?? null);

            return $item;
        });
    }
}
