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
        Schema::create('scholarships', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('application_deadline')->index('idx_deadline');
            $table->decimal('award_amount', 10)->nullable();
            $table->decimal('total_budget', 12)->nullable();
            $table->integer('max_awards')->nullable()->default(1);
            $table->enum('status', ['in-active', 'active'])->nullable()->default('active')->index('idx_status');
            $table->text('eligibility_criteria')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->integer('created_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
