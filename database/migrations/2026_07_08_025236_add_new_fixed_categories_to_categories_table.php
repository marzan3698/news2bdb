<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            'এআই ইনফরমেশন নিউজ',
            'সায়েন্স নিউজ',
            'এডুকেশনাল নিউজ',
            'স্পোর্টস নিউজ',
            'স্পোর্ট সায়েন্স নিউজ',
            'বিজনেস নিউজ',
            'বিজনেস সায়েন্স নিউজ',
            'নেচার নিউজ',
            'নেচার অ্যান্ড বোটানিক্যাল সায়েন্স নিউজ',
            'স্পেস সায়েন্স নিউজ',
            'ফিল্ম অ্যান্ড মিডিয়া',
            'ফিল্ম অ্যান্ড মিডিয়া সায়েন্স নিউজ'
        ];

        // Ensure order is maintained
        $maxOrder = DB::table('categories')->max('order') ?? 0;

        foreach ($categories as $index => $name) {
            $slug = Str::slug($name);
            // If slug generation for Bengali doesn't work well, we use a custom or english fallback
            if (empty($slug)) {
                $slug = 'cat-' . time() . '-' . $index;
            }

            // check if exists
            $exists = DB::table('categories')->where('name', $name)->exists();
            if (!$exists) {
                DB::table('categories')->insert([
                    'name' => $name,
                    'slug' => $slug,
                    'order' => $maxOrder + $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data inserts, or we could delete them
    }
};
