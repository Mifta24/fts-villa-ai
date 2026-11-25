<?php

namespace App\Console\Commands;

use App\Models\Villa;
use Database\Seeders\DemoVillaSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sets up the demo villa (unit types, inventory, knowledge base) on an empty
 * database, without the demo login. It runs once: as soon as any villa exists it
 * refuses, because the demo seeder rewrites inventory and would overwrite real
 * bookings. In production it needs --confirm-production.
 */
#[Signature('villa:seed-demo-content {--confirm-production : Required in production}')]
#[Description('Create the demo villa content on an empty database (no demo login, runs only once)')]
class SeedDemoContentCommand extends Command
{
    public function handle(): int
    {
        if (Villa::query()->exists()) {
            $this->components->error('A villa already exists; demo content is only for an empty database. Nothing was changed.');

            return self::FAILURE;
        }

        if ($this->laravel->environment('production') && ! $this->option('confirm-production')) {
            $this->components->error('This is production. Re-run with --confirm-production once the demo content is approved.');

            return self::FAILURE;
        }

        DemoVillaSeeder::$withDemoLogin = false;

        try {
            DB::transaction(fn () => $this->laravel->make(DemoVillaSeeder::class)->setContainer($this->laravel)->setCommand($this)->__invoke());
        } finally {
            DemoVillaSeeder::$withDemoLogin = true;
        }

        $villa = Villa::query()->firstOrFail();
        $this->components->info("Demo villa created: {$villa->slug}. Create the owner with villa:create-admin --villa={$villa->slug}.");

        return self::SUCCESS;
    }
}
