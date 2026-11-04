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
