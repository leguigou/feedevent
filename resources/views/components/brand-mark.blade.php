@props(['compact' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="relative grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-[13px] bg-gradient-to-br from-brand-500 via-violet-600 to-fuchsia-500 shadow-lg shadow-brand-500/20" aria-hidden="true">
        <svg viewBox="0 0 36 36" class="h-9 w-9 text-white" fill="none">
            <circle cx="18" cy="18" r="10" stroke="currentColor" stroke-width="1.5" opacity=".35"/>
            <circle cx="18" cy="18" r="5.25" stroke="currentColor" stroke-width="1.5" opacity=".65"/>
            <path d="M18 8.2l1.6 5.2 5.2 1.6-5.2 1.6-1.6 5.2-1.6-5.2-5.2-1.6 5.2-1.6L18 8.2Z" fill="currentColor"/>
            <circle cx="26.5" cy="25.5" r="2.25" fill="#FDE047"/>
        </svg>
    </span>
    @unless($compact)
        <span class="text-[1.05rem] font-extrabold tracking-[-0.035em] text-gray-950 dark:text-white">
            feed<span class="text-brand-600 dark:text-brand-400">event</span>
        </span>
    @endunless
</span>
