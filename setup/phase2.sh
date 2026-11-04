set -e
PHP=/usr/bin/php8.4
mkdir -p app/Models/Concerns database/seeders

cat > database/migrations/2026_01_01_000001_create_guhanga_tables.php <<'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function pivots(): array
    {
        return [['business_idea','skill'],['article','business_idea'],['business_idea','photo'],['business_idea','video'],['article','skill'],['photo','skill'],['skill','video'],['article','photo'],['article','video'],['opportunity','photo'],['opportunity','video']];
    }

    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false)->after('email'));

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image_key')->nullable();
            $t->timestamps();
        });

        $common = function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('summary')->nullable();
            $t->string('cover_image_key')->nullable();
            $t->string('cover_image_alt')->nullable();
            $t->string('status', 20)->default('draft')->index();
            $t->boolean('is_featured')->default(false)->index();
            $t->timestamp('published_at')->nullable();
            $t->string('seo_title')->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->string('focus_keyword')->nullable();
            $t->string('og_image_key')->nullable();
            $t->softDeletes();
            $t->timestamps();
        };

        Schema::create('business_ideas', function (Blueprint $t) use ($common) {
            $common($t);
            $t->longText('description')->nullable();
            $t->string('currency', 3)->default('RWF');
            foreach (['startup_capital', 'operating_costs', 'expected_revenue', 'expected_profit'] as $c) {
                $t->decimal($c, 14, 2)->nullable();
            }
            foreach (['materials', 'equipment', 'requirements', 'target_customers', 'location_notes', 'steps', 'operating_procedures', 'profitability_notes', 'risks', 'challenges', 'tips'] as $c) {
                $t->longText($c)->nullable();
            }
            $t->fullText(['title', 'summary']);
        });

        Schema::create('skills', function (Blueprint $t) use ($common) {
            $common($t);
            foreach (['description', 'materials', 'equipment', 'instructions', 'applications'] as $c) {
                $t->longText($c)->nullable();
            }
            $t->fullText(['title', 'summary']);
        });

        Schema::create('opportunities', function (Blueprint $t) use ($common) {
            $common($t);
            $t->longText('description')->nullable();
            $t->longText('eligibility')->nullable();
            $t->longText('application_instructions')->nullable();
            $t->date('opens_at')->nullable();
            $t->date('closes_at')->nullable();
            $t->string('status_override', 20)->nullable();
            $t->string('application_url')->nullable();
            $t->fullText(['title', 'summary']);
        });

        Schema::create('articles', function (Blueprint $t) use ($common) {
            $common($t);
            $t->longText('content')->nullable();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedSmallInteger('reading_time')->default(1);
            $t->fullText(['title', 'summary', 'content']);
        });

        foreach (['photos', 'videos'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
                $t->string('title');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                if ($table === 'photos') {
                    $t->string('image_key');
                    $t->string('alt_text')->nullable();
                } else {
                    $t->string('video_key')->nullable();
                    $t->string('thumbnail_key')->nullable();
                    $t->string('external_url')->nullable();
                    $t->string('provider', 30)->nullable();
                }
                $t->string('status', 20)->default('draft')->index();
                $t->boolean('is_featured')->default(false)->index();
                $t->timestamp('published_at')->nullable();
                $t->softDeletes();
                $t->timestamps();
                $t->fullText(['title', 'description']);
            });
        }

        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('disk', 20)->default('r2');
            $t->string('object_key')->unique();
            $t->string('original_name');
            $t->string('mime_type', 100);
            $t->string('type', 20)->index();
            $t->unsignedBigInteger('size')->default(0);
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->string('alt_text')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('homepage_slides', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->text('description')->nullable();
            $t->string('button_label')->nullable();
            $t->string('button_link')->nullable();
            $t->string('image_key');
            $t->string('alt_text')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('subject');
            $t->text('message');
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('read_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->morphs('commentable');
            $t->string('name');
            $t->string('email')->nullable();
            $t->text('body');
            $t->string('status', 20)->default('pending')->index();
            $t->boolean('is_reported')->default(false);
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->longText('value')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->nullableMorphs('subject');
            $t->json('details')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
        });

        Schema::create('redirects', function (Blueprint $t) {
            $t->id();
            $t->string('from_path')->unique();
            $t->string('to_path');
            $t->timestamps();
        });

        foreach ($this->pivots() as [$a, $b]) {
            Schema::create("{$a}_{$b}", function (Blueprint $t) use ($a, $b) {
                $t->foreignId("{$a}_id")->constrained()->cascadeOnDelete();
                $t->foreignId("{$b}_id")->constrained()->cascadeOnDelete();
                $t->primary(["{$a}_id", "{$b}_id"]);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->pivots() as [$a, $b]) {
            Schema::dropIfExists("{$a}_{$b}");
        }
        foreach (['redirects', 'activity_logs', 'settings', 'comments', 'contact_messages', 'homepage_slides', 'media', 'videos', 'photos', 'articles', 'opportunities', 'skills', 'business_ideas', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
EOF

cat > app/Models/Concerns/Publishable.php <<'EOF'
<?php

namespace App\Models\Concerns;

trait Publishable
{
    public function scopePublished($q)
    {
        return $q->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }
}
EOF

for M in Media HomepageSlide ContactMessage Comment Setting ActivityLog; do
printf '<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass %s extends Model\n{\n    protected $guarded = [];\n}\n' $M > app/Models/$M.php
done

cat > app/Models/Category.php <<'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    public function businessIdeas() { return $this->hasMany(BusinessIdea::class); }
    public function skills() { return $this->hasMany(Skill::class); }
    public function opportunities() { return $this->hasMany(Opportunity::class); }
    public function articles() { return $this->hasMany(Article::class); }
    public function photos() { return $this->hasMany(Photo::class); }
    public function videos() { return $this->hasMany(Video::class); }
}
EOF

cat > app/Models/BusinessIdea.php <<'EOF'
<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessIdea extends Model
{
    use Publishable, SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    public function category() { return $this->belongsTo(Category::class); }
    public function skills() { return $this->belongsToMany(Skill::class); }
    public function articles() { return $this->belongsToMany(Article::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }
}
EOF

cat > app/Models/Skill.php <<'EOF'
<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
    use Publishable, SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    public function category() { return $this->belongsTo(Category::class); }
    public function businessIdeas() { return $this->belongsToMany(BusinessIdea::class); }
    public function articles() { return $this->belongsToMany(Article::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }
}
EOF

cat > app/Models/Opportunity.php <<'EOF'
<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use Publishable, SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime', 'opens_at' => 'date', 'closes_at' => 'date'];

    public function category() { return $this->belongsTo(Category::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status_override) {
            return $this->status_override;
        }
        $today = now()->startOfDay();
        if ($this->opens_at && $this->opens_at->gt($today)) {
            return 'upcoming';
        }
        if ($this->closes_at && $this->closes_at->lt($today)) {
            return 'closed';
        }
        if ($this->closes_at && $this->closes_at->lte($today->copy()->addDays(7))) {
            return 'closing_soon';
        }
        return 'open';
    }
}
EOF

cat > app/Models/Article.php <<'EOF'
<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use Publishable, SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (Article $a) {
            $words = str_word_count(strip_tags((string) $a->content));
            $a->reading_time = max(1, (int) ceil($words / 200));
        });
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function businessIdeas() { return $this->belongsToMany(BusinessIdea::class); }
    public function skills() { return $this->belongsToMany(Skill::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }
}
EOF

for M in Photo Video; do
cat > app/Models/$M.php <<'EOF'
<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MODEL extends Model
{
    use Publishable, SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    public function category() { return $this->belongsTo(Category::class); }
    public function businessIdeas() { return $this->belongsToMany(BusinessIdea::class); }
    public function skills() { return $this->belongsToMany(Skill::class); }
    public function articles() { return $this->belongsToMany(Article::class); }
    public function opportunities() { return $this->belongsToMany(Opportunity::class); }
}
EOF
sed -i "s/class MODEL/class $M/" app/Models/$M.php
done

cat > database/seeders/AdminSeeder.php <<'EOF'
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (! $email || ! $password) {
            $this->command->error('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env first.');
            return;
        }
        $user = User::firstOrNew(['email' => $email]);
        $user->name = env('ADMIN_NAME', 'Administrator');
        $user->password = $password;
        $user->is_admin = true;
        $user->save();
    }
}
EOF

cat > database/seeders/SettingSeeder.php <<'EOF'
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
EOF

cat > database/seeders/CategorySeeder.php <<'EOF'
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Agriculture', 'Technology', 'Trade', 'Services', 'Education', 'Manufacturing'] as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
EOF

cat > database/seeders/DatabaseSeeder.php <<'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([AdminSeeder::class, SettingSeeder::class, CategorySeeder::class]);
    }
}
EOF

$PHP artisan migrate --force
$PHP artisan db:seed --force
echo "PHASE 2 COMPLETE"