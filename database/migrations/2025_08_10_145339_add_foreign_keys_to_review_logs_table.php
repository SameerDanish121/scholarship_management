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
        Schema::table('review_logs', function (Blueprint $table) {
            $table->foreign(['admin_id'], 'fk_review_logs_admin')->references(['id'])->on('admins')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['application_id'], 'fk_review_logs_application')->references(['id'])->on('applications')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_logs', function (Blueprint $table) {
            $table->dropForeign('fk_review_logs_admin');
            $table->dropForeign('fk_review_logs_application');
        });
    }
};
