<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminCatalogService
{
    public function institutionAbbrev(string $name): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $name));

        if (strlen($clean) < 2) {
            throw new RuntimeException('El nombre de la institucion debe permitir generar abreviatura.');
        }

        $first = substr($clean, 0, 1);
        for ($i = 1; $i < strlen($clean); $i++) {
            $abbr = $first . substr($clean, $i, 1);
            if (!DB::table('institucion')->where('abrev', $abbr)->exists()) {
                return $abbr;
            }
        }

        return substr($clean, 0, 2) . random_int(1, 9);
    }

    public function nextProgramCode(int $institutionId): string
    {
        $institution = DB::table('institucion')->where('id', $institutionId)->first();

        if (!$institution || !$institution->abrev) {
            throw new RuntimeException('Institucion no encontrada o sin abreviatura.');
        }

        $max = DB::table('programa')
            ->where('inst', (string) $institutionId)
            ->pluck('cod')
            ->map(fn ($code) => (int) substr((string) $code, 2))
            ->max() ?: 0;

        return $institution->abrev . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    public function nextCourseCode(string $programCode): string
    {
        $max = DB::table('asignatura')
            ->where('programa', $programCode)
            ->pluck('cod')
            ->map(function ($code) use ($programCode) {
                return (int) substr((string) $code, strlen($programCode));
            })
            ->max() ?: 0;

        return $programCode . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    public function storeSignature(?UploadedFile $file, string $programCode, string $userId): ?string
    {
        if (!$file) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            throw new RuntimeException('Solo se permiten firmas JPG o PNG.');
        }

        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $programCode . '_' . $userId) . '_' . time() . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
        $file->move(public_path('img/firmas'), $name);

        return 'img/firmas/' . $name;
    }

    public function audit(string $action, array $details = []): void
    {
        try {
            DB::table('audit_log')->insert([
                'user_id' => session('x'),
                'action' => $action,
                'details' => json_encode($details),
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Compatible con instalaciones sin auditoria.
        }
    }
}
