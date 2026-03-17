<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_translation_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('status')->default('pending');
            $table->string('source_language', 10);
            $table->string('target_language', 10);
            $table->json('options')->nullable();
            $table->string('original_filename');
            $table->string('external_request_id')->nullable();
            $table->string('translated_storage_path')->nullable();
            $table->text('error_message')->nullable();
            $table->string('institution_user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_translation_jobs');
    }
};
