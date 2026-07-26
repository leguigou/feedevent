<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                📅 Calendrier
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="btn-ghost text-sm">← Feed</a>
                <a href="{{ route('map') }}" class="btn-ghost text-sm">🗺️</a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="event-card p-3 sm:p-4">
                <div id="calendar" class="min-h-[400px] sm:min-h-[600px]"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    @vite(['resources/js/calendar-init.js'])
    <style>
        .fc { font-size: 0.9em; }
        .fc .fc-toolbar-title { font-size: 1.1em !important; font-weight: 700; }
        .fc .fc-button-primary { background: #7c3aed; border-color: #7c3aed; }
        .fc .fc-button-primary:hover { background: #6d28d9; border-color: #6d28d9; }
        .fc .fc-button-primary:not(:disabled).fc-button-active { background: #5b21b6; border-color: #5b21b6; }
        .fc .fc-button-primary:disabled { opacity: 0.5; }
        .fc .fc-day-today { background: #f5f3ff !important; }
        .fc .fc-daygrid-day-frame { min-height: 80px; }
        .fc .fc-col-header-cell { font-weight: 600; }
        .dark .fc { color: #e5e5e5; }
        .dark .fc .fc-toolbar-title { color: #fff; }
        .dark .fc .fc-button-primary { background: #4c1d95; border-color: #4c1d95; }
        .dark .fc .fc-button-primary:hover { background: #5b21b6; border-color: #5b21b6; }
        .dark .fc .fc-day-today { background: rgba(124, 58, 237, 0.15) !important; }
        .dark .fc .fc-daygrid-day { background: #111118; }
        .dark .fc .fc-col-header-cell { background: #1a1a2e; color: #d1d5db; }
        .dark .fc .fc-daygrid-day-number { color: #d1d5db; }
        .dark .fc .fc-list-day-cushion { background: #1a1a2e; }
        .dark .fc .fc-list-event:hover td { background: #1a1a2e; }
        .dark .fc .fc-day-other .fc-daygrid-day-top { opacity: 0.3; }
        .dark .fc .fc-nonbusiness { background: #0d0d14; }
        @media (max-width: 640px) {
            .fc .fc-toolbar { flex-direction: column; gap: 0.5rem; }
            .fc .fc-toolbar-title { font-size: 1rem !important; }
        }
    </style>
    @endpush
</x-app-layout>
