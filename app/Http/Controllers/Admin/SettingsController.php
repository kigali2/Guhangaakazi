<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const FIELDS = [
        'hero_heading' => ['Home page main heading', 'text', 'nullable|string|max:200'],
        'hero_description' => ['Home page description', 'textarea', 'nullable|string|max:600'],
        'featured_limit' => ['Items shown in each home section (1 to 12)', 'number', 'nullable|integer|min:1|max:12'],
        'about_content' => ['About Us page text', 'textarea', 'nullable|string|max:20000'],
        'mission_content' => ['Mission page text', 'textarea', 'nullable|string|max:20000'],
        'vision_content' => ['Vision page text', 'textarea', 'nullable|string|max:20000'],
        'privacy_content' => ['Privacy Policy text', 'textarea', 'nullable|string|max:20000'],
        'terms_content' => ['Terms of Use text', 'textarea', 'nullable|string|max:20000'],
    ];

    public function index()
    {
        $defaults = [
            'hero_heading' => config('site.tagline'),
            'hero_description' => config('site.description'),
            'featured_limit' => '6',
            'about_content' => config('site.pages.about'),
            'mission_content' => config('site.pages.mission'),
            'vision_content' => config('site.pages.vision'),
            'privacy_content' => config('site.pages.privacy'),
            'terms_content' => config('site.pages.terms'),
        ];

        return view('admin.settings.index', [
            'fields' => self::FIELDS,
            'values' => Setting::query()->pluck('value', 'key'),
            'defaults' => $defaults,
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::FIELDS as $key => $field) {
            $rules[$key] = $field[2];
        }
        $data = $request->validate($rules);
        foreach ($data as $k => $v) { if ($v && str_ends_with($k, "_content")) { $data[$k] = \App\Support\Html::clean($v); } }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Settings saved. The website is updated.');
    }
}
