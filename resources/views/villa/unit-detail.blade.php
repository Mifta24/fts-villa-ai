<x-villa-stage :villa="$villa" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :lobby="$lobby" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :menu-items="$menuItems" :title="$unitType->translatedName($locale).' · '.$villa->name">
    @if($unitTypes->isNotEmpty())
        <x-slot:sidebar>@include('villa.unit-nav')</x-slot:sidebar>
    @endif

    <div class="stage-panels">
        @include('villa.unit-scene')
    </div>
</x-villa-stage>
