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
        Schema::create('disbursement_schedules', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('scholarship_id')->index('idx_scholarship');
            $table->integer('cost_category_id')->index('cost_category_id');
            $table->unsignedBigInteger('award_allocation_id')->index('fk_disbursement_schedules_award_allocation');
            $table->date('scheduled_date')->index('idx_scheduled_date');
            $table->decimal('scheduled_amount', 10);
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'ready', 'completed', 'cancelled'])->nullable()->default('pending')->index('idx_status');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursement_schedules');
    }
};
