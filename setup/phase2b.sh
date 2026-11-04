set -e
PHP=/usr/bin/php8.4
mkdir -p app/Http/Controllers

cat > database/migrations/2026_01_01_000002_add_engagement.php <<'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['business_ideas', 'skills', 'opportunities', 'articles', 'photos', 'videos'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('views_count')->default(0);
                $t->unsignedBigInteger('likes_count')->default(0);
                $t->unsignedBigInteger('comments_count')->default(0);
            });
        }

        Schema::create('likes', function (Blueprint $t) {
            $t->id();
            $t->morphs('likeable');
            $t->string('visitor_hash', 64);
            $t->timestamps();
            $t->unique(['likeable_type', 'likeable_id', 'visitor_hash'], 'likes_unique');
        });

        Schema::create('content_views', function (Blueprint $t) {
            $t->id();
            $t->morphs('viewable');
            $t->string('visitor_hash', 64);
            $t->date('viewed_on');
            $t->timestamps();
            $t->unique(['viewable_type', 'viewable_id', 'visitor_hash', 'viewed_on'], 'cv_unique');
        });

        Schema::table('comments', function (Blueprint $t) {
            $t->foreignId('parent_id')->nullable()->after('id')->constrained('comments')->cascadeOnDelete();
            $t->string('ip_address', 45)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_id');
            $t->dropColumn('ip_address');
        });
        Schema::dropIfExists('content_views');
        Schema::dropIfExists('likes');
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['views_count', 'likes_count', 'comments_count']));
        }
    }
};
EOF

cat > app/Models/Like.php <<'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $guarded = [];
}
EOF

cat > app/Models/Comment.php <<'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $guarded = [];
    protected $casts = ['is_reported' => 'boolean'];

    protected static function booted(): void
    {
        $sync = function (Comment $c) {
            $parent = $c->commentable;
            if ($parent) {
                $parent->forceFill([
                    'comments_count' => $parent->comments()->where('status', 'approved')->count(),
                ])->saveQuietly();
            }
        };
        static::saved($sync);
        static::deleted($sync);
    }

    public function commentable() { return $this->morphTo(); }
    public function parent() { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies() { return $this->hasMany(Comment::class, 'parent_id')->where('status', 'approved')->oldest(); }
}
EOF

cat > app/Models/Concerns/HasEngagement.php <<'EOF'
<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Models\Like;

trait HasEngagement
{
    public function likes() { return $this->morphMany(Like::class, 'likeable'); }
    public function comments() { return $this->morphMany(Comment::class, 'commentable'); }

    public function approvedComments()
    {
        return $this->comments()->where('status', 'approved')->whereNull('parent_id')->with('replies')->latest();
    }
}
EOF

sed -i 's/use Publishable, SoftDeletes;/use Publishable, SoftDeletes, \\App\\Models\\Concerns\\HasEngagement;/' app/Models/BusinessIdea.php app/Models/Skill.php app/Models/Opportunity.php app/Models/Article.php app/Models/Photo.php app/Models/Video.php

cat > app/Http/Controllers/EngagementController.php <<'EOF'
<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Skill;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EngagementController extends Controller
{
    private const TYPES = [
        'business-ideas' => BusinessIdea::class,
        'skills' => Skill::class,
        'opportunities' => Opportunity::class,
        'articles' => Article::class,
        'photos' => Photo::class,
        'videos' => Video::class,
    ];

    private function item(string $type, int $id)
    {
        $class = self::TYPES[$type] ?? abort(404);
        return $class::published()->findOrFail($id);
    }

    private function visitor(Request $request): string
    {
        $token = $request->cookie('visitor_id');
        if (! is_string($token) || strlen($token) !== 40) {
            $token = Str::random(40);
            Cookie::queue(cookie('visitor_id', $token, 60 * 24 * 365));
        }
        return hash('sha256', $token);
    }

    public function like(Request $request, string $type, int $id)
    {
        $item = $this->item($type, $id);
        $hash = $this->visitor($request);
        $liked = false;

        DB::transaction(function () use ($item, $hash, &$liked) {
            $existing = $item->likes()->where('visitor_hash', $hash)->first();
            if ($existing) {
                $existing->delete();
                $item->decrement('likes_count');
            } else {
                $item->likes()->create(['visitor_hash' => $hash]);
                $item->increment('likes_count');
                $liked = true;
            }
        });

        return response()->json(['liked' => $liked, 'likes' => $item->fresh()->likes_count]);
    }

    public function view(Request $request, string $type, int $id)
    {
        $item = $this->item($type, $id);
        $inserted = DB::table('content_views')->insertOrIgnore([
            'viewable_type' => $item->getMorphClass(),
            'viewable_id' => $item->id,
            'visitor_hash' => $this->visitor($request),
            'viewed_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($inserted) {
            $item->increment('views_count');
        }
        return response()->json(['views' => $item->fresh()->views_count]);
    }

    public function comment(Request $request, string $type, int $id)
    {
        $item = $this->item($type, $id);
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'nullable|email|max:150',
            'body' => 'required|string|min:2|max:1500',
            'parent_id' => 'nullable|integer',
            'website' => 'prohibited',
        ]);

        if (! empty($data['parent_id'])) {
            $item->comments()->whereNull('parent_id')->findOrFail($data['parent_id']);
        }

        $comment = $item->comments()->create([
            'name' => strip_tags($data['name']),
            'email' => $data['email'] ?? null,
            'body' => strip_tags($data['body']),
            'parent_id' => $data['parent_id'] ?? null,
            'status' => 'approved',
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['ok' => true, 'id' => $comment->id, 'comments' => $item->fresh()->comments_count], 201);
    }
}
EOF

cat >> routes/web.php <<'EOF'

Route::prefix('engage/{type}/{id}')->whereNumber('id')->group(function () {
    Route::post('like', [\App\Http\Controllers\EngagementController::class, 'like'])->middleware('throttle:30,1');
    Route::post('view', [\App\Http\Controllers\EngagementController::class, 'view'])->middleware('throttle:60,1');
    Route::post('comment', [\App\Http\Controllers\EngagementController::class, 'comment'])->middleware('throttle:5,1');
});
EOF

$PHP artisan migrate --force
$PHP artisan route:list --path=engage
echo "PHASE 2B COMPLETE"