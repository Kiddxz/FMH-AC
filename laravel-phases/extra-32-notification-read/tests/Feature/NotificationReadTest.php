<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bell: a clicked notification, "Mark as read" and "Mark all as read" make the number go down.
 */
class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function staff(): User
    {
        return User::where('email', 'assistant@fmhanimalclinic.com')->first();
    }

    private function keys(): array
    {
        preg_match_all('/data-key="([0-9a-f]{40})"/', $this->get('/notifications')->getContent(), $m);

        return $m[1];
    }

    public function test_reading_one_notification_lowers_the_number(): void
    {
        $this->actingAs($this->staff());
        $keys = $this->keys();
        $total = count($keys);
        $this->assertGreaterThan(1, $total);
        $this->get('/notifications')->assertSee('data-unread="' . $total . '"', false)->assertSee('is-unread');

        $this->postJson('/notifications/read', ['key' => $keys[0]])->assertOk()->assertJson(['unread' => $total - 1]);
        $this->get('/notifications')->assertSee('data-unread="' . ($total - 1) . '"', false)->assertSee('is-read');
        $this->get('/staff')->assertSee('<span class="notif-count">' . ($total - 1) . '</span>', false);

        // the same one again does not count twice
        $this->postJson('/notifications/read', ['key' => $keys[0]])->assertJson(['unread' => $total - 1]);
    }

    public function test_mark_all_as_read(): void
    {
        $this->actingAs($this->staff());
        $this->assertNotEmpty($this->keys());

        $this->postJson('/notifications/read-all')->assertOk()->assertJson(['unread' => 0]);
        $this->get('/notifications')->assertSee('data-unread="0"', false)->assertDontSee('is-unread');
        $this->get('/staff')->assertDontSee('<span class="notif-count">', false);
    }

    public function test_a_key_that_is_not_yours_is_ignored(): void
    {
        $this->actingAs($this->staff());
        $total = count($this->keys());

        $this->postJson('/notifications/read', ['key' => str_repeat('a', 40)])->assertOk()->assertJson(['unread' => $total]);
        $this->postJson('/notifications/read', ['key' => 'bad'])->assertStatus(422);
    }

    public function test_reads_are_per_user(): void
    {
        $this->actingAs($this->staff())->postJson('/notifications/read-all')->assertJson(['unread' => 0]);

        $admin = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->actingAs($admin)->get('/notifications')->assertSee('is-unread');
    }

    public function test_customers_cannot_use_it(): void
    {
        $customer = User::whereHas('role', fn ($q) => $q->where('slug', \App\Models\Role::CUSTOMER))->first();
        $this->actingAs($customer)->postJson('/notifications/read-all')->assertForbidden();
    }
}
