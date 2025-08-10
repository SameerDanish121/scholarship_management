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
        Schema::create('scholarship_budgets', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('scholarship_id')->index('idx_scholarship');
            $table->integer('cost_category_id')->index('cost_category_id');
            $table->decimal('planned_amount', 10)->default(0);
            $table->decimal('committed_amount', 10)->default(0);
            $table->decimal('disbursed_amount', 10)->default(0);
            $table->decimal('receipted_amount', 10)->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();

            $table->unique(['scholarship_id', 'cost_category_id'], 'unique_scholarship_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_budgets');
    }
};
