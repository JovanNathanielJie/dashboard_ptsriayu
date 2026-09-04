<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE analysis_runs MODIFY COLUMN status ENUM('uploaded', 'processing', 'menunggu_validasi', 'disetujui', 'ditolak', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");

        Schema::table('analysis_runs', function (Blueprint $table) {
            $table->foreignId('validated_by')->nullable()->after('status')->constrained('users');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('catatan_validasi')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_runs', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validated_by', 'validated_at', 'catatan_validasi']);
        });

        DB::statement("ALTER TABLE analysis_runs MODIFY COLUMN status ENUM('uploaded', 'processing', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");
    }
};
