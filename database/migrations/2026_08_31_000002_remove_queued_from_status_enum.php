<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revert status enum ke nilai asli: 'uploaded', 'processing', 'done', 'failed'
     * Menghapus 'queued' karena sudah beralih ke arsitektur Python FastAPI
     */
    public function up(): void
    {
        $database = DB::connection()->getDriverName();

        if ($database === 'sqlite') {
            DB::statement("
                CREATE TABLE analysis_runs_new AS
                SELECT * FROM analysis_runs WHERE status IN ('uploaded', 'processing', 'done', 'failed')
            ");

            DB::statement("DROP TABLE analysis_runs");
            DB::statement("ALTER TABLE analysis_runs_new RENAME TO analysis_runs");

            return;
        }

        // MySQL: Ubah enum status tanpa 'queued'
        DB::statement("ALTER TABLE analysis_runs MODIFY status ENUM('uploaded', 'processing', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");
    }

    /**
     * Reverse (jika perlu rollback): Tambah kembali 'queued'
     */
    public function down(): void
    {
        $database = DB::connection()->getDriverName();

        if ($database === 'sqlite') {
            return; // SQLite tidak mudah rollback, skip
        }

        DB::statement("ALTER TABLE analysis_runs MODIFY status ENUM('uploaded', 'queued', 'processing', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");
    }
};
