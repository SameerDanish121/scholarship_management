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
        Schema::create('disbursements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('award_allocation_id')->index('fk_disbursements_allocation');
            $table->decimal('amount', 10);
            $table->date('disbursement_date');
            $table->string('reference_number', 100)->unique('reference_number');
            $table->string('idempotency_key')->nullable()->unique('idempotency_key');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
