<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PatientFlow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 15: reports (FR-REQ021, FR-REQ027, Fig 6.2) and the "Today at the Clinic" dashboard box.
 * Demo data: 7 appointments from 30 days ago to 2 days ahead (1 completed, 5 pending/confirmed, 1 cancelled),
 * 1 vaccination record 30 days ago, bills OR-000001 (₱800 paid, 30 days ago) and OR-000002 (₱890, ₱500 paid today).
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $vet;
    private User $super;
    private string $range;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
        // a fixed range that holds all the demo data, whatever day the tests run
        $this->range = 'from=' . today()->subDays(40)->toDateString() . '&to=' . today()->addDays(5)->toDateString();
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

    private function report(User $user, string $query, string $area = 'staff'): TestResponse
    {
        $url = ['staff' => '/staff/reports', 'admin' => '/admin/reports', 'superadmin' => '/superadmin/reports'][$area];

        return $this->actingAs($user)->get($url . '?' . $query);
    }

    public function test_appointment_report_counts_and_status_filter(): void
    {
        $page = $this->report($this->staff, 'report=appointments&' . $this->range)->assertOk();
        $this->assertSeeWords($page, 'Appointment Report', 'Generate Report', 'Export CSV');
        $page->assertViewHas('report', fn ($r) => array_column($r['cards'], 2) === [7, 1, 5, 1]);
        $page->assertViewHas('report', fn ($r) => count($r['detail'][2]) === 7);

        $cancelled = $this->report($this->staff, 'report=appointments&status=cancelled&' . $this->range);
        $cancelled->assertViewHas('report', fn ($r) => count($r['detail'][2]) === 1 && $r['detail'][2][0][3] === 'Rocky');

        // default = this month: the report opens with no filter at all
        $this->actingAs($this->staff)->get('/staff/reports')->assertOk()->assertViewHas('filters', fn ($f) => $f['from'] === today()->startOfMonth()->toDateString());
    }

    public function test_filters_are_validated(): void
    {
        $this->actingAs($this->staff)->from('/staff/reports')
            ->get('/staff/reports?from=' . today()->toDateString() . '&to=' . today()->subDay()->toDateString())
            ->assertRedirect('/staff/reports')->assertSessionHasErrors('to');
        $this->actingAs($this->staff)->from('/staff/reports')->get('/staff/reports?report=sales&status=cancelled')->assertSessionHasErrors('status');
        $this->actingAs($this->staff)->from('/staff/reports')->get('/staff/reports?from=not-a-date')->assertSessionHasErrors('from');
        $this->actingAs($this->staff)->from('/staff/reports')
            ->get('/staff/reports?from=' . today()->subYears(2)->toDateString() . '&to=' . today()->toDateString())
            ->assertSessionHasErrors('to');
    }

    public function test_sales_report_excludes_void_bills(): void
    {
        $page = $this->report($this->vet, 'report=sales&' . $this->range, 'admin')->assertOk();
        $page->assertViewHas('report', fn ($r) => array_column($r['cards'], 2) === [2, '₱1,690.00', '₱1,300.00', '₱390.00']);
        $this->assertSeeWords($page, 'Collected by Payment Method', 'Dog Food 1kg', 'OR-000001', 'OR-000002');

        // void OR-000002: it disappears from the sales and from the money collected
        $this->actingAs($this->vet)->patch('/admin/transactions/' . Transaction::where('receipt_number', 'OR-000002')->value('id') . '/void', ['void_reason' => 'Customer returned the items.']);
        $after = $this->report($this->vet, 'report=sales&' . $this->range, 'admin');
        $after->assertViewHas('report', fn ($r) => array_column($r['cards'], 2) === [1, '₱800.00', '₱800.00', '₱0.00']);
        // the void bill is still listed (status Void), so nothing is hidden
        $this->report($this->vet, 'report=sales&status=void&' . $this->range, 'admin')
            ->assertViewHas('report', fn ($r) => count($r['detail'][2]) === 1 && $r['detail'][2][0][8] === 'Void');
    }

    public function test_inventory_report(): void
    {
        $page = $this->report($this->staff, 'report=inventory&' . $this->range)->assertOk();
        $page->assertViewHas('report', fn ($r) => array_column($r['cards'], 2) === [10, 6, 3, 1]);
        // the demo sale today: 2 dog food + 1 shampoo sold
        $page->assertViewHas('report', fn ($r) => $r['tables'][0][2][0][2] === 3);
        $this->report($this->staff, 'report=inventory&status=out&' . $this->range)
            ->assertViewHas('report', fn ($r) => count($r['detail'][2]) === 1 && $r['detail'][2][0][1] === 'Cotton Balls');
    }

    public function test_flow_and_daily_count_reports(): void
    {
        $flow = app(PatientFlow::class);
        foreach (['Buddy', 'Coco'] as $name) {
            $pet = Pet::where('name', $name)->first();
            $visit = $flow->checkIn(['customer_id' => $pet->customer_id, 'pet_id' => $pet->id, 'service_id' => Service::where('name', 'Consultation')->value('id'), 'visit_type' => 'walk_in'], $this->staff->id);
        }
        $flow->changeStatus($visit, 'ongoing');
        $flow->changeStatus($visit, 'completed');

        $page = $this->report($this->staff, 'report=flow&' . $this->range)->assertOk();
        $page->assertViewHas('report', fn ($r) => $r['cards'][0][2] === 2 && $r['cards'][1][2] === 2 && $r['cards'][3][2] === 1);
        $this->assertSeeWords($page, 'Visits by Service Type', 'Average Wait');

        $daily = $this->report($this->staff, 'report=daily&from=' . today()->toDateString() . '&to=' . today()->toDateString());
        $daily->assertViewHas('report', fn ($r) => $r['detail'][2][0][2] === 2 && $r['detail'][2][0][4] === 2 && $r['detail'][2][0][6] === 1);
    }

    public function test_record_summary_has_no_medical_notes(): void
    {
        $page = $this->report($this->super, 'report=records&' . $this->range, 'superadmin')->assertOk();
        $page->assertViewHas('report', fn ($r) => $r['cards'][0][2] === 1 && $r['cards'][2][2] === 1);
        $this->assertSeeWords($page, 'Records by Type', 'Registered Pets by Species', 'Max');
        // decision P1: no diagnosis or findings in reports
        $page->assertDontSee('Healthy - for vaccination')->assertDontSee('normal vital signs');
    }

    public function test_csv_export_and_print_view(): void
    {
        $csv = $this->report($this->staff, 'report=appointments&format=csv&' . $this->range)->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=appointment-report_', $csv->headers->get('Content-Disposition'));
        $content = $csv->streamedContent();
        $this->assertStringContainsString('FMH Animal Clinic - Appointment Report', $content);
        $this->assertStringContainsString('Date,Time,"Appointment ID",Pet,Owner,Service,Status', $content);
        $this->assertStringContainsString('Rocky', $content);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Reports', 'action' => 'exported']);

        $print = $this->report($this->vet, 'report=sales&format=print&' . $this->range, 'admin')->assertOk();
        $this->assertSeeWords($print, 'Sales &amp; Transaction Report', 'Print / Save as PDF', 'Printed', 'OR-000002');
        $print->assertDontSee('admin-nav');   // a clean page without the menu
    }

    public function test_dashboards_show_today_at_the_clinic(): void
    {
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff'), 'Today at the Clinic', 'Collected today: <strong>₱500.00</strong>', 'Recent Pet Registrations', 'Milo');
        $this->assertSeeWords($this->actingAs($this->vet)->get('/admin'), 'Today at the Clinic', 'Patient Status Today');
    }

    public function test_access_rules(): void
    {
        $owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->actingAs($owner)->get('/staff/reports')->assertForbidden();
        $this->actingAs($owner)->get('/admin/reports?format=csv')->assertForbidden();
        $this->actingAs($this->staff)->get('/admin/reports')->assertForbidden();
        $this->actingAs($this->vet)->get('/superadmin/reports')->assertForbidden();

        // the "reports" permission can be switched off (Phase 8 roles page)
        $this->staff->role->permissions()->detach(Permission::where('slug', 'reports.view')->value('id'));
        $this->actingAs($this->staff->fresh())->get('/staff/reports')->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/staff/reports')->assertRedirect('/login');
        $this->get('/superadmin/reports?format=csv')->assertRedirect('/login');
    }
}
