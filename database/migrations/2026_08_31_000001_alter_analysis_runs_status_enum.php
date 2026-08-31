<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $database = DB::connection()->getDriverName();

        if ($database === 'sqlite') {
            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->string('status_tmp')->nullable();
            });

            DB::statement("UPDATE analysis_runs SET status_tmp = status");

            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->enum('status', ['uploaded', 'queued', 'processing', 'done', 'failed'])->default('uploaded');
            });

            DB::statement("UPDATE analysis_runs SET status = status_tmp");

            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->dropColumn('status_tmp');
            });

            return;
        }

        DB::statement("ALTER TABLE analysis_runs MODIFY status ENUM('uploaded', 'queued', 'processing', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $database = DB::connection()->getDriverName();

        if ($database === 'sqlite') {
            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->string('status_tmp')->nullable();
            });

            DB::statement("UPDATE analysis_runs SET status_tmp = status");

            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->enum('status', ['uploaded', 'processing', 'done', 'failed'])->default('uploaded');
            });

            DB::statement("UPDATE analysis_runs SET status = status_tmp");

            Schema::table('analysis_runs', function (Blueprint $table) {
                $table->dropColumn('status_tmp');
            });

            return;
        }

        DB::statement("ALTER TABLE analysis_runs MODIFY status ENUM('uploaded', 'processing', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");
    }
};
