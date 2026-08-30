<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frequent_itemsets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->text('itemset');
            $table->unsignedTinyInteger('length');
            $table->decimal('support', 8, 6);
            $table->timestamps();

            $table->index(['analysis_run_id', 'length']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequent_itemsets');
    }
};
