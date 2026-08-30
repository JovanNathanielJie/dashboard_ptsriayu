<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->string('nomor_faktur');
            $table->date('tanggal');
            $table->string('nama_barang');
            $table->timestamps();

            $table->index(['analysis_run_id', 'nomor_faktur']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_items');
    }
};
