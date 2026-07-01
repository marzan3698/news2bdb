<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSource;
use Illuminate\Http\Request;

class AiSourceController extends Controller
{
    public function index()
    {
        $sources = AiSource::orderBy('id', 'desc')->get();
        return view('admin.ai-sources.index', compact('sources'));
    }

    public function create()
    {
        return view('admin.ai-sources.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string',
            'type' => 'required|in:rss,facebook,scraping',
            'status' => 'nullable|boolean',
        ]);

        $source = new AiSource();
        $source->name = $request->name;
        $source->url = $request->url;
        $source->type = $request->type;
        $source->status = $request->has('status') ? 1 : 0;
        $source->save();

        return redirect()->route('admin.ai-sources.index')->with('success', 'AI Source created successfully.');
    }

    public function edit($id)
    {
        $source = AiSource::findOrFail($id);
        return view('admin.ai-sources.edit', compact('source'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string',
            'type' => 'required|in:rss,facebook,scraping',
            'status' => 'nullable|boolean',
        ]);

        $source = AiSource::findOrFail($id);
        $source->name = $request->name;
        $source->url = $request->url;
        $source->type = $request->type;
        $source->status = $request->has('status') ? 1 : 0;
        $source->save();

        return redirect()->route('admin.ai-sources.index')->with('success', 'AI Source updated successfully.');
    }

    public function destroy($id)
    {
        $source = AiSource::findOrFail($id);
        $source->delete();

        return redirect()->route('admin.ai-sources.index')->with('success', 'AI Source deleted successfully.');
    }
}
