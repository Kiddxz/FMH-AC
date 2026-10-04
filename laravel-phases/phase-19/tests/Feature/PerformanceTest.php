<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 19 (NFR-REQ004 "reasonable response time"): with more than 1,000 customers, pets and
 * appointments, every busy list page still opens quickly, shows one page of results at a time,
 * and does not ask the database once per row (the usual cause of slow pages).
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const RECORDS = 1200;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // 1,200 fictitious customers, each with one pet and one appointment (fast bulk insert)
        $now = now()->toDateTimeString();
        $serviceId = Service::value('id');
        $vetId = User::where('email', 'admin@fmhanimalclinic.com')->value('id');
        $firstCustomer = DB::table('customers')->max('id') + 1;
        $firstPet = DB::table('pets')->max('id') + 1;

        foreach (array_chunk(range(1, self::RECORDS), 300) as $chunk) {
            DB::table('customers')->insert(array_map(fn ($i) => [
                'first_name' => "Load{$i}", 'last_name' => 'Tester', 'contact_number' => '0917' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'is_walk_in' => false, 'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
            DB::table('pets')->insert(array_map(fn ($i) => [
                'customer_id' => $firstCustomer + $i - 1, 'name' => "LoadPet{$i}", 'species' => $i % 2 ? 'dog' : 'cat',
                'gender' => $i % 2 ? 'male' : 'female', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
            DB::table('appointments')->insert(array_map(fn ($i) => [
                'reference' => 'LOAD-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT), 'customer_id' => $firstCustomer + $i - 1,
                'pet_id' => $firstPet + $i - 1, 'service_id' => $serviceId, 'veterinarian_id' => $vetId,
                'appointment_date' => now()->addDays($i % 30)->toDateString(), 'appointment_time' => '09:00',
                'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
        }
    }

    public function test_busy_pages_stay_fast_with_more_than_1000_records(): void
    {
        $pages = [
            'assistant@fmhanimalclinic.com' => ['/staff', '/staff/customers', '/staff/pets', '/staff/appointments', '/staff/patient-flow'],
            'admin@fmhanimalclinic.com' => ['/admin', '/admin/customers', '/admin/pets', '/admin/appointments'],
            'superadmin@fmhanimalclinic.com' => ['/superadmin', '/superadmin/appointments', '/superadmin/pet-records', '/superadmin/activity-logs'],
        ];

        foreach ($pages as $email => $urls) {
            $user = User::where('email', $email)->first();
            foreach ($urls as $url) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $start = microtime(true);

                $this->actingAs($user)->get($url)->assertOk();

                $seconds = microtime(true) - $start;
                $queries = count(DB::getQueryLog());
                DB::disableQueryLog();

                $this->assertLessThan(2.0, $seconds, "{$url} took " . round($seconds, 2) . ' seconds');
                $this->assertLessThan(60, $queries, "{$url} asked the database {$queries} times (one query per row?)");
            }
        }
    }

    public function test_lists_show_one_page_at_a_time(): void
    {
        $staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();

        $this->actingAs($staff)->get('/staff/pets')->assertOk()
            ->assertViewHas('pets', fn ($pets) => $pets->count() <= 15 && $pets->total() > self::RECORDS);
        $this->actingAs($staff)->get('/staff/customers')->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->count() <= 15 && $customers->total() > self::RECORDS);

        // searching still finds one record among all of them
        $this->actingAs($staff)->get('/staff/pets?search=LoadPet777')->assertOk()->assertSee('LoadPet777')->assertDontSee('LoadPet778');
    }
}
