<x-villa-stage :villa="$villa" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :lobby="$lobby" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :menu-items="$menuItems" :menu="false" :title="$lobby['reservation'].' · '.$villa->name">
    <div class="stage-panels">
        @include('villa.reservation')
    </div>
</x-villa-stage>
