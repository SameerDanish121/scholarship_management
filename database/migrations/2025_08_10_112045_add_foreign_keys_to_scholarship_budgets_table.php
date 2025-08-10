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
        Schema::table('scholarship_budgets', function (Blueprint $table) {
            $table->foreign(['scholarship_id'], 'scholarship_budgets_ibfk_1')->references(['id'])->on('scholarships')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cost_category_id'], 'scholarship_budgets_ibfk_2')->references(['id'])->on('cost_categories')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_budgets', function (Blueprint $table) {
            $table->dropForeign('scholarship_budgets_ibfk_1');
            $table->dropForeign('scholarship_budgets_ibfk_2');
        });
    }
};
