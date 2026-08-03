<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-sky-600 to-indigo-600 border border-transparent rounded-2xl font-semibold text-sm text-white uppercase tracking-[0.2em] shadow-lg shadow-sky-500/20 hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
