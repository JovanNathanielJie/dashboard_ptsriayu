<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->text('antecedent');
            $table->text('consequent');
            $table->decimal('support', 8, 6);
            $table->decimal('confidence', 8, 6);
            $table->decimal('lift', 8, 6);
            $table->timestamps();

            $table->index(['analysis_run_id', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_rules');
    }
};
