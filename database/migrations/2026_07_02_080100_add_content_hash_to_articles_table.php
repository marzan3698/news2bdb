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
        Schema::table('articles', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->index()->after('source_name');
            $table->text('source_url')->nullable()->after('content_hash');
            $table->string('meta_description', 300)->nullable()->after('source_url');
            $table->json('tags')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['content_hash']);
            $table->dropColumn(['content_hash', 'source_url', 'meta_description', 'tags']);
        });
    }
};
