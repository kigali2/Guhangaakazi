<?php

namespace Tests\Feature;

use App\Models\BusinessIdea;
use App\Models\Media;
use App\Models\MediaLink;
use App\Models\User;
use App\Support\Html;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function image(): Media
    {
        return Media::create([
            'object_key' => 'images/test.jpg',
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'type' => 'image',
        ]);
    }

    private function idea(): BusinessIdea
    {
        return BusinessIdea::create(['title' => 'Fruit shop', 'slug' => 'fruit-shop', 'status' => 'published', 'published_at' => now()]);
    }

    public function test_html_sanitizer_removes_dangerous_code(): void
    {
        $clean = Html::clean('<p>Hello</p><script>alert(1)</script><a href="javascript:alert(2)">x</a><p onclick="bad()">Hi</p>');
        $this->assertStringContainsString('<p>Hello</p>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
    }

    public function test_profile_requires_login(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin');
    }

    public function test_admin_can_update_profile_name(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/profile', ['name' => 'New Name', 'email' => $admin->email])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'New Name']);
    }

    public function test_email_change_needs_current_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/profile', ['name' => 'Admin', 'email' => 'other@example.com'])->assertSessionHasErrors('current_password');
        $this->actingAs($admin)->put('/admin/profile', ['name' => 'Admin', 'email' => 'other@example.com', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'email' => 'other@example.com']);
    }

    public function test_admin_can_change_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/profile/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword123', $admin->fresh()->password));
    }

    public function test_link_media_show_on_page_and_remove_keeps_file(): void
    {
        $admin = $this->admin();
        $media = $this->image();
        $idea = $this->idea();

        $this->actingAs($admin)->post('/admin/links', [
            'content_type' => 'business-ideas',
            'content_id' => $idea->id,
            'role' => 'gallery',
            'media_ids' => [$media->id],
        ])->assertRedirect('/admin/links');

        $this->assertDatabaseHas('media_links', ['media_id' => $media->id, 'role' => 'gallery']);
        $this->get('/business-ideas/fruit-shop')->assertOk()->assertSee('images/test.jpg', false);

        $link = MediaLink::first();
        $this->actingAs($admin)->delete('/admin/links/' . $link->id)->assertRedirect();
        $this->assertDatabaseMissing('media_links', ['id' => $link->id]);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_video_role_rejects_an_image_file(): void
    {
        $admin = $this->admin();
        $media = $this->image();
        $idea = $this->idea();

        $this->actingAs($admin)->post('/admin/links', [
            'content_type' => 'business-ideas',
            'content_id' => $idea->id,
            'role' => 'video',
            'media_ids' => [$media->id],
        ])->assertSessionHasErrors('media_ids');
    }
}
