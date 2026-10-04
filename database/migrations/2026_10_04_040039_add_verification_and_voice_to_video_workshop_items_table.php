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
        Schema::table('video_workshop_items', function (Blueprint $table) {
            $table->string('voice_tone')->default('bn-BD-PradeepNeural')->after('voiceover_path');
            $table->json('verification_data')->nullable()->after('extracted_frames');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_workshop_items', function (Blueprint $table) {
            $table->dropColumn(['voice_tone', 'verification_data']);
        });
    }
};
