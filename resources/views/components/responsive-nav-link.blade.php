@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-500 text-start text-base font-bold text-brand-700 bg-brand-50 dark:text-brand-300 dark:bg-brand-900/30 focus:outline-none focus:ring-2 focus:ring-brand-500 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
