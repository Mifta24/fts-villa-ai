<x-villa-stage :villa="$villa" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :lobby="$lobby" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :menu-items="$menuItems" :title="$labels['units_heading'].' · '.$villa->name">
    <x-slot:welcome>
        <div class="stage-welcome stage-welcome-scene" data-anchor="{{ $backdrop['text'] }}">
            <span class="lobby-eyebrow">{{ $lobby['explore'] }}</span>
            <h1>{{ $labels['units_heading'] }}</h1>
            @if ($unitNarrations['units'])
                @include('villa.narrator', ['text' => $unitNarrations['units'], 'key' => 'units', 'onStage' => true])
            @else
                <p>{{ $lobby['units_empty'] }}</p>
            @endif
        </div>
    </x-slot:welcome>

    @if ($unitTypes->isNotEmpty())
        <x-slot:sidebar>@include('villa.unit-nav')</x-slot:sidebar>
    @endif
</x-villa-stage>
