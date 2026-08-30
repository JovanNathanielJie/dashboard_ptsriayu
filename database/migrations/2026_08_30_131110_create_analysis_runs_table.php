<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_file_upload');
            $table->date('periode_awal')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->decimal('min_support', 5, 4)->default(0.10);
            $table->unsignedTinyInteger('max_len')->default(2);
            $table->decimal('min_confidence', 5, 4)->default(0.60);
            $table->unsignedInteger('total_baris_raw')->nullable();
            $table->unsignedInteger('total_baris_clean')->nullable();
            $table->unsignedInteger('total_faktur_unik')->nullable();
            $table->unsignedInteger('total_produk_unik')->nullable();
            $table->unsignedInteger('total_frequent_itemsets')->nullable();
            $table->unsignedInteger('total_association_rules')->nullable();
            $table->enum('status', ['uploaded', 'processing', 'done', 'failed'])->default('uploaded');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['periode_awal', 'periode_akhir']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_runs');
    }
};
