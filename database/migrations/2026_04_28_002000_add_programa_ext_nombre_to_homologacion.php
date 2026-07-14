<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homologacion') || Schema::hasColumn('homologacion', 'programa_ext_nombre')) {
            return;
        }

        Schema::table('homologacion', function (Blueprint $table) {
            $table->string('programa_ext_nombre', 150)->nullable()->after('programa_ext_cod');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('homologacion') || !Schema::hasColumn('homologacion', 'programa_ext_nombre')) {
            return;
        }

        Schema::table('homologacion', function (Blueprint $table) {
            $table->dropColumn('programa_ext_nombre');
        });
    }
};
