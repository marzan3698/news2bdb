<?php

namespace App\Console\Commands;

use App\Services\NewsGeneratorService;
use Illuminate\Console\Command;

class GenerateNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:generate
                            {--count=1 : Number of articles to generate}
                            {--category=* : Specific category IDs to target}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate news articles using AI (Gemini + RSS sources)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $categoryIds = $this->option('category');

        // Convert string category IDs to integers
        $categoryIds = array_filter(array_map('intval', $categoryIds));

        $this->info("🚀 Starting AI news generation...");
        $this->info("   Target: {$count} article(s)");

        if (!empty($categoryIds)) {
            $this->info("   Categories: " . implode(', ', $categoryIds));
        } else {
            $this->info("   Categories: All (rotating)");
        }

        $this->newLine();

        $service = new NewsGeneratorService();
        $successCount = 0;
        $failCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $this->info("📰 Generating article {$i}/{$count}...");

            $result = $service->generate($categoryIds, 1); // user_id = 1 (admin) for CLI

            if ($result['success']) {
                $successCount++;
                $article = $result['article'];
                $this->info("   ✅ {$article->title}");
                $this->info("   📂 Category: {$article->category->name}");

                if ($article->source_name && $article->source_name !== 'AI Generated') {
                    $this->info("   📡 Source: {$article->source_name}");
                } else {
                    $this->info("   🤖 Source: AI Generated");
                }
            } else {
                $failCount++;
                $this->error("   ❌ Failed: {$result['message']}");
            }

            // Small delay between generations to avoid rate limiting
            if ($i < $count) {
                $this->info("   ⏳ Waiting 3 seconds...");
                sleep(3);
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Results: {$successCount} success, {$failCount} failed");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
