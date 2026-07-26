@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex min-h-10 items-center px-4 py-2 text-sm font-bold leading-5 text-brand-700 dark:text-brand-300 bg-brand-50 dark:bg-brand-900/30 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition duration-150 ease-in-out'
            : 'inline-flex min-h-10 items-center px-4 py-2 text-sm font-semibold leading-5 text-gray-600 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
