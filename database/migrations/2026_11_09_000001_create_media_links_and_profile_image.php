<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $t->string('linkable_type', 120);
            $t->unsignedBigInteger('linkable_id');
            $t->string('role', 20);
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('caption')->nullable();
            $t->timestamps();
            $t->index(['linkable_type', 'linkable_id']);
            $t->unique(['media_id', 'linkable_type', 'linkable_id', 'role'], 'media_links_unique');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('profile_image_key')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('profile_image_key'));
        Schema::dropIfExists('media_links');
    }
};
