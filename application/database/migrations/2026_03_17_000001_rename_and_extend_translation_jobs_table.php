<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('file_translation_jobs', 'translation_jobs');

        Schema::table('translation_jobs', function (Blueprint $table) {
            $table->string('type')->default('file')->after('provider');
            $table->text('input_text')->nullable()->after('type');
            $table->text('output_text')->nullable()->after('input_text');
            $table->string('original_filename')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('translation_jobs', function (Blueprint $table) {
            $table->dropColumn(['type', 'input_text', 'output_text']);
            $table->string('original_filename')->nullable(false)->change();
        });

        Schema::rename('translation_jobs', 'file_translation_jobs');
    }
};
