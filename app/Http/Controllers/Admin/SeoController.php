<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SeoController extends Controller
{
    public function index()
    {
        $values = Setting::query()->pluck('value', 'key');
        $image = $values['seo_image'] ?? null;

        return view('admin.seo.index', [
            'values' => $values,
            'imageUrl' => $image ? R2::url($image) : '',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
            'seo_image' => 'nullable|string|max:600',
            'gsc_verification' => 'nullable|string|max:400',
            'ga_measurement_id' => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Za-z0-9]{4,20}$/'],
        ]);

        $image = null;
        if (! empty($data['seo_image'])) {
            $image = R2::keyFromUrl($data['seo_image']);
            if (! $image) {
                throw ValidationException::withMessages([
                    'seo_image' => 'Paste a link from your R2 public URL (' . config('r2.url') . ').',
                ]);
            }
        }

        $gsc = $data['gsc_verification'] ?? null;
        if ($gsc && preg_match('/content=["\']([^"\']+)["\']/', $gsc, $m)) {
            $gsc = $m[1];
        }
        if ($gsc && ! preg_match('/^[A-Za-z0-9_-]{10,120}$/', $gsc)) {
            throw ValidationException::withMessages([
                'gsc_verification' => 'Paste only the verification code from Google (letters, numbers, - and _).',
            ]);
        }

        $values = [
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'seo_image' => $image,
            'gsc_verification' => $gsc,
            'ga_measurement_id' => empty($data['ga_measurement_id']) ? null : strtoupper($data['ga_measurement_id']),
        ];
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'SEO settings saved.');
    }
}
