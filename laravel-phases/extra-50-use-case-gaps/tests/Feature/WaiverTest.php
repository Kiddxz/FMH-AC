<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pet;
use App\Models\Role;
use App\Models\User;
use App\Models\Waiver;
use App\Models\WaiverTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 13: digital waiver and consent forms.
 * Demo data: WVR-000001 (Max, health certificate, reviewed) and WVR-000002 (Luna, surgical operation, waiting).
 */
class WaiverTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $vet;
    private User $owner;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
    }

    // Like assertSee, but spaces and line breaks do not matter (editors may re-wrap long lines)
    private function assertSeeWords(TestResponse $response, string ...$texts): TestResponse
    {
        $html = preg_replace('/\s+/', ' ', $response->getContent());
        foreach ($texts as $text) {
            $this->assertStringContainsString($text, $html, "The page does not show: {$text}");
        }

        return $response;
    }

    private function pending(): Waiver
    {
        return Waiver::where('reference', 'WVR-000002')->first();
    }

    private function sign(): array
    {
        return ['signer_name' => 'Mark Santos', 'agree' => '1'];
    }

    public function test_staff_list_and_filters(): void
    {
        $page = $this->actingAs($this->staff)->get('/staff/waivers')->assertOk();
        $this->assertSeeWords($page, 'WVR-000001', 'WVR-000002', 'Waiting for signature: 1', 'Reviewed: 1', '+ Prepare Waiver');

        $this->actingAs($this->staff)->get('/staff/waivers?status=pending')->assertSee('WVR-000002')->assertDontSee('WVR-000001');
        $this->actingAs($this->staff)->get('/staff/waivers?search=luna')->assertSee('WVR-000002')->assertDontSee('WVR-000001');
    }

    public function test_staff_prepares_a_waiver_with_a_copy_of_the_text(): void
    {
        $buddy = Pet::where('name', 'Buddy')->first();
        $template = WaiverTemplate::where('waiver_type', 'major_procedure')->first();

        $this->actingAs($this->staff)->get('/staff/waivers/create?pet=' . $buddy->id)->assertOk()->assertSee('Prepare Waiver');
        $this->actingAs($this->staff)->post('/staff/waivers', ['waiver_template_id' => $template->id, 'pet_id' => $buddy->id])
            ->assertRedirect('/staff/waivers/3');

        $waiver = Waiver::find(3);
        $this->assertSame('WVR-000003', $waiver->reference);
        $this->assertSame('pending', $waiver->status);
        $this->assertSame($buddy->customer_id, $waiver->customer_id);
        $this->assertSame($this->staff->id, $waiver->prepared_by);
        $this->assertStringContainsString('Owner: John Cruz', $waiver->content_snapshot);
        $this->assertStringContainsString('Pet: Buddy', $waiver->content_snapshot);
        $this->assertStringContainsString($template->body, $waiver->content_snapshot);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Waivers', 'action' => 'prepared']);

        // an inactive form cannot be used
        $template->update(['is_active' => false]);
        $this->actingAs($this->staff)->post('/staff/waivers', ['waiver_template_id' => $template->id, 'pet_id' => $buddy->id])
            ->assertSessionHasErrors('waiver_template_id');
        $this->actingAs($this->staff)->post('/staff/waivers', [])->assertSessionHasErrors(['waiver_template_id', 'pet_id']);
    }

    public function test_owner_signs_at_the_clinic_and_the_text_is_locked(): void
    {
        $waiver = $this->pending();
        $this->assertSeeWords($this->actingAs($this->staff)->get("/staff/waivers/{$waiver->id}"), 'Sign at the Clinic', 'Cancel Waiver');

        $this->actingAs($this->staff)->post("/staff/waivers/{$waiver->id}/sign", ['signer_name' => ''])
            ->assertSessionHasErrors(['signer_name', 'agree']);

        $this->actingAs($this->staff)->post("/staff/waivers/{$waiver->id}/sign", $this->sign())->assertSessionHasNoErrors();
        $waiver->refresh();
        $this->assertSame('signed', $waiver->status);
        $this->assertSame('clinic', $waiver->signed_via);
        $this->assertSame('Mark Santos', $waiver->signer_name);
        $this->assertNotNull($waiver->signed_at);

        // signed: no second signature, cannot be cancelled, page says it is locked
        $this->actingAs($this->staff)->post("/staff/waivers/{$waiver->id}/sign", $this->sign())->assertForbidden();
        $this->actingAs($this->staff)->delete("/staff/waivers/{$waiver->id}")->assertForbidden();
        $page = $this->actingAs($this->staff)->get("/staff/waivers/{$waiver->id}");
        $this->assertSeeWords($page, 'This text is locked');
        $page->assertDontSee('Cancel Waiver');

        // changing the template later does not change the signed copy
        $before = $waiver->content_snapshot;
        $waiver->template->update(['body' => 'A completely new text for future waivers only.']);
        $this->assertSame($before, $waiver->fresh()->content_snapshot);
    }

    public function test_unsigned_waiver_can_be_cancelled(): void
    {
        $waiver = $this->pending();
        $this->actingAs($this->staff)->delete("/staff/waivers/{$waiver->id}")->assertRedirect('/staff/waivers');
        $this->assertNull($waiver->fresh());
        $this->assertDatabaseHas('activity_logs', ['module' => 'Waivers', 'action' => 'cancelled']);
    }

    public function test_customer_reads_and_signs_in_the_portal(): void
    {
        $waiver = $this->pending();

        $list = $this->actingAs($this->owner)->get('/portal/waivers')->assertOk();
        $this->assertSeeWords($list, 'Consent for Surgical Operation', 'Health Certificate Request and Consent', 'Read &amp; Sign');
        // menu badge: one form to sign
        $this->assertMatchesRegularExpression('/Waivers\s*<span[^>]*>1<\/span>/', $this->actingAs($this->owner)->get('/portal')->getContent());

        $this->assertSeeWords($this->actingAs($this->owner)->get("/portal/waivers/{$waiver->id}"), 'Sign This Form', 'Owner: Mark Santos');

        $this->actingAs($this->owner)->post("/portal/waivers/{$waiver->id}/sign", $this->sign())->assertSessionHasNoErrors();
        $this->assertSame('portal', $waiver->fresh()->signed_via);
        $this->assertSame('signed', $waiver->fresh()->status);

        $page = $this->actingAs($this->owner)->get("/portal/waivers/{$waiver->id}");
        $this->assertSeeWords($page, 'This form is signed', 'Print / Save as PDF');
        $page->assertDontSee('Sign This Form');
        $this->actingAs($this->owner)->get("/portal/waivers/{$waiver->id}/print")->assertOk()->assertSee('Mark Santos');
    }

    public function test_another_owner_cannot_see_or_sign(): void
    {
        $waiver = $this->pending();
        $other = User::factory()->create(['email' => 'other@example.com']);
        $other->role_id = Role::where('slug', Role::CUSTOMER)->value('id');
        $other->save();
        Customer::create(['first_name' => 'Other', 'last_name' => 'Owner', 'contact_number' => '09170000009'])->forceFill(['user_id' => $other->id])->save();

        $this->actingAs($other->fresh())->get('/portal/waivers')->assertOk()->assertDontSee('WVR-000002');
        $this->actingAs($other->fresh())->get("/portal/waivers/{$waiver->id}")->assertForbidden();
        $this->actingAs($other->fresh())->get("/portal/waivers/{$waiver->id}/print")->assertForbidden();
        $this->actingAs($other->fresh())->post("/portal/waivers/{$waiver->id}/sign", $this->sign())->assertForbidden();
        $this->assertSame('pending', $waiver->fresh()->status);
    }

    public function test_vet_reviews_signed_waivers_and_manages_templates(): void
    {
        $waiver = $this->pending();

        // not signed yet: nothing to review
        $this->actingAs($this->vet)->patch("/admin/waivers/{$waiver->id}/review")->assertForbidden();

        $this->actingAs($this->staff)->post("/staff/waivers/{$waiver->id}/sign", $this->sign());
        $this->assertSeeWords($this->actingAs($this->vet)->get("/admin/waivers/{$waiver->id}"), 'Mark as Reviewed');
        $this->actingAs($this->vet)->patch("/admin/waivers/{$waiver->id}/review", ['review_notes' => 'Consent confirmed.'])->assertSessionHasNoErrors();
        $waiver->refresh();
        $this->assertSame('reviewed', $waiver->status);
        $this->assertSame($this->vet->id, $waiver->reviewed_by);
        $this->assertSame('Consent confirmed.', $waiver->review_notes);

        // templates
        $this->actingAs($this->vet)->get('/admin/waiver-templates')->assertOk()->assertSee('Refusal of Treatment Waiver');
        $this->actingAs($this->vet)->post('/admin/waiver-templates', [
            'title' => 'Grooming Consent', 'waiver_type' => 'other', 'is_active' => '1',
            'body' => 'I allow FMH Animal Clinic to groom my pet and understand the risks for anxious pets.',
        ])->assertRedirect('/admin/waiver-templates');
        $this->assertDatabaseHas('waiver_templates', ['title' => 'Grooming Consent']);
        $this->actingAs($this->vet)->post('/admin/waiver-templates', ['title' => 'Grooming Consent', 'waiver_type' => 'x', 'body' => 'short'])
            ->assertSessionHasErrors(['title', 'waiver_type', 'body']);

        // the vet does not prepare waivers or sign for owners
        $this->actingAs($this->vet)->post('/staff/waivers', [])->assertForbidden();
    }

    public function test_super_admin_views_waivers_and_edits_the_form_templates(): void
    {
        $waiver = $this->pending();

        // the waivers: view only (signed text is locked)
        $this->assertSeeWords($this->actingAs($this->super)->get('/superadmin/waivers'), 'WVR-000001', 'WVR-000002', 'Form Templates');
        $page = $this->actingAs($this->super)->get("/superadmin/waivers/{$waiver->id}")->assertOk();
        $page->assertDontSee('Sign at the Clinic')->assertDontSee('Mark as Reviewed')->assertDontSee('Cancel Waiver')->assertDontSee('Edit Waiver');
        $this->actingAs($this->super)->get("/superadmin/waivers/{$waiver->id}/print")->assertOk()->assertSee('WVR-000002');
        $this->actingAs($this->super)->post("/staff/waivers/{$waiver->id}/sign", $this->sign())->assertForbidden();
        $this->actingAs($this->super)->patch("/admin/waivers/{$waiver->id}/review")->assertForbidden();

        // the forms: "View and edit Digital Liability Waiver and Consent Forms" (use case Figure 5.3)
        $this->actingAs($this->super)->get('/superadmin/waiver-templates')->assertOk()->assertSee('Refusal of Treatment Waiver');
        $this->actingAs($this->super)->post('/superadmin/waiver-templates', [
            'title' => 'Boarding Consent', 'waiver_type' => 'other', 'is_active' => '1',
            'body' => 'I allow FMH Animal Clinic to keep my pet overnight and to call me if anything happens.',
        ])->assertRedirect('/superadmin/waiver-templates');
        $template = WaiverTemplate::where('title', 'Boarding Consent')->firstOrFail();
        $this->actingAs($this->super)->get("/superadmin/waiver-templates/{$template->id}/edit")->assertOk()->assertSee('Edit Waiver Form');
        $this->actingAs($this->super)->put("/superadmin/waiver-templates/{$template->id}", [
            'title' => 'Boarding Consent', 'waiver_type' => 'other', 'is_active' => '0',
            'body' => 'I allow FMH Animal Clinic to keep my pet overnight and to call me right away if anything happens.',
        ])->assertRedirect('/superadmin/waiver-templates');
        $this->assertFalse($template->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Waivers', 'action' => 'updated']);

        // staff and customers cannot edit the forms
        $this->actingAs($this->staff)->get('/superadmin/waiver-templates')->assertForbidden();
        $this->actingAs($this->owner)->get('/admin/waiver-templates')->assertForbidden();
    }

    public function test_staff_edit_a_waiver_only_before_it_is_signed(): void
    {
        $waiver = $this->pending();
        $otherForm = WaiverTemplate::where('title', 'Consent for Major Procedure')->firstOrFail();

        $this->assertSeeWords($this->actingAs($this->staff)->get("/staff/waivers/{$waiver->id}"), 'Edit Waiver');
        $this->actingAs($this->staff)->get("/staff/waivers/{$waiver->id}/edit")->assertOk()->assertSee('Edit Waiver ' . $waiver->reference);
        $this->actingAs($this->staff)->put("/staff/waivers/{$waiver->id}", ['waiver_template_id' => $otherForm->id, 'pet_id' => $waiver->pet_id])
            ->assertRedirect("/staff/waivers/{$waiver->id}");

        $waiver->refresh();
        $this->assertSame($otherForm->id, $waiver->waiver_template_id);
        $this->assertStringContainsString(strtoupper($otherForm->title), $waiver->content_snapshot);
        $this->assertSame('WVR-000002', $waiver->reference);   // same waiver, new text
        $this->assertDatabaseHas('activity_logs', ['module' => 'Waivers', 'action' => 'updated']);

        // once signed it is locked: no edit page, no button
        $this->actingAs($this->staff)->post("/staff/waivers/{$waiver->id}/sign", $this->sign());
        $this->actingAs($this->staff)->get("/staff/waivers/{$waiver->id}")->assertDontSee('Edit Waiver');
        $this->actingAs($this->staff)->get("/staff/waivers/{$waiver->id}/edit")->assertForbidden();
        $this->actingAs($this->staff)->put("/staff/waivers/{$waiver->id}", ['waiver_template_id' => $otherForm->id, 'pet_id' => $waiver->pet_id])->assertForbidden();

        // the vet and the super admin do not edit waivers
        $other = Waiver::prepare($otherForm, $waiver->pet, $this->staff->id);
        $this->actingAs($this->vet)->get("/staff/waivers/{$other->id}/edit")->assertForbidden();
        $this->actingAs($this->super)->put("/staff/waivers/{$other->id}", ['waiver_template_id' => $otherForm->id, 'pet_id' => $waiver->pet_id])->assertForbidden();
    }

    public function test_access_rules(): void
    {
        $this->actingAs($this->owner)->get('/staff/waivers')->assertForbidden();
        $this->actingAs($this->owner)->get('/admin/waivers')->assertForbidden();
        $this->actingAs($this->staff)->get('/admin/waiver-templates')->assertForbidden();
        $this->actingAs($this->staff)->get('/portal/waivers')->assertForbidden();
        $this->actingAs($this->vet)->get('/staff/waivers/create')->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/portal/waivers')->assertRedirect('/login');
        $this->get('/staff/waivers')->assertRedirect('/login');
        $this->get('/superadmin/waivers')->assertRedirect('/login');
    }
}
