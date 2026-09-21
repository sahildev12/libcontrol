<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl border border-transparent bg-brand-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-navy focus:bg-brand-navy focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:ring-offset-2 active:bg-brand-navy']) }}>
    {{ $slot }}
</button>
