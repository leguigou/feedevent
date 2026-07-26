@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-4 py-2 text-sm font-medium leading-5 text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/30 rounded-xl focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
