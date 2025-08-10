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
        Schema::table('applications', function (Blueprint $table) {
            $table->foreign(['scholarship_id'], 'fk_applications_scholarship')->references(['id'])->on('scholarships')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['student_id'], 'fk_applications_student')->references(['id'])->on('students')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign('fk_applications_scholarship');
            $table->dropForeign('fk_applications_student');
        });
    }
};
