<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditService
{
    public function log(
        string $action,
        array $details = [],
        ?int $homologacionId = null,
        string $table = 'homologacion',
        ?array $before = null,
        ?array $after = null
    ): void {
        if (!Schema::hasTable('audit_log')) {
            return;
        }

        try {
            $columns = Schema::getColumnListing('audit_log');
            $payload = [
                'homologacion_id' => $homologacionId,
                'user_id' => session('x'),
                'action' => $action,
                'tabla' => $table,
                'campo_anterior' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'campo_nuevo' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'ip' => request()->ip(),
                'ip_address' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
                'timestamp' => now(),
                'created_at' => now(),
            ];

            DB::table('audit_log')->insert(array_intersect_key($payload, array_flip($columns)));
        } catch (\Throwable $exception) {
            // La auditoria no debe bloquear el flujo academico.
        }
    }
}
