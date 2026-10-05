<?php

namespace Tests\Feature;

use App\Http\Middleware\EmojiToLineIcons;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Minimalist look: pages show simple line icons instead of colored emoji.
 */
class LineIconsTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_emoji_become_line_icons_but_scripts_and_titles_are_safe(): void
    {
        $html = '<html><head><title>🐾 FMH</title></head><body>'
            . '<p>📅 October 5</p><select><option>🐶 Dog</option></select>'
            . '<button aria-label="📋 list">📋 All</button>'
            . '<script>const close = "✕ Close"; const cal = "📅";</script></body></html>';

        $out = (new EmojiToLineIcons())->convert($html);

        $this->assertStringContainsString('<title>FMH</title>', $out);                 // emoji removed from the tab title
        $this->assertStringContainsString('<option>Dog</option>', $out);               // and from dropdown options
        $this->assertStringContainsString('<svg class="ui-icon"', $out);               // shown as a drawing in the page
        $this->assertStringNotContainsString('<p>📅', $out);
        $this->assertStringContainsString('aria-label="📋 list"', $out);              // text inside tags is not touched
        $this->assertStringContainsString('const cal = "📅";', $out);                  // scripts are not touched
        $this->assertStringContainsString('.ui-icon{', $out);                          // the icon style is added once
    }

    public function test_real_pages_use_line_icons(): void
    {
        $this->seed(DatabaseSeeder::class);
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();

        $page = $this->actingAs($staff)->get('/staff/pos')->assertOk();
        $this->assertStringNotContainsString('📅', $page->getContent());
        $this->assertStringNotContainsString('📋', $page->getContent());
        $this->assertStringContainsString('class="ui-icon"', $page->getContent());

        $this->get('/')->assertOk()->assertDontSee('🐾', false);
    }

    public function test_downloads_are_not_changed(): void
    {
        $this->seed(DatabaseSeeder::class);
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();

        $csv = $this->actingAs($staff)->get('/staff/reports?type=appointments&format=csv');
        $this->assertStringNotContainsString('ui-icon', $csv->streamedContent());
    }
}
