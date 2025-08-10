<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('application_award_allocations', function (Blueprint $table) {
            $table->foreign(['award_id'], 'fk_allocations_award')->references(['id'])->on('application_awards')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cost_category_id'], 'fk_allocations_cost_category')->references(['id'])->on('cost_categories')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_award_allocations', function (Blueprint $table) {
            $table->dropForeign('fk_allocations_award');
            $table->dropForeign('fk_allocations_cost_category');
        });
    }
};
