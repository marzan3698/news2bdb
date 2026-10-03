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
        Schema::create('video_workshop_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('জাতীয়');
            $table->string('source_type')->default('youtube'); // youtube, direct_url, upload
            $table->text('source_url');
            $table->string('youtube_video_id')->nullable();
            $table->string('channel_name')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Trimming & Branding settings
            $table->integer('trim_start')->default(5); // cut first X seconds
            $table->integer('trim_end')->default(10);  // cut last Y seconds
            $table->string('branding_type')->default('both'); // watermark, banner, both, none
            $table->string('watermark_position')->default('top_right'); // top_right, top_left, bottom_right, bottom_left
            $table->string('branding_text')->nullable(); // e.g. BDB NEWS • সত্যের সন্ধানে সার্বক্ষণিক
            
            // File paths
            $table->string('original_video_path')->nullable();
            $table->string('processed_video_path')->nullable();
            
            // Workflow status
            $table->enum('status', ['pending', 'downloading', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('status_message')->nullable();
            $table->text('error_message')->nullable();
            
            // Facebook Post Status
            $table->boolean('auto_post_facebook')->default(false);
            $table->enum('facebook_status', ['none', 'posting', 'posted', 'failed'])->default('none');
            $table->string('facebook_post_id')->nullable();
            $table->text('facebook_video_url')->nullable();
            $table->timestamp('facebook_posted_at')->nullable();
            $table->text('facebook_error')->nullable();
            
            // Integration with website articles
            $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_workshop_items');
    }
};
