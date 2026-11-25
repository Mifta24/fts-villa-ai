<?php

namespace Tests\Feature;

use App\Models\UnitInventory;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Villa;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionCommandsTest extends TestCase
{
    use RefreshDatabase;

    private function villa(): Villa
    {
        return Villa::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta']);
    }

    private function unitTypeWithAWeek(Villa $villa): UnitType
    {
        $unitType = $villa->unitTypes()->create(['name' => 'Deluxe', 'slug' => 'deluxe', 'base_price' => 1000000, 'max_adults' => 2, 'is_active' => true, 'sort_order' => 0]);
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        for ($day = 0; $day < 7; $day++) {
            UnitInventory::create(['unit_type_id' => $unitType->id, 'stay_date' => $today->addDays($day), 'total_units' => 3, 'booked_units' => $day === 1 ? 2 : 0, 'price' => $day === 4 ? 1150000 : 1000000]);
        }

        return $unitType;
    }

    public function test_inventory_extend_repeats_last_week_and_never_touches_bookings(): void
    {
        $unitType = $this->unitTypeWithAWeek($this->villa());
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $night = fn (int $offset) => UnitInventory::query()->where('unit_type_id', $unitType->id)->whereDate('stay_date', $today->addDays($offset)->toDateString())->first();

        $this->artisan('inventory:extend', ['--days' => 30])->assertSuccessful();
        $count = UnitInventory::count();
        $this->artisan('inventory:extend', ['--days' => 30])->expectsOutputToContain('Opened 0 night(s)')->assertSuccessful();

        $this->assertSame(31, $count);
        $this->assertSame($count, UnitInventory::count());
        $this->assertSame('1150000.00', $night(11)->price);
        $this->assertSame(0, $night(8)->booked_units);
        $this->assertSame(2, $night(1)->booked_units);
    }

    public function test_inventory_extend_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'inventory:extend'));

        $this->assertCount(1, $events);
        $this->assertSame('0 1 * * *', $events->first()->expression);
    }

    public function test_demo_content_runs_once_on_an_empty_database_without_a_demo_login(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('villa:seed-demo-content')->assertFailed();
        $this->assertSame(0, Villa::count());

        $this->artisan('villa:seed-demo-content', ['--confirm-production' => true])->assertSuccessful();

        $this->assertSame(1, Villa::count());
        $this->assertGreaterThan(0, UnitType::count());
        $this->assertGreaterThan(0, UnitInventory::count());
        $this->assertSame(0, User::count(), 'No account with a known password is created.');

        $inventory = UnitInventory::query()->orderBy('id')->pluck('booked_units', 'id')->all();
        $this->artisan('villa:seed-demo-content', ['--confirm-production' => true])->assertFailed();
        $this->assertSame($inventory, UnitInventory::query()->orderBy('id')->pluck('booked_units', 'id')->all());
    }

    public function test_create_admin_reads_the_password_from_the_prompt_and_attaches_the_owner(): void
    {
        $villa = $this->villa();

        $this->artisan('villa:create-admin', ['--email' => 'Owner@Example.test', '--name' => 'Owner', '--villa' => 'demo'])
            ->expectsQuestion('Password (minimum 12 characters)', 'a-strong-password')
            ->expectsQuestion('Confirm password', 'a-strong-password')
            ->doesntExpectOutputToContain('a-strong-password')
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
        $this->assertSame('owner', $villa->users()->first()->pivot->role);
    }

    public function test_create_admin_refuses_weak_passwords_unknown_villas_and_existing_emails(): void
    {
        $this->villa();
        User::factory()->create(['email' => 'taken@example.test']);

        $this->artisan('villa:create-admin', ['--email' => 'a@example.test', '--name' => 'A', '--villa' => 'nowhere'])->assertFailed();
        $this->artisan('villa:create-admin', ['--email' => 'taken@example.test', '--name' => 'A', '--villa' => 'demo'])->assertFailed();
        $this->artisan('villa:create-admin', ['--email' => 'a@example.test', '--name' => 'A', '--villa' => 'demo'])
            ->expectsQuestion('Password (minimum 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
