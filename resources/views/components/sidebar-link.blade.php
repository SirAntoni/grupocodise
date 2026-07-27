@props(['active' => false])

<a {{ $attributes->merge([
    'class' => ($active
        ? 'bg-brand-700/90 text-white '
        : 'text-slate-300 hover:bg-slate-800 hover:text-white ')
        .'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
]) }}>
    {{ $slot }}
</a>
