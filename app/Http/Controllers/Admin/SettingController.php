<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    public function aiSettings()
    {
        $gemini_api_key = Setting::where('key', 'gemini_api_key')->value('value');
        $facebook_enabled = Setting::where('key', 'facebook_enabled')->value('value') ?? '0';
        $facebook_app_id = Setting::where('key', 'facebook_app_id')->value('value');
        $facebook_app_secret = Setting::where('key', 'facebook_app_secret')->value('value');
        $facebook_page_access_token = Setting::where('key', 'facebook_page_access_token')->value('value');
        $image_mode = Setting::where('key', 'image_mode')->value('value') ?? 'real';

        return view('admin.settings.ai', compact(
            'gemini_api_key',
            'facebook_enabled',
            'facebook_app_id',
            'facebook_app_secret',
            'facebook_page_access_token',
            'image_mode'
        ));
    }

    public function saveAiSettings(Request $request)
    {
        $request->validate([
            'gemini_api_key' => 'nullable|string',
            'facebook_enabled' => 'nullable|string',
            'facebook_app_id' => 'nullable|string',
            'facebook_app_secret' => 'nullable|string',
            'facebook_page_access_token' => 'nullable|string',
            'image_mode' => 'nullable|in:auto,real,animation',
        ]);

        Setting::updateOrCreate(['key' => 'gemini_api_key'], ['value' => $request->gemini_api_key]);
        Setting::updateOrCreate(['key' => 'facebook_enabled'], ['value' => $request->has('facebook_enabled') ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'facebook_app_id'], ['value' => $request->facebook_app_id]);
        Setting::updateOrCreate(['key' => 'facebook_app_secret'], ['value' => $request->facebook_app_secret]);
        Setting::updateOrCreate(['key' => 'facebook_page_access_token'], ['value' => $request->facebook_page_access_token]);
        Setting::updateOrCreate(['key' => 'image_mode'], ['value' => $request->image_mode ?? 'real']);

        return redirect()->back()->with('success', 'AI and Integration Settings updated successfully.');
    }

    public function testGemini(Request $request)
    {
        $apiKey = Setting::where('key', 'gemini_api_key')->value('value');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API Key not found. Please save the API Key first.'
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Write a short breaking news report about Bangladesh in Bengali. The very first line MUST be the Headline. The rest should be a short paragraph of 2-3 sentences.']
                        ]
                    ]
                ]
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $fullText = trim($data['candidates'][0]['content']['parts'][0]['text']);
                
                // Extract heading (first line) and body (the rest)
                $lines = explode("\n", $fullText);
                $heading = array_shift($lines);
                // Remove formatting like markdown bold if present
                $heading = str_replace(['**', '##', '#'], '', $heading);
                $bodyText = trim(implode("\n", $lines));
                
                // Since Gemini API text models don't generate images, we use a free AI image generator (Pollinations.ai) to fulfill the image requirement.
                $imageUrl = 'https://image.pollinations.ai/prompt/breaking%20news%20event%20in%20bangladesh%20realistic%20photojournalism%20high%20quality';

                return response()->json([
                    'success' => true,
                    'heading' => trim($heading),
                    'text' => $bodyText,
                    'image' => $imageUrl,
                    'message' => 'Text generated successfully by Gemini! (Note: Since standard Gemini API does not generate images, the image below is generated via Pollinations AI as a fallback).'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $data['error']['message'] ?? 'Unknown error occurred from Gemini API.',
                'details' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function generalSettings()
    {
        $site_title = Setting::where('key', 'site_title')->value('value') ?? 'বিডিবি নিউজ';
        $site_description = Setting::where('key', 'site_description')->value('value') ?? 'সত্যের সন্ধানে সার্বক্ষণিক';
        $site_logo = Setting::where('key', 'site_logo')->value('value');

        return view('admin.settings.general', compact('site_title', 'site_description', 'site_logo'));
    }

    public function saveGeneralSettings(Request $request)
    {
        $request->validate([
            'site_title' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        Setting::updateOrCreate(['key' => 'site_title'], ['value' => $request->site_title]);
        Setting::updateOrCreate(['key' => 'site_description'], ['value' => $request->site_description]);

        if ($request->hasFile('site_logo')) {
            $image = $request->file('site_logo');
            $name = 'logo_' . time() . '.' . $image->getClientOriginalExtension();
            
            // Store logo under storage/app/public/site
            $image->storeAs('site', $name, 'public');
            
            $logoPath = '/storage/site/' . $name;
            Setting::updateOrCreate(['key' => 'site_logo'], ['value' => $logoPath]);
        }

        return redirect()->back()->with('success', 'General Settings updated successfully.');
    }
}
