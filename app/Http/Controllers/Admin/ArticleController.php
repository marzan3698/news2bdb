<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use App\Models\AiSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ArticleController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return view('admin.articles.index', compact('categories'));
    }

    public function data()
    {
        $articles = Article::with('category', 'user')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $articles]);
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'content' => 'required'
        ]);

        $article = new Article();
        $article->title = $request->title;
        $article->slug = Str::slug($request->title) . '-' . time();
        $article->content = $request->content;
        $article->category_id = $request->category_id;
        $article->user_id = auth()->id();
        $article->status = 'published';
        $article->save();

        return redirect()->route('admin.articles.index')->with('success', 'Article created successfully.');
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        // Delete local image if stored in storage
        if ($article->image_url && str_starts_with($article->image_url, '/storage/articles/')) {
            $path = str_replace('/storage/', '', $article->image_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        $article->delete();
        return response()->json(['success' => true, 'message' => 'Article deleted.']);
    }

    public function autoGenerate(Request $request)
    {
        $service = new \App\Services\NewsGeneratorService();

        $categoryIds = $request->input('category_ids', []);
        $userId = auth()->id();

        $result = $service->generate($categoryIds, $userId);

        return response()->json($result);
    }
}
