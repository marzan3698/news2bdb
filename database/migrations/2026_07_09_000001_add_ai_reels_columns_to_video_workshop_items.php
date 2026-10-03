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
            $table->string('ai_mode')->default('reels')->after('category'); // reels, direct
            $table->string('video_format')->default('vertical')->after('ai_mode'); // vertical (9:16), horizontal (16:9)
            $table->text('narration_script')->nullable()->after('branding_text');
            $table->string('voiceover_path')->nullable()->after('narration_script');
            $table->json('extracted_frames')->nullable()->after('voiceover_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_workshop_items', function (Blueprint $table) {
            $table->dropColumn(['ai_mode', 'video_format', 'narration_script', 'voiceover_path', 'extracted_frames']);
        });
    }
};
