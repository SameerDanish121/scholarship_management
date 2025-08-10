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
        Schema::table('application_awards', function (Blueprint $table) {
            $table->foreign(['application_id'], 'fk_awards_application')->references(['id'])->on('applications')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_awards', function (Blueprint $table) {
            $table->dropForeign('fk_awards_application');
        });
    }
};
