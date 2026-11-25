<?php

namespace App\Console\Commands;

use App\Models\UnitInventory;
use App\Models\UnitType;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inventory:extend {--days=365 : How many days ahead nights should be open for booking}')]
#[Description('Open further nights for every unit type, repeating the units and prices of the week before; existing days and bookings are never changed')]
class ExtendInventory extends Command
{
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $added = 0;

        UnitType::query()->where('is_active', true)->with('villa')->each(function (UnitType $unitType) use ($days, &$added): void {
            $added += $this->extend($unitType, $days);
        });

        $this->info("Opened {$added} night(s).");

        return self::SUCCESS;
    }

    private function extend(UnitType $unitType, int $days): int
    {
        $last = UnitInventory::query()->where('unit_type_id', $unitType->id)->orderByDesc('stay_date')->first();

        if (! $last) {
            return 0;
        }

        $horizon = CarbonImmutable::now($unitType->villa->timezone)->startOfDay()->addDays($days);
        $week = UnitInventory::query()->where('unit_type_id', $unitType->id)
            ->whereDate('stay_date', '>', $last->stay_date->copy()->subDays(7)->toDateString())
            ->get()
            ->keyBy(fn (UnitInventory $day) => $day->stay_date->toDateString())
            ->map(fn (UnitInventory $day) => ['total_units' => $day->total_units, 'price' => (float) $day->price])
            ->all();

        $rows = [];
        for ($date = CarbonImmutable::parse($last->stay_date)->addDay(); $date->lte($horizon); $date = $date->addDay()) {
            $template = $week[$date->subDays(7)->toDateString()] ?? ['total_units' => $last->total_units, 'price' => (float) $last->price];
            $week[$date->toDateString()] = $template;

            $rows[] = [
                'unit_type_id' => $unitType->id,
                'stay_date' => $date->startOfDay()->toDateTimeString(),
                'total_units' => $template['total_units'],
                'booked_units' => 0,
                'price' => $template['price'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $inserted = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            $inserted += UnitInventory::query()->insertOrIgnore($chunk);
        }

        return $inserted;
    }
}
