<x-villa-stage :villa="$villa" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :lobby="$lobby" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :menu-items="$menuItems">
    <x-slot:welcome>
        <div class="stage-welcome">
            <span class="lobby-eyebrow">{{ $villa->name }}</span>
            <h1>{{ $lobby['welcome'] }}<br><em>{{ $lobby['lobby'] }}.</em></h1>
            <p>{{ $lobby['intro'] }}</p>
        </div>
    </x-slot:welcome>
</x-villa-stage>
