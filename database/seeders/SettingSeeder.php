<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'GuhangaAkazi',
            'site_description' => 'Discover business ideas, build practical skills and find opportunities.',
            'hero_heading' => 'Discover Opportunities. Build Skills. Create Your Future.',
            'hero_description' => 'Explore business ideas, develop practical skills, discover opportunities, and access the knowledge you need to build a better future.',
            'slider_interval' => '5000',
            'featured_limit' => '6',
        ];
        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
