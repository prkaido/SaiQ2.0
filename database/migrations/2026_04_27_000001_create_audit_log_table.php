<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_log')) {
            Schema::create('audit_log', function (Blueprint $table) {
                $table->id('id_log');
                $table->unsignedInteger('homologacion_id')->nullable()->index();
                $table->string('user_id', 50)->nullable()->index();
                $table->string('action', 80)->index();
                $table->string('tabla', 80)->nullable();
                $table->longText('campo_anterior')->nullable();
                $table->longText('campo_nuevo')->nullable();
                $table->longText('details')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('timestamp')->nullable()->useCurrent();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });

            return;
        }

        Schema::table('audit_log', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_log', 'homologacion_id')) {
                $table->unsignedInteger('homologacion_id')->nullable()->index();
            }
            if (!Schema::hasColumn('audit_log', 'tabla')) {
                $table->string('tabla', 80)->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'campo_anterior')) {
                $table->longText('campo_anterior')->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'campo_nuevo')) {
                $table->longText('campo_nuevo')->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'details')) {
                $table->longText('details')->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'ip')) {
                $table->string('ip', 45)->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            if (!Schema::hasColumn('audit_log', 'timestamp')) {
                $table->timestamp('timestamp')->nullable()->useCurrent();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
