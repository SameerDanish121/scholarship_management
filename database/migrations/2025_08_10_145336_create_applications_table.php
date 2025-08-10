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
        Schema::create('applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('scholarship_id')->index('fk_applications_scholarship');
            $table->unsignedBigInteger('student_id');
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected'])->nullable()->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['student_id', 'scholarship_id'], 'unique_student_scholarship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
