<?php

namespace Tests\Feature;

use App\Models\BusinessIdea;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function idea(string $slug, string $status = 'published'): BusinessIdea
    {
        return BusinessIdea::create([
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'status' => $status,
            'published_at' => now(),
        ]);
    }

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('GuhangaAkazi');
    }

    public function test_public_pages_load(): void
    {
        foreach (['about', 'mission', 'vision', 'contact', 'privacy', 'terms', 'business-ideas', 'skills', 'opportunities', 'articles', 'photos', 'videos', 'search'] as $path) {
            $this->get('/' . $path)->assertOk();
        }
    }

    public function test_published_idea_is_visible(): void
    {
        $this->idea('poultry-farming');
        $this->get('/business-ideas/poultry-farming')->assertOk()->assertSee('Poultry farming');
        $this->get('/business-ideas')->assertOk()->assertSee('Poultry farming');
    }

    public function test_draft_idea_is_hidden(): void
    {
        $this->idea('secret-plan', 'draft');
        $this->get('/business-ideas/secret-plan')->assertNotFound();
        $this->get('/business-ideas')->assertDontSee('Secret plan');
    }

    public function test_search_suggestions_find_published_content_only(): void
    {
        Skill::create(['title' => 'Poultry farming', 'slug' => 'poultry-farming', 'status' => 'published', 'published_at' => now()]);
        Skill::create(['title' => 'Poultry secret', 'slug' => 'poultry-secret', 'status' => 'draft']);

        $this->getJson('/search/suggest?q=pou')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Poultry farming'])
            ->assertJsonMissing(['title' => 'Poultry secret']);

        $this->get('/search?q=poultry')->assertOk()->assertSee('Poultry farming');
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin');
        $this->get('/admin/business-ideas')->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
    }

    public function test_non_admin_user_is_blocked(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin/dashboard')->assertRedirect('/admin');
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_robots_and_sitemap(): void
    {
        $this->idea('sitemap-idea');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin', false)->assertSee('sitemap.xml', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/business-ideas/sitemap-idea', false);
    }

    public function test_comments_and_likes_only_on_published_content(): void
    {
        $published = $this->idea('open-idea');
        $draft = $this->idea('closed-idea', 'draft');

        $this->postJson('/engage/business-ideas/' . $published->id . '/comment', ['name' => 'Eric', 'body' => 'Great idea'])->assertStatus(201);
        $this->postJson('/engage/business-ideas/' . $draft->id . '/comment', ['name' => 'Eric', 'body' => 'Hidden idea'])->assertNotFound();
        $this->postJson('/engage/business-ideas/' . $published->id . '/like')->assertOk()->assertJson(['liked' => true, 'likes' => 1]);
    }
}
