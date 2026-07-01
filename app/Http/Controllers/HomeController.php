<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Category;
use App\Models\Article;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::orderBy('order', 'asc')->get();

        // 1. Featured Lead News (Always the latest published article)
        $featured_article = Article::where('status', 'published')
            ->latest()
            ->first();

        // Exclude featured article from other lists
        $excluded_ids = $featured_article ? [$featured_article->id] : [];

        // 2. Latest Articles (for Ticker)
        $latest_articles = Article::where('status', 'published')
            ->latest()
            ->take(10)
            ->get();

        // 3. Prime Minister's Corner Articles
        $pm_articles = Article::where('status', 'published')
            ->where(function ($query) {
                $query->where('title', 'like', '%প্রধানমন্ত্রী%')
                      ->orWhere('title', 'like', '%জিয়াউর%')
                      ->orWhere('title', 'like', '%স্বাবলম্বী%');
            })
            ->latest()
            ->take(3)
            ->get();

        $pm_corner_lead = $pm_articles->first();
        $pm_corner_subs = $pm_articles->slice(1);

        // Add PM articles to excluded list for the general middle column if they are shown there
        $pm_ids = $pm_articles->pluck('id')->toArray();
        $all_excluded_ids = array_merge($excluded_ids, $pm_ids);

        // 4. Middle Column Articles (Secondary News list)
        $middle_articles = Article::where('status', 'published')
            ->whereNotIn('id', $all_excluded_ids)
            ->latest()
            ->take(4)
            ->get();

        // 5. Location Search Filter
        $selected_division = $request->query('division');
        $selected_district = $request->query('district');
        $filtered_articles = null;

        if ($selected_division || $selected_district) {
            $query = Article::where('status', 'published');
            if ($selected_division) {
                $query->where('division', $selected_division);
            }
            if ($selected_district) {
                $query->where('district', $selected_district);
            }
            $filtered_articles = $query->latest()->get();
        }

        // Static lists for the search dropdown
        $divisions = ['ঢাকা', 'চট্টগ্রাম', 'রাজশাহী', 'খুলনা', 'বরিশাল', 'সিলেট', 'রংপুর', 'ময়মনসিংহ'];
        $districts = ['ঢাকা', 'গাজীপুর', 'নারায়ণগঞ্জ', 'চট্টগ্রাম', 'কক্সবাজার', 'সিলেট', 'বগুড়া', 'খুলনা', 'রংপুর', 'বরিশাল'];

        // 6. Category-specific article groups for rows
        $sarabangla_articles = Article::where('status', 'published')
            ->whereHas('category', function($q) { $q->where('slug', 'sarabangla'); })
            ->latest()
            ->take(4)
            ->get();

        $politics_articles = Article::where('status', 'published')
            ->whereHas('category', function($q) { $q->where('slug', 'politics'); })
            ->latest()
            ->take(4)
            ->get();

        $sports_articles = Article::where('status', 'published')
            ->whereHas('category', function($q) { $q->where('slug', 'sports'); })
            ->latest()
            ->take(4)
            ->get();

        $entertainment_articles = Article::where('status', 'published')
            ->whereHas('category', function($q) { $q->where('slug', 'entertainment'); })
            ->latest()
            ->take(4)
            ->get();

        $tech_articles = Article::where('status', 'published')
            ->whereHas('category', function($q) { $q->where('slug', 'technology'); })
            ->latest()
            ->take(4)
            ->get();

        $lifestyle_articles = Article::where('status', 'published')
            ->whereHas('category', function($q) { $q->where('slug', 'lifestyle'); })
            ->latest()
            ->take(4)
            ->get();

        return view('welcome', compact(
            'categories',
            'featured_article',
            'latest_articles',
            'pm_corner_lead',
            'pm_corner_subs',
            'middle_articles',
            'filtered_articles',
            'selected_division',
            'selected_district',
            'divisions',
            'districts',
            'politics_articles',
            'sports_articles',
            'entertainment_articles',
            'tech_articles',
            'lifestyle_articles',
            'sarabangla_articles'
        ));
    }

    public function show($slug)
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        // Pass categories for the frontend header
        $categories = Category::orderBy('order', 'asc')->get();

        // 2. Latest Articles (for Ticker and sidebar)
        $latest_articles = Article::where('status', 'published')
            ->latest()
            ->take(10)
            ->get();

        return view('news.show', compact('article', 'latest_articles', 'categories'));
    }
}
